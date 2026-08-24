<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$db = get_db();
$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);

$eventStmt = $db->prepare(
    "SELECT e.*, v.name AS venue_name, v.city
     FROM events e JOIN venues v ON v.venue_id = e.venue_id
     WHERE e.event_id = ? AND e.status = 'Published'"
);
$eventStmt->execute([$eventId]);
$event = $eventStmt->fetch();
if (!$event) {
    flash('error', 'Event not found.');
    redirect('/public/events.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seatIds = array_map('intval', $_POST['seat_ids'] ?? []);
    $seatIds = array_values(array_filter($seatIds));

    if (!$seatIds) {
        flash('error', 'Please select at least one seat.');
        redirect('/public/booking.php?event_id=' . $eventId);
    }

    try {
        $db->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
        $check = $db->prepare(
            "SELECT COUNT(*) FROM booking_seats
             WHERE event_id = ? AND seat_id IN ($placeholders) AND status IN ('Held','Booked')"
        );
        $check->execute(array_merge([$eventId], $seatIds));
        if ((int)$check->fetchColumn() > 0) {
            throw new RuntimeException('One or more seats are already booked.');
        }

        $priceStmt = $db->prepare(
            "SELECT s.seat_id, tt.price
             FROM seats s
             JOIN seat_rows r ON r.row_id = s.row_id
             JOIN sections sec ON sec.section_id = r.section_id
             JOIN ticket_types tt ON tt.section_id = sec.section_id AND tt.event_id = ?
             WHERE s.seat_id IN ($placeholders)"
        );
        $priceStmt->execute(array_merge([$eventId], $seatIds));
        $prices = $priceStmt->fetchAll();
        if (count($prices) !== count($seatIds)) {
            throw new RuntimeException('Invalid seat selection for this event.');
        }

        $total = array_sum(array_map(fn($row) => (float)$row['price'], $prices));
        $booking = $db->prepare('INSERT INTO bookings(user_id, event_id, total_amount, status) VALUES (?, ?, ?, ?)');
        $booking->execute([current_user()['user_id'], $eventId, $total, 'Pending']);
        $bookingId = (int)$db->lastInsertId();

        $seatInsert = $db->prepare('INSERT INTO booking_seats(booking_id, event_id, seat_id, price, status) VALUES (?, ?, ?, ?, ?)');
        foreach ($prices as $row) {
            $seatInsert->execute([$bookingId, $eventId, (int)$row['seat_id'], (float)$row['price'], 'Held']);
        }

        $payment = $db->prepare('INSERT INTO payments(booking_id, amount, status, transaction_ref) VALUES (?, ?, ?, ?)');
        $payment->execute([$bookingId, $total, 'Pending', 'MOCK-' . time() . '-' . $bookingId]);
        log_action(current_user()['user_id'], 'CREATE_BOOKING', 'bookings', $bookingId, 'Seats: ' . implode(',', $seatIds));
        $db->commit();

        flash('success', 'Seats held. Complete mock payment to confirm your booking.');
        redirect('/public/payment.php?booking_id=' . $bookingId);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        flash('error', 'Booking failed: ' . $e->getMessage());
        redirect('/public/booking.php?event_id=' . $eventId);
    }
}

$seatStmt = $db->prepare(
    "SELECT s.seat_id, sec.name AS section_name, r.row_label, s.seat_number, tt.price,
            CASE WHEN bs.booking_seat_id IS NULL THEN 1 ELSE 0 END AS is_available
     FROM seats s
     JOIN seat_rows r ON r.row_id = s.row_id
     JOIN sections sec ON sec.section_id = r.section_id
     JOIN ticket_types tt ON tt.section_id = sec.section_id AND tt.event_id = ?
     LEFT JOIN booking_seats bs ON bs.event_id = ? AND bs.seat_id = s.seat_id AND bs.status IN ('Held','Booked')
     WHERE sec.venue_id = ?
     ORDER BY sec.name, r.row_label, CAST(s.seat_number AS UNSIGNED)"
);
$seatStmt->execute([$eventId, $eventId, $event['venue_id']]);
$seats = $seatStmt->fetchAll();

// Group seats by Section and Row for clean layout formatting
$groupedSeats = [];
foreach ($seats as $seat) {
    $sec = $seat['section_name'];
    $row = $seat['row_label'];
    $groupedSeats[$sec][$row][] = $seat;
}

$title = 'Book Tickets';
page_header($title);
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-3 py-1.5 mb-2 fw-semibold border border-danger border-opacity-25">
            <i class="bi bi-ticket-perforated me-1"></i> Interactive Seating
        </span>
        <h1 class="h3 fw-bold mb-1"><?= e($event['title']) ?></h1>
        <p class="text-white-50 mb-0 small">
            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($event['venue_name']) ?>, <?= e($event['city']) ?>
            <span class="mx-2">&bull;</span>
            <i class="bi bi-calendar3 text-danger me-1"></i><?= e(date('d M Y, h:i A', strtotime($event['event_date']))) ?>
        </p>
    </div>
    <a class="btn btn-outline-light rounded-pill btn-sm px-3" href="events.php">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Seat States Legend -->
<div class="seat-legend">
    <div class="legend-item">
        <div class="legend-dot available"></div>
        <span>Available</span>
    </div>
    <div class="legend-item">
        <div class="legend-dot selected"></div>
        <span>Selected</span>
    </div>
    <div class="legend-item">
        <div class="legend-dot sold"></div>
        <span>Sold / Held</span>
    </div>
</div>

<!-- Curved Screen Glow -->
<div class="screen-container">
    <div class="screen-glow"></div>
    <span class="screen-label">STAGE / SCREEN THIS WAY</span>
</div>

<form method="post" id="booking-form">
    <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
    
    <?php foreach ($groupedSeats as $sectionName => $rows): ?>
        <div class="glass-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25 pb-2 mb-3">
                <h3 class="h6 mb-0 text-white-50 fw-bold text-uppercase tracking-wider">
                    <i class="bi bi-grid-3x3-gap me-2 text-danger"></i><?= e($sectionName) ?> Section
                </h3>
                <span class="small text-danger fw-semibold">
                    Rs. <?= number_format((float)reset($rows)[0]['price'], 2) ?> / ticket
                </span>
            </div>
            
            <div class="d-flex flex-column gap-2" style="overflow-x: auto; padding-bottom: 0.5rem;">
                <?php foreach ($rows as $rowLabel => $rowSeats): ?>
                    <div class="seat-row-wrapper" style="min-width: 500px;">
                        <div class="row-label-indicator text-center"><?= e($rowLabel) ?></div>
                        <div class="row-seats-grid">
                            <?php foreach ($rowSeats as $seat): ?>
                                <div class="seat-box <?= $seat['is_available'] ? '' : 'disabled' ?>">
                                    <input type="checkbox" 
                                           class="seat-checkbox"
                                           name="seat_ids[]" 
                                           id="seat-<?= (int)$seat['seat_id'] ?>" 
                                           value="<?= (int)$seat['seat_id'] ?>" 
                                           data-price="<?= (float)$seat['price'] ?>" 
                                           data-label="<?= e($seat['section_name']) ?> <?= e($seat['row_label']) ?>-<?= e($seat['seat_number']) ?>"
                                           <?= $seat['is_available'] ? '' : 'disabled' ?>>
                                    <label class="seat-checkmark" for="seat-<?= (int)$seat['seat_id'] ?>" title="Seat <?= e($seat['row_label']) ?>-<?= e($seat['seat_number']) ?> (Rs. <?= number_format((float)$seat['price'], 2) ?>)">
                                        <?= e($seat['seat_number']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</form>

<!-- Floating Checkout Summary Drawer -->
<div class="checkout-drawer" id="checkout-drawer">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="text-white-50 small"><i class="bi bi-check2-square text-danger me-1"></i>Selected Seats (<span id="seat-count-display">0</span>)</div>
            <div class="fw-bold text-white text-truncate" id="selected-seats-display" style="max-width: 450px;">None</div>
        </div>
        <div class="d-flex align-items-center gap-4">
            <div class="text-end">
                <div class="text-white-50 small">Total Amount</div>
                <div class="fw-bold fs-4 text-danger" id="total-price-display">Rs. 0.00</div>
            </div>
            <button type="submit" form="booking-form" class="btn btn-primary rounded-pill px-4 py-2.5 d-flex align-items-center gap-2">
                <span>Book Tickets</span>
                <i class="bi bi-arrow-right-short fs-5"></i>
            </button>
        </div>
    </div>
</div>
<?php page_footer(); ?>
