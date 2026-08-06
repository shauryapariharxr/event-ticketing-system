<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['Administrator','Organizer']);
$db = get_db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int)post('event_id');
        $organizerId = user_has_role('Organizer') ? $user['user_id'] : (int)post('organizer_id');
        if ($id) {
            $sql = 'UPDATE events SET organizer_id=?, venue_id=?, title=?, description=?, event_date=?, status=?, poster_image=? WHERE event_id=?';
            $params = [$organizerId, (int)post('venue_id'), post('title'), post('description'), post('event_date'), post('status'), post('poster_image'), $id];
            if (user_has_role('Organizer')) {
                $sql .= ' AND organizer_id=?';
                $params[] = $user['user_id'];
            }
            $db->prepare($sql)->execute($params);
            $eventId = $id;
            log_action($user['user_id'], 'UPDATE_EVENT', 'events', $eventId);
        } else {
            $stmt = $db->prepare('INSERT INTO events(organizer_id, venue_id, title, description, event_date, status, poster_image) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$organizerId, (int)post('venue_id'), post('title'), post('description'), post('event_date'), post('status'), post('poster_image')]);
            $eventId = (int)$db->lastInsertId();
            log_action($user['user_id'], 'CREATE_EVENT', 'events', $eventId);
        }

        if (isset($_POST['section_id'], $_POST['price'])) {
            foreach ($_POST['section_id'] as $i => $sectionId) {
                $price = (float)($_POST['price'][$i] ?? 0);
                if ($price > 0) {
                    $typeName = post('type_name_' . $sectionId, 'Standard');
                    $upsert = $db->prepare(
                        'INSERT INTO ticket_types(event_id, section_id, type_name, price) VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE price = VALUES(price)'
                    );
                    $upsert->execute([$eventId, (int)$sectionId, $typeName, $price]);
                }
            }
        }
        flash('success', 'Event saved.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/events.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = 'DELETE FROM events WHERE event_id=?';
    $params = [$id];
    if (user_has_role('Organizer')) {
        $sql .= ' AND organizer_id=?';
        $params[] = $user['user_id'];
    }
    $db->prepare($sql)->execute($params);
    log_action($user['user_id'], 'DELETE_EVENT', 'events', $id);
    flash('success', 'Event deleted.');
    redirect('/admin/events.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM events WHERE event_id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$venues = $db->query('SELECT * FROM venues WHERE is_active=1 ORDER BY name')->fetchAll();
$organizers = $db->query("SELECT u.* FROM users u JOIN roles r ON r.role_id=u.role_id WHERE r.role_name='Organizer' ORDER BY u.name")->fetchAll();
$sections = $db->query('SELECT sec.*, v.name AS venue_name FROM sections sec JOIN venues v ON v.venue_id=sec.venue_id ORDER BY v.name, sec.name')->fetchAll();
$where = user_has_role('Organizer') ? 'WHERE e.organizer_id=' . (int)$user['user_id'] : '';
$events = $db->query("SELECT e.*, v.name AS venue_name, u.name AS organizer FROM events e JOIN venues v ON v.venue_id=e.venue_id JOIN users u ON u.user_id=e.organizer_id $where ORDER BY e.event_date DESC")->fetchAll();

$title = 'Events Admin';
page_header($title);
?>
<h1 class="h3">Event Management</h1>
<form method="post" class="admin-form mb-4">
    <input type="hidden" name="event_id" value="<?= (int)($edit['event_id'] ?? 0) ?>">
    <div class="row g-3">
        <div class="col-md-4"><input class="form-control" name="title" placeholder="Event title" value="<?= e($edit['title'] ?? '') ?>" required></div>
        <div class="col-md-3">
            <select class="form-select" name="venue_id" required>
                <?php foreach ($venues as $venue): ?><option value="<?= (int)$venue['venue_id'] ?>" <?= selected($edit['venue_id'] ?? '', $venue['venue_id']) ?>><?= e($venue['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><input class="form-control" type="datetime-local" name="event_date" value="<?= $edit ? e(date('Y-m-d\TH:i', strtotime($edit['event_date']))) : '' ?>" required></div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <?php foreach (['Draft','Published','Cancelled','Completed'] as $status): ?><option <?= selected($edit['status'] ?? 'Draft', $status) ?>><?= $status ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php if (user_has_role('Administrator')): ?>
            <div class="col-md-4">
                <select class="form-select" name="organizer_id">
                    <?php foreach ($organizers as $org): ?><option value="<?= (int)$org['user_id'] ?>" <?= selected($edit['organizer_id'] ?? '', $org['user_id']) ?>><?= e($org['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-md-4"><input class="form-control" type="url" name="poster_image" placeholder="Poster image URL" value="<?= e($edit['poster_image'] ?? '') ?>"></div>
        <div class="col-md-8"><textarea class="form-control" name="description" placeholder="Description"><?= e($edit['description'] ?? '') ?></textarea></div>
    </div>
    <h2 class="h6 mt-3">Ticket Pricing By Section</h2>
    <div class="row g-2">
        <?php foreach ($sections as $section): ?>
            <div class="col-md-3">
                <input type="hidden" name="section_id[]" value="<?= (int)$section['section_id'] ?>">
                <label class="form-label small"><?= e($section['venue_name']) ?> / <?= e($section['name']) ?></label>
                <input class="form-control" type="number" step="0.01" min="0" name="price[]" placeholder="Price">
                <input type="hidden" name="type_name_<?= (int)$section['section_id'] ?>" value="Standard">
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary mt-3">Save Event</button>
</form>
<div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Title</th><th>Venue</th><th>Organizer</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody><?php foreach ($events as $event): ?><tr><td><?= e($event['title']) ?></td><td><?= e($event['venue_name']) ?></td><td><?= e($event['organizer']) ?></td><td><?= e($event['event_date']) ?></td><td><?= e($event['status']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$event['event_id'] ?>">Edit</a> <a class="btn btn-sm btn-outline-danger" href="?delete=<?= (int)$event['event_id'] ?>">Delete</a></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php page_footer(); ?>
