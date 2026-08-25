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
<div class="glass-card p-4">
    <h1 class="h3 fw-bold mb-4 text-white d-flex align-items-center gap-2">
        <span class="bg-danger d-inline-block rounded-2" style="width: 6px; height: 24px;"></span>
        My Booking History
    </h1>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Event Details</th>
                    <th>Event Date</th>
                    <th>Total Price</th>
                    <th>Booking Status</th>
                    <th>Payment Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td class="fw-bold text-white"><?= e($booking['title']) ?></td>
                    <td class="text-white-50"><?= e(date('d M Y', strtotime($booking['event_date']))) ?></td>
                    <td class="text-danger fw-semibold">Rs. <?= number_format((float)$booking['total_amount'], 2) ?></td>
                    <td>
                        <?php if ($booking['status'] === 'Confirmed'): ?>
                            <span class="status-badge success"><i class="bi bi-shield-check me-1"></i><?= e($booking['status']) ?></span>
                        <?php elseif ($booking['status'] === 'Pending'): ?>
                            <span class="status-badge warning"><i class="bi bi-clock me-1"></i><?= e($booking['status']) ?></span>
                        <?php elseif ($booking['status'] === 'Cancelled'): ?>
                            <span class="status-badge danger"><i class="bi bi-shield-x me-1"></i><?= e($booking['status']) ?></span>
                        <?php else: ?>
                            <span class="status-badge info"><i class="bi bi-arrow-counterclockwise me-1"></i><?= e($booking['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($booking['payment_status'] === 'Success'): ?>
                            <span class="status-badge success">Paid</span>
                        <?php elseif ($booking['payment_status'] === 'Pending'): ?>
                            <span class="status-badge warning">Unpaid</span>
                        <?php elseif ($booking['payment_status'] === 'Failed'): ?>
                            <span class="status-badge danger">Failed</span>
                        <?php else: ?>
                            <span class="status-badge info">Refunded</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <?php if ($booking['status'] === 'Pending'): ?>
                                <a class="btn btn-sm btn-primary rounded-pill px-3" href="payment.php?booking_id=<?= (int)$booking['booking_id'] ?>">
                                    <i class="bi bi-credit-card me-1"></i>Pay Now
                                </a>
                            <?php endif; ?>
                            <?php if ($booking['status'] === 'Confirmed'): ?>
                                <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="ticket.php?booking_id=<?= (int)$booking['booking_id'] ?>">
                                    <i class="bi bi-ticket-perforated me-1"></i>View Tickets
                                </a>
                                <form method="post" class="m-0">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        <i class="bi bi-trash3 me-1"></i>Cancel
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x display-6 d-block mb-2"></i>
                        No bookings made yet.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php page_footer(); ?>
