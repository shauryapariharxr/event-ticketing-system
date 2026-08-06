<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$bookingId = (int)($_GET['booking_id'] ?? 0);
$stmt = get_db()->prepare(
    "SELECT t.ticket_number, t.status, e.title, e.event_date, v.name AS venue_name, u.name AS customer,
            sec.name AS section_name, r.row_label, s.seat_number
     FROM tickets t
     JOIN bookings b ON b.booking_id = t.booking_id
     JOIN users u ON u.user_id = b.user_id
     JOIN events e ON e.event_id = b.event_id
     JOIN venues v ON v.venue_id = e.venue_id
     JOIN booking_seats bs ON bs.booking_seat_id = t.booking_seat_id
     JOIN seats s ON s.seat_id = bs.seat_id
     JOIN seat_rows r ON r.row_id = s.row_id
     JOIN sections sec ON sec.section_id = r.section_id
     WHERE b.booking_id = ? AND b.user_id = ?"
);
$stmt->execute([$bookingId, current_user()['user_id']]);
$tickets = $stmt->fetchAll();
if (!$tickets) {
    flash('error', 'Tickets not found.');
    redirect('/public/profile.php');
}

$title = 'Tickets';
page_header($title);
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h1 class="h3 mb-0">Tickets</h1>
    <button class="btn btn-outline-primary" onclick="window.print()">Print</button>
</div>
<div class="row g-3">
    <?php foreach ($tickets as $ticket): ?>
        <div class="col-md-6">
            <div class="ticket">
                <div>
                    <h2 class="h4"><?= e($ticket['title']) ?></h2>
                    <p class="mb-1"><?= e($ticket['venue_name']) ?></p>
                    <p class="mb-1"><?= e(date('d M Y, h:i A', strtotime($ticket['event_date']))) ?></p>
                    <p class="mb-1">Seat: <?= e($ticket['section_name']) ?> <?= e($ticket['row_label']) ?>-<?= e($ticket['seat_number']) ?></p>
                    <p class="mb-0">Customer: <?= e($ticket['customer']) ?></p>
                </div>
                <div class="ticket-code">
                    <strong><?= e($ticket['ticket_number']) ?></strong>
                    <span><?= e($ticket['status']) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php page_footer(); ?>
