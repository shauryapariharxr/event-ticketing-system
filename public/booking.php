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

$title = 'Book Tickets';
page_header($title);
?>
<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="h3"><?= e($event['title']) ?></h1>
        <p class="text-muted mb-0"><?= e($event['venue_name']) ?>, <?= e($event['city']) ?> - <?= e(date('d M Y, h:i A', strtotime($event['event_date']))) ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="events.php">Back</a>
</div>
<form method="post">
    <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">
    <div class="seat-grid mb-3">
        <?php foreach ($seats as $seat): ?>
            <label class="seat-tile <?= $seat['is_available'] ? '' : 'disabled' ?>">
                <input type="checkbox" name="seat_ids[]" value="<?= (int)$seat['seat_id'] ?>" <?= $seat['is_available'] ? '' : 'disabled' ?>>
                <span><?= e($seat['section_name']) ?> <?= e($seat['row_label']) ?>-<?= e($seat['seat_number']) ?></span>
                <small>Rs. <?= number_format((float)$seat['price'], 2) ?></small>
            </label>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary">Confirm Booking</button>
</form>
<?php page_footer(); ?>
