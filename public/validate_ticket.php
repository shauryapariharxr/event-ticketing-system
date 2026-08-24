<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['Administrator','Gate Staff']);

$ticket = null;
$result = null;
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketNumber = post('ticket_number');
    $stmt = $db->prepare(
        "SELECT t.*, e.title, e.event_date, b.status AS booking_status
         FROM tickets t
         JOIN bookings b ON b.booking_id = t.booking_id
         JOIN events e ON e.event_id = b.event_id
         WHERE t.ticket_number = ?"
    );
    $stmt->execute([$ticketNumber]);
    $ticket = $stmt->fetch();

    if (!$ticket || $ticket['status'] !== 'Valid' || $ticket['booking_status'] !== 'Confirmed') {
        $result = 'Rejected';
    } else {
        $result = 'Accepted';
        $db->prepare("UPDATE tickets SET status = 'Used' WHERE ticket_id = ?")->execute([(int)$ticket['ticket_id']]);
    }

    if ($ticket) {
        $db->prepare('INSERT INTO qr_scans(ticket_id, scanned_by, result, notes) VALUES (?, ?, ?, ?)')
            ->execute([(int)$ticket['ticket_id'], current_user()['user_id'], $result, 'Manual ticket number validation']);
        log_action(current_user()['user_id'], 'VALIDATE_TICKET', 'tickets', (int)$ticket['ticket_id'], $result);
    }
}

$title = 'Validate Ticket';
page_header($title);
?>
<div class="glass-card auth-box">
    <h1 class="h3 mb-3 fw-bold text-white text-center">
        <i class="bi bi-qr-code-scan text-danger me-2"></i>Gate Check-In
    </h1>
    <p class="text-white-50 text-center small mb-4">Validate customer admission passes by ticket number.</p>
    
    <form method="post" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Ticket Number</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="bi bi-ticket-perforated"></i></span>
                <input class="form-control" name="ticket_number" required placeholder="TKT-XXXX-XXXX-XXXX">
            </div>
        </div>
        <button class="btn btn-primary w-100 rounded-pill py-2.5">
            <i class="bi bi-check-circle me-1"></i>Verify & Validate
        </button>
    </form>
    
    <?php if ($result): ?>
        <div class="alert <?= $result === 'Accepted' ? 'alert-success border-success' : 'alert-danger border-danger' ?> d-flex align-items-start gap-2 rounded-3 small">
            <div>
                <?php if ($result === 'Accepted'): ?>
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <?php else: ?>
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <?php endif; ?>
            </div>
            <div>
                <strong class="d-block text-white"><?= e($result) ?></strong>
                <?php if ($ticket): ?>
                    <span class="text-white-50">
                        Event: <?= e($ticket['title']) ?><br>
                        Date: <?= e(date('d M Y, h:i A', strtotime($ticket['event_date']))) ?>
                    </span>
                <?php else: ?>
                    <span class="text-white-50">Ticket not found or booking not confirmed.</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php page_footer(); ?>
