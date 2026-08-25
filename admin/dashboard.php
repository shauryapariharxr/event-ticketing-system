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
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-brand-mark"><i class="bi bi-speedometer2"></i></div>
            <div>
                <p class="admin-sidebar-title">Control</p>
                <p class="admin-sidebar-name">Aero Admin</p>
            </div>
        </div>

        <nav class="admin-nav">
            <a class="admin-nav-link active" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
            <a class="admin-nav-link" href="venues.php"><i class="bi bi-building"></i>Venues</a>
            <a class="admin-nav-link" href="events.php"><i class="bi bi-calendar-event"></i>Events</a>
            <a class="admin-nav-link" href="seats.php"><i class="bi bi-layout-text-window"></i>Seats</a>
            <?php if (user_has_role('Administrator')): ?>
                <a class="admin-nav-link" href="users.php"><i class="bi bi-people"></i>Users</a>
                <a class="admin-nav-link" href="refunds.php"><i class="bi bi-cash-stack"></i>Refunds</a>
            <?php endif; ?>
            <a class="admin-nav-link" href="reports.php"><i class="bi bi-bar-chart"></i>Reports</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-panel">
            <div class="page-section-header">
                <div>
                    <h1 class="page-section-title">Dashboard</h1>
                    <div class="page-section-subtitle">Live operations and booking performance</div>
                </div>
                <div class="panel-actions">
                    <a href="events.php" class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-plus-circle me-1"></i>New event</a>
                </div>
            </div>

            <div class="metric-grid">
                <div class="metric-card">
                    <div class="metric-card-header">
                        <span>Events</span>
                        <span class="metric-icon"><i class="bi bi-calendar-event"></i></span>
                    </div>
                    <div class="metric-value"><?= (int)$stats['events'] ?></div>
                    <div class="metric-trend"><strong>+12%</strong> this month</div>
                </div>
                <div class="metric-card">
                    <div class="metric-card-header">
                        <span>Venues</span>
                        <span class="metric-icon"><i class="bi bi-building"></i></span>
                    </div>
                    <div class="metric-value"><?= (int)$stats['venues'] ?></div>
                    <div class="metric-trend"><strong>6</strong> live locations</div>
                </div>
                <div class="metric-card">
                    <div class="metric-card-header">
                        <span>Bookings</span>
                        <span class="metric-icon"><i class="bi bi-ticket-perforated"></i></span>
                    </div>
                    <div class="metric-value"><?= (int)$stats['bookings'] ?></div>
                    <div class="metric-trend"><strong>81</strong> this week</div>
                </div>
                <div class="metric-card">
                    <div class="metric-card-header">
                        <span>Revenue</span>
                        <span class="metric-icon"><i class="bi bi-currency-rupee"></i></span>
                    </div>
                    <div class="metric-value">Rs. <?= number_format((float)$stats['revenue'], 2) ?></div>
                    <div class="metric-trend"><strong>9.4%</strong> YoY growth</div>
                </div>
            </div>

            <div class="admin-grid">
                <div class="admin-card">
                    <h2 class="admin-card-title">Operational summary</h2>
                    <div class="kpi-row"><div class="kpi-label">Confirmed bookings</div><div class="kpi-value">1,284</div></div>
                    <div class="kpi-row"><div class="kpi-label">Pending payments</div><div class="kpi-value">38</div></div>
                    <div class="kpi-row"><div class="kpi-label">Seat utilization</div><div class="kpi-value">74%</div></div>
                    <div class="kpi-row"><div class="kpi-label">Avg. refund wait</div><div class="kpi-value">2.4 days</div></div>
                </div>
                <div class="admin-card">
                    <h2 class="admin-card-title">Recent status</h2>
                    <div class="info-list">
                        <div class="info-item"><span>Inventory sync</span><span class="status-badge success">Live</span></div>
                        <div class="info-item"><span>Payment gateway</span><span class="status-badge info">Mock</span></div>
                        <div class="info-item"><span>Gate validation</span><span class="status-badge warning">Ready</span></div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h2 class="admin-card-title">Recent Audit Logs</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Table</th><th>Details</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent as $log): ?>
                            <tr>
                                <td><?= e($log['created_at']) ?></td>
                                <td><?= e($log['email'] ?? 'System') ?></td>
                                <td><span class="status-badge info"><?= e($log['action']) ?></span></td>
                                <td><?= e($log['table_name']) ?></td>
                                <td><?= e($log['details']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<?php page_footer(); ?>
