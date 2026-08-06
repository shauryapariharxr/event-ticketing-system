<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['Administrator','Organizer']);

$db = get_db();
$stats = [
    'events' => $db->query('SELECT COUNT(*) FROM events')->fetchColumn(),
    'venues' => $db->query('SELECT COUNT(*) FROM venues')->fetchColumn(),
    'bookings' => $db->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
    'revenue' => $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status IN ('Success','Refunded')")->fetchColumn(),
];
$recent = $db->query(
    'SELECT a.*, u.email FROM audit_logs a LEFT JOIN users u ON u.user_id = a.user_id ORDER BY a.created_at DESC LIMIT 10'
)->fetchAll();

$title = 'Admin Dashboard';
page_header($title);
?>
<h1 class="h3 mb-3">Dashboard</h1>
<div class="admin-links mb-4">
    <a href="venues.php">Venues</a>
    <a href="events.php">Events</a>
    <a href="seats.php">Seats</a>
    <?php if (user_has_role('Administrator')): ?>
        <a href="users.php">Users</a>
        <a href="refunds.php">Refunds</a>
    <?php endif; ?>
    <a href="reports.php">Reports</a>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><span>Events</span><strong><?= (int)$stats['events'] ?></strong></div></div>
    <div class="col-md-3"><div class="stat-card"><span>Venues</span><strong><?= (int)$stats['venues'] ?></strong></div></div>
    <div class="col-md-3"><div class="stat-card"><span>Bookings</span><strong><?= (int)$stats['bookings'] ?></strong></div></div>
    <div class="col-md-3"><div class="stat-card"><span>Revenue</span><strong>Rs. <?= number_format((float)$stats['revenue'], 2) ?></strong></div></div>
</div>
<h2 class="h5">Recent Audit Logs</h2>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Table</th><th>Details</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $log): ?>
            <tr>
                <td><?= e($log['created_at']) ?></td>
                <td><?= e($log['email'] ?? 'System') ?></td>
                <td><?= e($log['action']) ?></td>
                <td><?= e($log['table_name']) ?></td>
                <td><?= e($log['details']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php page_footer(); ?>
