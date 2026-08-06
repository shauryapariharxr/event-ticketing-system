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
<div class="auth-box">
    <h1 class="h3">Validate Ticket</h1>
    <form method="post" class="mb-3">
        <label class="form-label">Ticket Number</label>
        <input class="form-control mb-2" name="ticket_number" required>
        <button class="btn btn-primary w-100">Validate</button>
    </form>
    <?php if ($result): ?>
        <div class="alert <?= $result === 'Accepted' ? 'alert-success' : 'alert-danger' ?>">
            <?= e($result) ?>
            <?php if ($ticket): ?>
                for <?= e($ticket['title']) ?> on <?= e(date('d M Y, h:i A', strtotime($ticket['event_date']))) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php page_footer(); ?>
