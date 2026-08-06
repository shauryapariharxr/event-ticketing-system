<?php
require_once __DIR__ . '/../includes/functions.php';
$title = 'Home';
$events = get_db()->query(
    "SELECT e.*, v.name AS venue_name, v.city, va.available_seats
     FROM events e
     JOIN venues v ON v.venue_id = e.venue_id
     LEFT JOIN vw_available_seats va ON va.event_id = e.event_id
     WHERE e.status = 'Published' AND e.event_date >= NOW()
     ORDER BY e.event_date LIMIT 6"
)->fetchAll();
page_header($title);
?>
<section class="py-3">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold">Book event seats with clear database rules.</h1>
            <p class="lead text-muted">A PHP and MySQL DBMS project with authentication, venues, seating, bookings, mock payments, tickets, reports, triggers, views, and transactions.</p>
            <a class="btn btn-primary" href="events.php">Browse Events</a>
        </div>
        <div class="col-lg-5">
            <div class="stats-panel">
                <div><strong>15</strong><span>Tables</span></div>
                <div><strong>20+</strong><span>Queries</span></div>
                <div><strong>4</strong><span>Roles</span></div>
            </div>
        </div>
    </div>
</section>
<h2 class="h4 mt-5 mb-3">Upcoming Events</h2>
<div class="row g-3">
    <?php foreach ($events as $event): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 event-card">
                <?php if (!empty($event['poster_image'])): ?>
                    <img class="event-poster" src="<?= e($event['poster_image']) ?>" alt="<?= e($event['title']) ?> poster">
                <?php endif; ?>
                <div class="card-body">
                    <h3 class="h5"><?= e($event['title']) ?></h3>
                    <p class="text-muted mb-1"><?= e($event['venue_name']) ?>, <?= e($event['city']) ?></p>
                    <p class="small"><?= e(date('d M Y, h:i A', strtotime($event['event_date']))) ?></p>
                    <p><?= e(substr($event['description'], 0, 110)) ?></p>
                    <span class="badge text-bg-success"><?= (int)($event['available_seats'] ?? 0) ?> seats available</span>
                </div>
                <div class="card-footer bg-white border-0">
                    <a class="btn btn-outline-primary w-100" href="booking.php?event_id=<?= (int)$event['event_id'] ?>">Select Seats</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php page_footer(); ?>
