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

<div class="landing-page">
    <section class="landing-hero">
        <div class="landing-hero-copy">
            <span class="landing-kicker"><i class="bi bi-stars"></i> Your live event platform</span>
            <h1>Make every night<br><em>unforgettable.</em></h1>
            <p>Discover remarkable concerts, comedy, theatre, and sport, then book your perfect seat in seconds.</p>
            <div class="landing-actions">
                <a href="events.php" class="landing-button landing-button-primary">Explore events <i class="bi bi-arrow-up-right"></i></a>
                <a href="#how-it-works" class="landing-button landing-button-quiet">How it works <i class="bi bi-arrow-down"></i></a>
            </div>
            <div class="landing-proof"><span class="proof-dots"><i></i><i></i><i></i></span><strong>12k+</strong> tickets booked by people making plans.</div>
        </div>
        <div class="ticket-stack" aria-label="Featured event preview">
            <div class="ticket-card ticket-card-back"></div>
            <div class="ticket-card ticket-card-main">
                <div class="ticket-top"><span>AEROTICKETS</span><i class="bi bi-qr-code"></i></div>
                <div class="ticket-art"><i class="bi bi-music-note-beamed"></i><span>LIVE<br>TONIGHT</span></div>
                <p class="ticket-label">Featured experience</p><h2><?= $events ? e($events[0]['title']) : 'The city is calling' ?></h2>
                <div class="ticket-meta"><span><i class="bi bi-calendar3"></i> <?= $events ? e(date('D, d M', strtotime($events[0]['event_date']))) : 'Every weekend' ?></span><span><i class="bi bi-geo-alt"></i> <?= $events ? e($events[0]['city']) : 'Your city' ?></span></div>
                <a href="<?= $events ? 'booking.php?event_id=' . (int)$events[0]['event_id'] : 'events.php' ?>" class="ticket-link">Reserve a seat <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="floating-note"><i class="bi bi-check2-circle"></i><span><strong>Seat secured</strong><small>Just now</small></span></div>
        </div>
    </section>

    <section class="landing-stats">
        <div><strong>15+</strong><span>event categories</span></div><div><strong>40+</strong><span>venues across cities</span></div><div><strong>100%</strong><span>secure checkout</span></div><div><strong>24/7</strong><span>digital ticket access</span></div>
    </section>

    <section class="landing-section" id="how-it-works">
        <div class="section-intro"><span class="landing-kicker">The simple way out</span><h2>Plans are better<br>when they <em>feel easy.</em></h2><p>From the first spark of an idea to the moment the lights go down, Aerotickets keeps your night moving.</p></div>
        <div class="landing-features"><article><span>01</span><i class="bi bi-compass"></i><h3>Find your next thing</h3><p>Browse a living calendar of experiences curated for your city and your mood.</p><a href="events.php">Browse events <i class="bi bi-arrow-up-right"></i></a></article><article><span>02</span><i class="bi bi-grid-3x3-gap"></i><h3>Choose your view</h3><p>See the room, compare seats in real time, and pick the spot that feels right.</p><a href="events.php">See the seats <i class="bi bi-arrow-up-right"></i></a></article><article><span>03</span><i class="bi bi-ticket-perforated"></i><h3>Show up ready</h3><p>Your digital ticket is always close, easy to validate, and ready when you are.</p><a href="register.php">Create an account <i class="bi bi-arrow-up-right"></i></a></article></div>
    </section>

    <section class="landing-feature-band"><div class="feature-image"><img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&q=85&w=1000" alt="Crowd enjoying a live concert"></div><div class="feature-copy"><span class="landing-kicker">Made for the moment</span><h2>Less time searching.<br><em>More time living.</em></h2><p>One calm, thoughtful place to find the events worth leaving home for. No clutter. No guesswork. Just a better way to make plans.</p><div class="feature-list"><div><i class="bi bi-lightning-charge"></i><span><strong>Real-time availability</strong><small>Know what is open before you click.</small></span></div><div><i class="bi bi-shield-check"></i><span><strong>Simple, secure payments</strong><small>Checkout without the friction.</small></span></div></div></div></section>

    <section class="landing-events"><div class="section-heading"><div><span class="landing-kicker">On the calendar</span><h2>Make a date of it.</h2></div><a href="events.php" class="text-link">View all events <i class="bi bi-arrow-up-right"></i></a></div><?php if ($events): ?><div class="landing-event-grid"><?php foreach (array_slice($events, 0, 3) as $event): ?><a class="landing-event" href="booking.php?event_id=<?= (int)$event['event_id'] ?>"><div class="event-date"><strong><?= e(date('d', strtotime($event['event_date']))) ?></strong><span><?= e(date('M', strtotime($event['event_date']))) ?></span></div><div><h3><?= e($event['title']) ?></h3><p><i class="bi bi-geo-alt"></i> <?= e($event['venue_name']) ?>, <?= e($event['city']) ?></p></div><i class="bi bi-arrow-up-right event-arrow"></i></a><?php endforeach; ?></div><?php else: ?><div class="landing-empty">New experiences are on their way. Check back soon.</div><?php endif; ?></section>

    <section class="landing-quote"><i class="bi bi-quote"></i><blockquote>“The best nights are the ones you did not have to overthink.”</blockquote><p>Make room for something memorable.</p><a href="events.php" class="landing-button landing-button-primary">Find an experience <i class="bi bi-arrow-up-right"></i></a></section>
</div>

<?php page_footer(); ?>
