<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$db = get_db();
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$stmt = $db->prepare(
    "SELECT b.*, e.title, p.payment_id, p.status AS payment_status, p.amount
     FROM bookings b
     JOIN events e ON e.event_id = b.event_id
     JOIN payments p ON p.booking_id = b.booking_id
     WHERE b.booking_id = ? AND b.user_id = ?"
);
$stmt->execute([$bookingId, current_user()['user_id']]);
$booking = $stmt->fetch();
if (!$booking) {
    flash('error', 'Booking not found.');
    redirect('/public/profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = post('result');
    try {
        $db->beginTransaction();
        if ($result === 'success') {
            $pay = $db->prepare("UPDATE payments SET status = 'Success', paid_at = NOW() WHERE booking_id = ?");
            $pay->execute([$bookingId]);

            $seatStmt = $db->prepare('SELECT booking_seat_id FROM booking_seats WHERE booking_id = ?');
            $seatStmt->execute([$bookingId]);
            $ticketInsert = $db->prepare('INSERT INTO tickets(booking_id, booking_seat_id, ticket_number, qr_code) VALUES (?, ?, ?, ?)');
            foreach ($seatStmt->fetchAll() as $seat) {
                $ticketNo = 'TKT-' . $bookingId . '-' . $seat['booking_seat_id'] . '-' . random_int(1000, 9999);
                $ticketInsert->execute([$bookingId, $seat['booking_seat_id'], $ticketNo, $ticketNo]);
            }
            log_action(current_user()['user_id'], 'PAYMENT_SUCCESS', 'payments', (int)$booking['payment_id']);
            flash('success', 'Payment successful. Tickets generated.');
            $db->commit();
            redirect('/public/ticket.php?booking_id=' . $bookingId);
        }

        $db->prepare("UPDATE payments SET status = 'Failed' WHERE booking_id = ?")->execute([$bookingId]);
        $db->prepare("UPDATE bookings SET status = 'Cancelled' WHERE booking_id = ?")->execute([$bookingId]);
        log_action(current_user()['user_id'], 'PAYMENT_FAILED', 'payments', (int)$booking['payment_id']);
        $db->commit();
        flash('error', 'Mock payment failed. Seats were released.');
        redirect('/public/profile.php');
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        flash('error', 'Payment failed: ' . $e->getMessage());
    }
}

$title = 'Payment';
page_header($title);
?>
<div class="card payment-card">
    <div class="card-body">
        <h1 class="h4">Mock Payment</h1>
        <p class="mb-1"><strong>Event:</strong> <?= e($booking['title']) ?></p>
        <p class="mb-3"><strong>Amount:</strong> Rs. <?= number_format((float)$booking['amount'], 2) ?></p>
        <form method="post" class="d-flex gap-2">
            <input type="hidden" name="booking_id" value="<?= (int)$bookingId ?>">
            <button class="btn btn-success" name="result" value="success">Pay Successfully</button>
            <button class="btn btn-outline-danger" name="result" value="failed">Fail Payment</button>
        </form>
    </div>
</div>
<?php page_footer(); ?>
