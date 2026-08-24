<?php
require_once __DIR__ . '/../includes/functions.php';
$title = 'Home';

// Upcoming events for hero cards
$events = get_db()->query(
    "SELECT e.*, v.name AS venue_name, v.city, va.available_seats
     FROM events e
     JOIN venues v ON v.venue_id = e.venue_id
     LEFT JOIN vw_available_seats va ON va.event_id = e.event_id
     WHERE e.status = 'Published' AND e.event_date >= NOW()
     ORDER BY e.event_date LIMIT 12"
)->fetchAll();

page_header($title);
?>

<!-- ══════════════════════════════════════════
     HERO BANNER
══════════════════════════════════════════ -->
<div style="margin: 0 calc(-50vw + 50%); margin-top: -1.5rem;">
<div class="hero-banner">
    <div class="hero-bg-image"></div>
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>

    <div class="container hero-content">
        <div class="row align-items-center g-4">
            <div class="col-lg-6 fade-up">
                <div class="hero-eyebrow">
                    <i class="bi bi-fire"></i>
                    Live DBMS Seat Simulation
                </div>
                <h1 class="hero-title">Book Your Next<br>Experience<br><span style="background:linear-gradient(135deg,#f84464,#ff6b35);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Seamlessly.</span></h1>
                <p class="hero-subtitle">
                    Real-time event seating, simulated payment transactions, and instant digital ticket stubs — all in one premium platform.
                </p>
                <div class="hero-cta-group">
                    <a href="events.php" class="btn btn-primary rounded-pill px-4 py-2">
                        <i class="bi bi-compass me-2"></i>Explore Events
                    </a>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-light rounded-pill px-4 py-2">
                        <i class="bi bi-shield-lock me-2"></i>Admin Panel
                    </a>
                </div>
            </div>
            <div class="col-lg-6 fade-up fade-up-1">
                <div class="hero-stats-grid">
                    <div class="stat-card">
                        <div class="stat-num">15</div>
                        <div class="stat-label">DB Tables</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-num">20+</div>
                        <div class="stat-label">Stored Queries</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-num">4</div>
                        <div class="stat-label">Access Roles</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- ══════════════════════════════════════════
     CATEGORY STRIP
══════════════════════════════════════════ -->
<div class="category-strip mt-4">
    <a href="events.php" class="category-pill active">
        <i class="bi bi-grid-fill pill-icon"></i> All Events
    </a>
    <a href="events.php?q=concert" class="category-pill">
        <i class="bi bi-music-note-beamed pill-icon"></i> Concerts
    </a>
    <a href="events.php?q=comedy" class="category-pill">
        <i class="bi bi-emoji-laughing pill-icon"></i> Comedy
    </a>
    <a href="events.php?q=theatre" class="category-pill">
        <i class="bi bi-masks-theater pill-icon"></i> Theatre
    </a>
    <a href="events.php?q=sports" class="category-pill">
        <i class="bi bi-trophy pill-icon"></i> Sports
    </a>
    <a href="events.php?q=festival" class="category-pill">
        <i class="bi bi-stars pill-icon"></i> Festivals
    </a>
    <a href="events.php?q=Pune" class="category-pill">
        <i class="bi bi-geo-alt pill-icon"></i> Pune
    </a>
    <a href="events.php?q=Mumbai" class="category-pill">
        <i class="bi bi-geo-alt pill-icon"></i> Mumbai
    </a>
    <a href="events.php?q=Delhi" class="category-pill">
        <i class="bi bi-geo-alt pill-icon"></i> Delhi
    </a>
</div>

<!-- ══════════════════════════════════════════
     UPCOMING EVENTS — HORIZONTAL SCROLL ROW
══════════════════════════════════════════ -->
<?php if ($events): ?>
<div class="section-header">
    <div class="section-title">
        <span class="section-title-bar"></span>
        Upcoming Events
    </div>
    <a href="events.php" class="section-see-all">
        See All <i class="bi bi-chevron-right"></i>
    </a>
</div>

