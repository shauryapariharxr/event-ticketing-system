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
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="h3 fw-bold mb-1">Your Tickets</h1>
        <p class="text-white-50 small mb-0">Present these passes at the gate for scan validation.</p>
    </div>
    <button class="btn btn-outline-light rounded-pill px-4" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print Passes
    </button>
</div>

<div class="row g-4">
    <?php foreach ($tickets as $ticket): ?>
        <div class="col-xl-6">
            <div class="ticket-wrapper">
                <div class="ticket-main">
                    <span class="badge bg-danger bg-opacity-25 text-danger rounded-pill px-2.5 py-1 mb-3 fw-semibold border border-danger border-opacity-25 small">
                        <i class="bi bi-calendar-check me-1"></i>Admission Pass
                    </span>
                    <h2 class="h4 fw-bold text-white mb-3"><?= e($ticket['title']) ?></h2>
                    
                    <div class="row g-2 mb-3 small">
                        <div class="col-sm-6 text-white-50">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($ticket['venue_name']) ?>
                        </div>
                        <div class="col-sm-6 text-white-50">
                            <i class="bi bi-calendar3 text-danger me-1"></i><?= e(date('d M Y, h:i A', strtotime($ticket['event_date']))) ?>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 pt-2 border-top border-secondary border-opacity-25">
                        <div class="bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded px-3 py-1.5 text-center">
                            <span class="text-white-50 small d-block" style="font-size: 0.65rem; text-transform: uppercase;">Section</span>
                            <span class="fw-bold text-white"><?= e($ticket['section_name']) ?></span>
                        </div>
                        <div class="bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded px-3 py-1.5 text-center">
                            <span class="text-white-50 small d-block" style="font-size: 0.65rem; text-transform: uppercase;">Row</span>
                            <span class="fw-bold text-white"><?= e($ticket['row_label']) ?></span>
                        </div>
                        <div class="bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded px-3 py-1.5 text-center">
                            <span class="text-white-50 small d-block" style="font-size: 0.65rem; text-transform: uppercase;">Seat</span>
                            <span class="fw-bold text-white"><?= e($ticket['seat_number']) ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-white-50 small">
                        <span>Holder: <strong><?= e($ticket['customer']) ?></strong></span>
                    </div>
                </div>
                
                <div class="ticket-stub">
                    <div class="barcode-visual"></div>
                    <code class="text-white fw-bold d-block mb-2" style="font-size: 0.8rem; letter-spacing: 0.5px;"><?= e($ticket['ticket_number']) ?></code>
                    
                    <?php if ($ticket['status'] === 'Valid'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 0.75rem;">
                            <i class="bi bi-check-circle-fill me-1"></i><?= e($ticket['status']) ?>
                        </span>
                    <?php elseif ($ticket['status'] === 'Used'): ?>
                        <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25 rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 0.75rem;">
                            <i class="bi bi-x-circle-fill me-1"></i><?= e($ticket['status']) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 text-uppercase fw-bold" style="font-size: 0.75rem;">
                            <i class="bi bi-slash-circle-fill me-1"></i><?= e($ticket['status']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php page_footer(); ?>
