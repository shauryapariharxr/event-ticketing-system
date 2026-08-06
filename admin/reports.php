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
<h1 class="h3">Reports</h1>
<div class="row g-4">
    <div class="col-lg-6 report-block"><h2 class="h5">Revenue</h2><table class="table table-sm"><tbody><?php foreach ($revenue as $r): ?><tr><td><?= e($r['title']) ?></td><td>Rs. <?= number_format((float)$r['revenue'], 2) ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="col-lg-6 report-block"><h2 class="h5">Daily Bookings</h2><table class="table table-sm"><tbody><?php foreach ($daily as $r): ?><tr><td><?= e($r['booking_date']) ?></td><td><?= (int)$r['total_bookings'] ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="col-lg-6 report-block"><h2 class="h5">Monthly Bookings</h2><table class="table table-sm"><tbody><?php foreach ($monthly as $r): ?><tr><td><?= e($r['month']) ?></td><td><?= (int)$r['total_bookings'] ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="col-lg-6 report-block"><h2 class="h5">Available Seats</h2><table class="table table-sm"><tbody><?php foreach ($available as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= (int)$r['available_seats'] ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="col-lg-6 report-block"><h2 class="h5">Popular Events</h2><table class="table table-sm"><tbody><?php foreach ($popular as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= (int)$r['booked_seats'] ?> seats</td></tr><?php endforeach; ?></tbody></table></div>
    <div class="col-lg-6 report-block"><h2 class="h5">Refunds</h2><table class="table table-sm"><tbody><?php foreach ($refunds as $r): ?><tr><td><?= e($r['title']) ?></td><td>Rs. <?= number_format((float)$r['amount'], 2) ?></td><td><?= e($r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<?php page_footer(); ?>
