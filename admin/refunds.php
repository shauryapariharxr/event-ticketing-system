<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['Administrator']);
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $refundId = (int)post('refund_id');
    $status = post('status');
    $db->prepare('UPDATE refunds SET status=?, processed_at=NOW() WHERE refund_id=?')->execute([$status, $refundId]);
    log_action(current_user()['user_id'], 'UPDATE_REFUND', 'refunds', $refundId, $status);
    flash('success', 'Refund updated.');
    redirect('/admin/refunds.php');
}

$refunds = $db->query(
    "SELECT r.*, u.name AS customer, e.title
     FROM refunds r
     JOIN bookings b ON b.booking_id=r.booking_id
     JOIN users u ON u.user_id=b.user_id
     JOIN events e ON e.event_id=b.event_id
     ORDER BY r.requested_at DESC"
)->fetchAll();
$title = 'Refunds';
page_header($title);
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-brand-mark"><i class="bi bi-cash-stack"></i></div>
            <div>
                <p class="admin-sidebar-title">Finance</p>
                <p class="admin-sidebar-name">Refund Queue</p>
            </div>
        </div>

        <nav class="admin-nav">
            <a class="admin-nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
            <a class="admin-nav-link" href="venues.php"><i class="bi bi-building"></i>Venues</a>
            <a class="admin-nav-link" href="events.php"><i class="bi bi-calendar-event"></i>Events</a>
            <a class="admin-nav-link" href="seats.php"><i class="bi bi-layout-text-window"></i>Seats</a>
            <a class="admin-nav-link" href="users.php"><i class="bi bi-people"></i>Users</a>
            <a class="admin-nav-link active" href="refunds.php"><i class="bi bi-cash-stack"></i>Refunds</a>
            <a class="admin-nav-link" href="reports.php"><i class="bi bi-bar-chart"></i>Reports</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-panel">
            <div class="page-section-header">
                <div>
                    <h1 class="page-section-title">Refund Management</h1>
                    <div class="page-section-subtitle">Review and resolve customer refund requests</div>
                </div>
            </div>
            <table class="admin-table align-middle">
                <thead><tr><th>Customer</th><th>Event</th><th>Amount</th><th>Reason</th><th>Status</th><th>Updated</th></tr></thead>
                <tbody>
                <?php foreach ($refunds as $refund): ?>
                    <tr>
                        <td><?= e($refund['customer']) ?></td>
                        <td><?= e($refund['title']) ?></td>
                        <td>Rs. <?= number_format((float)$refund['amount'], 2) ?></td>
                        <td><?= e($refund['reason']) ?></td>
                        <td>
                            <form method="post" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="refund_id" value="<?= (int)$refund['refund_id'] ?>">
                                <select class="form-select form-select-sm" name="status"><?php foreach (['Requested','Approved','Rejected','Processed'] as $s): ?><option <?= selected($refund['status'], $s) ?>><?= $s ?></option><?php endforeach; ?></select>
                                <button class="btn btn-sm btn-primary rounded-pill px-3">Save</button>
                            </form>
                        </td>
                        <td><?= e($refund['processed_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php page_footer(); ?>
