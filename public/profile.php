<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$db = get_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'cancel') {
    $bookingId = (int)post('booking_id');
    $stmt = $db->prepare(
        "SELECT b.*, p.payment_id FROM bookings b LEFT JOIN payments p ON p.booking_id = b.booking_id
         WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'Confirmed'"
    );
    $stmt->execute([$bookingId, current_user()['user_id']]);
    $booking = $stmt->fetch();
    if ($booking) {
        $db->beginTransaction();
        $db->prepare("UPDATE bookings SET status = 'Refunded' WHERE booking_id = ?")->execute([$bookingId]);
        $db->prepare("UPDATE payments SET status = 'Refunded' WHERE booking_id = ?")->execute([$bookingId]);
        $db->prepare("INSERT INTO refunds(booking_id, payment_id, amount, reason, status, processed_at) VALUES (?, ?, ?, ?, 'Processed', NOW())")
            ->execute([$bookingId, $booking['payment_id'], $booking['total_amount'], 'Customer cancellation']);
        log_action(current_user()['user_id'], 'CANCEL_BOOKING', 'bookings', $bookingId);
        $db->commit();
        flash('success', 'Booking cancelled and refund processed.');
    }
    redirect('/public/profile.php');
}

$stmt = $db->prepare(
    "SELECT b.*, e.title, e.event_date, p.status AS payment_status
     FROM bookings b JOIN events e ON e.event_id = b.event_id
     LEFT JOIN payments p ON p.booking_id = b.booking_id
     WHERE b.user_id = ? ORDER BY b.booked_at DESC"
);
$stmt->execute([current_user()['user_id']]);
$bookings = $stmt->fetchAll();

$title = 'Profile';
page_header($title);
?>
<h1 class="h3">My Bookings</h1>
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>Event</th><th>Date</th><th>Amount</th><th>Booking</th><th>Payment</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?= e($booking['title']) ?></td>
                <td><?= e(date('d M Y', strtotime($booking['event_date']))) ?></td>
                <td>Rs. <?= number_format((float)$booking['total_amount'], 2) ?></td>
                <td><?= e($booking['status']) ?></td>
                <td><?= e($booking['payment_status']) ?></td>
                <td class="d-flex gap-2">
                    <?php if ($booking['status'] === 'Pending'): ?>
                        <a class="btn btn-sm btn-primary" href="payment.php?booking_id=<?= (int)$booking['booking_id'] ?>">Pay</a>
                    <?php endif; ?>
                    <?php if ($booking['status'] === 'Confirmed'): ?>
                        <a class="btn btn-sm btn-outline-primary" href="ticket.php?booking_id=<?= (int)$booking['booking_id'] ?>">Tickets</a>
                        <form method="post">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Cancel</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php page_footer(); ?>