<div class="scroll-row mb-5">
    <?php foreach (array_slice($events, 0, 8) as $event): ?>
    <div class="event-card-wrap">
        <a href="booking.php?event_id=<?= (int)$event['event_id'] ?>" class="text-decoration-none">
        <div class="event-card">
            <div class="event-poster-container">
                <?php if (!empty($event['poster_image'])): ?>
                    <img class="event-poster" src="<?= e($event['poster_image']) ?>"
                         alt="<?= e($event['title']) ?>"
                         onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&q=80&w=400';">
                <?php else: ?>
                    <img class="event-poster"
                         src="https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&q=80&w=400"
                         alt="Event poster">
                <?php endif; ?>
                <div class="event-card-gradient">
                    <span class="event-badge">
                        <i class="bi bi-calendar-event"></i>
                        <?= e(date('d M', strtotime($event['event_date']))) ?>
                    </span>
                </div>
            </div>
            <div class="event-card-body">
                <div class="event-card-title"><?= e($event['title']) ?></div>
                <div class="event-card-meta">
                    <i class="bi bi-geo-alt"></i>
                    <?= e($event['venue_name']) ?>, <?= e($event['city']) ?>
                </div>
                <div class="event-card-footer">
                    <span class="seats-badge">
                        <i class="bi bi-people-fill"></i>
                        <?= (int)($event['available_seats'] ?? 0) ?>
                    </span>
                    <span style="color:#f84464;font-size:0.78rem;font-weight:700;">Book →</span>
                </div>
            </div>
        </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════
     PROMO BANNER
══════════════════════════════════════════ -->
<div class="promo-banner mb-2">
    <div class="promo-banner-text">
        <h3>🎟️ Endless Entertainment, Anytime.</h3>
        <p>Discover live events happening near you — from concerts and theatre to comedy and sports.</p>
    </div>
    <div class="promo-banner-action">
        <a href="events.php" class="btn btn-primary rounded-pill px-4">
            Browse Events <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════
     FEATURED / ALL EVENTS GRID
══════════════════════════════════════════ -->
<?php if (count($events) > 8): ?>
<div class="section-header mt-5">
    <div class="section-title">
        <span class="section-title-bar"></span>
        Featured Events
    </div>
    <a href="events.php" class="section-see-all">
        See All <i class="bi bi-chevron-right"></i>
    </a>
</div>
<div class="row g-4 mb-5">
    <?php foreach (array_slice($events, 8) as $i => $event): ?>
    <div class="col-md-6 col-lg-4 fade-up" style="animation-delay:<?= $i * 0.07 ?>s">
        <div class="event-card h-100">
            <div class="event-poster-container">
                <?php if (!empty($event['poster_image'])): ?>
                    <img class="event-poster" src="<?= e($event['poster_image']) ?>"
                         alt="<?= e($event['title']) ?>"
                         onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&q=80&w=600';">
                <?php else: ?>
                    <img class="event-poster"
                         src="https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?auto=format&fit=crop&q=80&w=600"
                         alt="placeholder">
                <?php endif; ?>
                <div class="event-card-gradient">
                    <span class="event-badge">
                        <i class="bi bi-calendar-event"></i>
                        <?= e(date('d M Y', strtotime($event['event_date']))) ?>
                    </span>
                </div>
            </div>
            <div class="event-card-body">
                <div class="event-card-title"><?= e($event['title']) ?></div>
                <div class="event-card-meta">
                    <i class="bi bi-geo-alt"></i>
                    <?= e($event['venue_name']) ?>, <?= e($event['city']) ?>
                </div>
                <div class="event-card-meta">
                    <i class="bi bi-clock"></i>
                    <?= e(date('h:i A', strtotime($event['event_date']))) ?>
                </div>
                <div class="event-card-footer">
                    <span class="seats-badge">
                        <i class="bi bi-people-fill"></i>
                        <?= (int)($event['available_seats'] ?? 0) ?> seats
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
<?php endif; ?>

<!-- ══════════════════════════════════════════
     EMPTY STATE
══════════════════════════════════════════ -->
<?php if (!$events): ?>
<div class="glass-card empty-state">
    <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
    <h3>No Upcoming Events</h3>
    <p>Check back soon or ask an admin to publish events.</p>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-plus-circle me-2"></i>Add Events
    </a>
</div>
<?php endif; ?>

<?php page_footer(); ?>
