<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['Administrator','Organizer']);
$db = get_db();

$revenue = $db->query('SELECT * FROM vw_event_revenue ORDER BY revenue DESC')->fetchAll();
$daily = $db->query('SELECT DATE(booked_at) AS booking_date, COUNT(*) AS total_bookings FROM bookings GROUP BY DATE(booked_at) ORDER BY booking_date DESC')->fetchAll();
$monthly = $db->query("SELECT DATE_FORMAT(booked_at, '%Y-%m') AS month, COUNT(*) AS total_bookings FROM bookings GROUP BY DATE_FORMAT(booked_at, '%Y-%m') ORDER BY month DESC")->fetchAll();
$available = $db->query('SELECT * FROM vw_available_seats ORDER BY title')->fetchAll();
$popular = $db->query("SELECT e.title, COUNT(bs.booking_seat_id) AS booked_seats FROM events e LEFT JOIN booking_seats bs ON bs.event_id=e.event_id AND bs.status='Booked' GROUP BY e.event_id ORDER BY booked_seats DESC")->fetchAll();
$refunds = $db->query("SELECT r.*, e.title FROM refunds r JOIN bookings b ON b.booking_id=r.booking_id JOIN events e ON e.event_id=b.event_id ORDER BY r.requested_at DESC")->fetchAll();

$title = 'Reports';
page_header($title);
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-brand-mark"><i class="bi bi-bar-chart"></i></div>
            <div>
                <p class="admin-sidebar-title">Insights</p>
                <p class="admin-sidebar-name">Analytics</p>
            </div>
        </div>

        <nav class="admin-nav">
            <a class="admin-nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
            <a class="admin-nav-link" href="venues.php"><i class="bi bi-building"></i>Venues</a>
            <a class="admin-nav-link" href="events.php"><i class="bi bi-calendar-event"></i>Events</a>
            <a class="admin-nav-link" href="seats.php"><i class="bi bi-layout-text-window"></i>Seats</a>
            <?php if (user_has_role('Administrator')): ?>
                <a class="admin-nav-link" href="users.php"><i class="bi bi-people"></i>Users</a>
                <a class="admin-nav-link" href="refunds.php"><i class="bi bi-cash-stack"></i>Refunds</a>
            <?php endif; ?>
            <a class="admin-nav-link active" href="reports.php"><i class="bi bi-bar-chart"></i>Reports</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-panel">
            <div class="page-section-header">
                <div>
                    <h1 class="page-section-title">Reports</h1>
                    <div class="page-section-subtitle">Operational, financial, and ticket availability insights</div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6"><div class="admin-card"><h2 class="admin-card-title">Revenue</h2><table class="admin-table"><tbody><?php foreach ($revenue as $r): ?><tr><td><?= e($r['title']) ?></td><td>Rs. <?= number_format((float)$r['revenue'], 2) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
                <div class="col-lg-6"><div class="admin-card"><h2 class="admin-card-title">Daily Bookings</h2><table class="admin-table"><tbody><?php foreach ($daily as $r): ?><tr><td><?= e($r['booking_date']) ?></td><td><?= (int)$r['total_bookings'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
                <div class="col-lg-6"><div class="admin-card"><h2 class="admin-card-title">Monthly Bookings</h2><table class="admin-table"><tbody><?php foreach ($monthly as $r): ?><tr><td><?= e($r['month']) ?></td><td><?= (int)$r['total_bookings'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
                <div class="col-lg-6"><div class="admin-card"><h2 class="admin-card-title">Available Seats</h2><table class="admin-table"><tbody><?php foreach ($available as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= (int)$r['available_seats'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
                <div class="col-lg-6"><div class="admin-card"><h2 class="admin-card-title">Popular Events</h2><table class="admin-table"><tbody><?php foreach ($popular as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= (int)$r['booked_seats'] ?> seats</td></tr><?php endforeach; ?></tbody></table></div></div>
                <div class="col-lg-6"><div class="admin-card"><h2 class="admin-card-title">Refunds</h2><table class="admin-table"><tbody><?php foreach ($refunds as $r): ?><tr><td><?= e($r['title']) ?></td><td>Rs. <?= number_format((float)$r['amount'], 2) ?></td><td><?= e($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
            </div>
        </div>
    </main>
</div>
<?php page_footer(); ?>
