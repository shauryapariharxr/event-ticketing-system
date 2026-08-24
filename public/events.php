<?php
require_once __DIR__ . '/../includes/functions.php';
$title = 'Events';
$search = trim($_GET['q'] ?? '');

$stmt = get_db()->prepare(
    "SELECT e.*, v.name AS venue_name, v.city, va.available_seats
     FROM events e
     JOIN venues v ON v.venue_id = e.venue_id
     LEFT JOIN vw_available_seats va ON va.event_id = e.event_id
     WHERE e.status = 'Published' AND e.event_date >= NOW()
       AND (e.title LIKE ? OR v.city LIKE ? OR v.name LIKE ? OR e.description LIKE ?)
     ORDER BY e.event_date"
);
$like = '%' . $search . '%';
$stmt->execute([$like, $like, $like, $like]);
$events = $stmt->fetchAll();
page_header($title);
?>

<!-- ══════════════════════════════════════════
     SEARCH & FILTER BAR
══════════════════════════════════════════ -->
<div class="search-filter-bar">
    <div class="row align-items-center g-3">
        <div class="col-md-5">
            <div class="search-filter-title">
                <span class="section-title-bar"></span>
                Browse Events
                <?php if ($search): ?>
                    <span style="font-size:1rem;font-weight:500;color:var(--text-sub);">
                        — "<?= e($search) ?>"
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($events): ?>
            <p style="color:var(--text-muted);font-size:0.82rem;margin:0;">
                <i class="bi bi-ticket-perforated me-1"></i>
                <?= count($events) ?> event<?= count($events) !== 1 ? 's' : '' ?> found
            </p>
            <?php endif; ?>
        </div>
        <div class="col-md-7">
            <form class="d-flex gap-2" method="get">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input class="form-control" name="q"
                           value="<?= e($search) ?>"
                           placeholder="Search events, venues, cities…">
                    <?php if ($search): ?>
                        <a href="events.php" class="btn btn-outline-danger" title="Clear">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category & City pills -->
    <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
        <span style="color:var(--text-muted);font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
            <i class="bi bi-funnel me-1"></i>Filter:
        </span>
        <a href="events.php" class="filter-pill <?= empty($search) ? 'active' : '' ?>">All</a>
        <a href="events.php?q=concert"  class="filter-pill <?= strtolower($search)==='concert'  ? 'active':'' ?>">
            🎵 Concerts
        </a>
        <a href="events.php?q=comedy"   class="filter-pill <?= strtolower($search)==='comedy'   ? 'active':'' ?>">
            😂 Comedy
        </a>
        <a href="events.php?q=theatre"  class="filter-pill <?= strtolower($search)==='theatre'  ? 'active':'' ?>">
            🎭 Theatre
        </a>
        <a href="events.php?q=sports"   class="filter-pill <?= strtolower($search)==='sports'   ? 'active':'' ?>">
            🏆 Sports
        </a>
        <a href="events.php?q=festival" class="filter-pill <?= strtolower($search)==='festival' ? 'active':'' ?>">
            🎉 Festivals
        </a>
        <span class="d-none d-sm-inline" style="color:var(--border-hover);">|</span>
        <a href="events.php?q=Pune"      class="filter-pill <?= strtolower($search)==='pune'      ? 'active':'' ?>">
            📍 Pune
        </a>
        <a href="events.php?q=Mumbai"    class="filter-pill <?= strtolower($search)==='mumbai'    ? 'active':'' ?>">
            📍 Mumbai
        </a>
        <a href="events.php?q=Delhi"     class="filter-pill <?= strtolower($search)==='delhi'     ? 'active':'' ?>">
            📍 Delhi
        </a>
        <a href="events.php?q=Bangalore" class="filter-pill <?= strtolower($search)==='bangalore' ? 'active':'' ?>">
            📍 Bangalore
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════
     EVENTS GRID
══════════════════════════════════════════ -->
<?php if ($events): ?>
<div class="row g-4">
    <?php foreach ($events as $i => $event): ?>
    <div class="col-sm-6 col-lg-4 col-xl-3 fade-up" style="animation-delay:<?= min($i * 0.06, 0.4) ?>s; opacity:0;">
        <div class="event-card h-100">
            <div class="event-poster-container">
                <?php if (!empty($event['poster_image'])): ?>
                    <img class="event-poster"
                         src="<?= e($event['poster_image']) ?>"
                         alt="<?= e($event['title']) ?>"
                         onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&q=80&w=600';">
                <?php else: ?>
                    <img class="event-poster"
                         src="https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&q=80&w=600"
                         alt="event placeholder">
                <?php endif; ?>
                <div class="event-card-gradient">
                    <span class="event-badge">
                        <i class="bi bi-calendar-event"></i>
                        <?= e(date('d M Y', strtotime($event['event_date']))) ?>
                    </span>
                </div>
            </div>
            <div class="event-card-body">
                <h2 class="event-card-title"><?= e($event['title']) ?></h2>
                <div class="event-card-meta">
                    <i class="bi bi-geo-alt"></i>
                    <span><?= e($event['venue_name']) ?>, <?= e($event['city']) ?></span>
                </div>
                <div class="event-card-meta">
                    <i class="bi bi-clock"></i>
                    <span><?= e(date('h:i A', strtotime($event['event_date']))) ?></span>
                </div>
                <?php if (!empty($event['description'])): ?>
                <p style="font-size:0.8rem;color:var(--text-muted);line-height:1.45;margin:0.25rem 0 0;flex:1;
                          display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= e($event['description']) ?>
                </p>
                <?php endif; ?>
                <div class="event-card-footer">
                    <span class="seats-badge">
                        <i class="bi bi-people-fill"></i>
                        <?= (int)($event['available_seats'] ?? 0) ?> left
                    </span>
                    <a class="btn btn-primary btn-sm rounded-pill px-3"
                       href="booking.php?event_id=<?= (int)$event['event_id'] ?>">
                        Book Now
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<!-- ══ EMPTY STATE ══ -->
<div class="glass-card empty-state">
    <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
    <h3>No Events Found</h3>
    <p>We couldn't find events matching "<?= e($search) ?>". Try a different keyword.</p>
    <a href="events.php" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-arrow-left me-2"></i>View All Events
    </a>
</div>
<?php endif; ?>

<?php page_footer(); ?>
