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
<div class="row g-4 justify-content-center align-items-start">
    <div class="col-lg-5">
        <div class="glass-card p-4">
            <h2 class="h5 fw-bold mb-3 text-white border-bottom border-secondary border-opacity-25 pb-2">
                <i class="bi bi-cart3 text-danger me-2"></i>Order Summary
            </h2>
            <div class="mb-4">
                <h3 class="h6 text-white fw-bold mb-1"><?= e($booking['title']) ?></h3>
                <p class="text-white-50 small mb-0">
                    Booking ID: <code class="text-danger">#<?= (int)$bookingId ?></code>
                </p>
            </div>

            <div class="info-list">
                <div class="info-item"><span>Ticket Price Total</span><strong class="text-white">Rs. <?= number_format((float)$booking['amount'], 2) ?></strong></div>
                <div class="info-item"><span>Booking Fee / Taxes</span><strong class="text-success">Rs. 0.00</strong></div>
            </div>

            <div class="d-flex justify-content-between border-top border-secondary border-opacity-25 pt-3 mt-3 fw-bold text-white fs-5">
                <span>Amount Payable</span>
                <span class="text-danger">Rs. <?= number_format((float)$booking['amount'], 2) ?></span>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="glass-card p-4">
            <h2 class="h5 fw-bold mb-4 text-white border-bottom border-secondary border-opacity-25 pb-2">
                <i class="bi bi-wallet2 text-danger me-2"></i>Payment Terminal
            </h2>

            <div class="payment-card-visual text-white">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <i class="bi bi-cpu fs-3 text-warning"></i>
                    <span class="fw-bold tracking-widest small">SECURE DB CHIP</span>
                </div>
                <div class="fs-5 fw-semibold tracking-wider mb-3">••••  ••••  ••••  <?= sprintf('%04d', random_int(1000, 9999)) ?></div>
                <div class="row">
                    <div class="col-8">
                        <div class="text-white-50" style="font-size: 0.7rem; text-transform: uppercase;">Card Holder</div>
                        <div class="small fw-semibold"><?= e(current_user()['name']) ?></div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="text-white-50" style="font-size: 0.7rem; text-transform: uppercase;">Expiry</div>
                        <div class="small fw-semibold">12 / 29</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Card Number</label>
                    <input type="text" class="form-control" placeholder="4111 2222 3333 4444" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">CVV</label>
                    <input type="password" class="form-control" placeholder="***" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expiry</label>
                    <input type="text" class="form-control" placeholder="MM/YY" disabled>
                </div>
            </div>

            <form method="post" class="d-flex flex-column gap-2">
                <input type="hidden" name="booking_id" value="<?= (int)$bookingId ?>">
                <button class="btn btn-primary rounded-pill py-2.5 d-flex align-items-center justify-content-center gap-2" name="result" value="success">
                    <i class="bi bi-shield-check fs-5"></i>
                    <span>Simulate Success Payment</span>
                </button>
                <button class="btn btn-outline-danger rounded-pill py-2.5 d-flex align-items-center justify-content-center gap-2" name="result" value="failed">
                    <i class="bi bi-shield-x fs-5"></i>
                    <span>Simulate Failed Payment</span>
                </button>
            </form>
            <p class="text-center text-muted small mt-3 mb-0">
                <i class="bi bi-lock-fill text-success me-1"></i>Secure mock payments processed locally. No actual money is transferred.
            </p>
        </div>
    </div>
</div>
<?php page_footer(); ?>
