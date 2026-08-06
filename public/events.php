<?php
require_once __DIR__ . '/../includes/functions.php';
$title = 'Events';
$search = post('q', $_GET['q'] ?? '');
$stmt = get_db()->prepare(
    "SELECT e.*, v.name AS venue_name, v.city, va.available_seats
     FROM events e
     JOIN venues v ON v.venue_id = e.venue_id
     LEFT JOIN vw_available_seats va ON va.event_id = e.event_id
     WHERE e.status = 'Published' AND e.event_date >= NOW()
       AND (e.title LIKE ? OR v.city LIKE ? OR v.name LIKE ?)
     ORDER BY e.event_date"
);
$like = '%' . $search . '%';
$stmt->execute([$like, $like, $like]);
$events = $stmt->fetchAll();
page_header($title);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0">Events</h1>
    <form class="d-flex" method="get">
        <input class="form-control me-2" name="q" value="<?= e($search) ?>" placeholder="Search events">
        <button class="btn btn-outline-secondary">Search</button>
    </form>
</div>
<div class="row g-3">
    <?php foreach ($events as $event): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 event-card">
                <?php if (!empty($event['poster_image'])): ?>
                    <img class="event-poster" src="<?= e($event['poster_image']) ?>" alt="<?= e($event['title']) ?> poster">
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="h5"><?= e($event['title']) ?></h2>
                    <p class="text-muted mb-1"><?= e($event['venue_name']) ?>, <?= e($event['city']) ?></p>
                    <p class="small"><?= e(date('d M Y, h:i A', strtotime($event['event_date']))) ?></p>
                    <p><?= e($event['description']) ?></p>
                    <span class="badge text-bg-success"><?= (int)($event['available_seats'] ?? 0) ?> available</span>
                </div>
                <div class="card-footer bg-white">
                    <a class="btn btn-primary w-100" href="booking.php?event_id=<?= (int)$event['event_id'] ?>">Book Tickets</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$events): ?>
        <div class="col-12"><div class="alert alert-info">No events found.</div></div>
    <?php endif; ?>
</div>
<?php page_footer(); ?>
