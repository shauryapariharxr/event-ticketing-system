<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['Administrator']);
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (post('type') === 'section') {
            $stmt = $db->prepare('INSERT INTO sections(venue_id, name) VALUES (?, ?)');
            $stmt->execute([(int)post('venue_id'), post('name')]);
            log_action(current_user()['user_id'], 'CREATE_SECTION', 'sections', (int)$db->lastInsertId());
        } elseif (post('type') === 'row') {
            $stmt = $db->prepare('INSERT INTO seat_rows(section_id, row_label) VALUES (?, ?)');
            $stmt->execute([(int)post('section_id'), post('row_label')]);
            log_action(current_user()['user_id'], 'CREATE_ROW', 'seat_rows', (int)$db->lastInsertId());
        } else {
            $stmt = $db->prepare('INSERT INTO seats(row_id, seat_number) VALUES (?, ?)');
            $stmt->execute([(int)post('row_id'), post('seat_number')]);
            log_action(current_user()['user_id'], 'CREATE_SEAT', 'seats', (int)$db->lastInsertId());
        }
        flash('success', 'Seat structure saved.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/seats.php');
}

$venues = $db->query('SELECT * FROM venues ORDER BY name')->fetchAll();
$sections = $db->query('SELECT sec.*, v.name AS venue_name FROM sections sec JOIN venues v ON v.venue_id = sec.venue_id ORDER BY v.name, sec.name')->fetchAll();
$rows = $db->query('SELECT r.*, sec.name AS section_name, v.name AS venue_name FROM seat_rows r JOIN sections sec ON sec.section_id = r.section_id JOIN venues v ON v.venue_id = sec.venue_id ORDER BY v.name, sec.name, r.row_label')->fetchAll();
$seats = $db->query('SELECT s.*, r.row_label, sec.name AS section_name, v.name AS venue_name FROM seats s JOIN seat_rows r ON r.row_id=s.row_id JOIN sections sec ON sec.section_id=r.section_id JOIN venues v ON v.venue_id=sec.venue_id ORDER BY v.name, sec.name, r.row_label, CAST(s.seat_number AS UNSIGNED) LIMIT 200')->fetchAll();
$title = 'Seats';
page_header($title);
?>
<h1 class="h3">Seat Structure</h1>
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <form method="post" class="admin-form">
            <input type="hidden" name="type" value="section">
            <h2 class="h5">Create Section</h2>
            <select class="form-select mb-2" name="venue_id" required><?php foreach ($venues as $v): ?><option value="<?= (int)$v['venue_id'] ?>"><?= e($v['name']) ?></option><?php endforeach; ?></select>
            <input class="form-control mb-2" name="name" placeholder="Section name" required>
            <button class="btn btn-primary">Add Section</button>
        </form>
    </div>
    <div class="col-lg-4">
        <form method="post" class="admin-form">
            <input type="hidden" name="type" value="row">
            <h2 class="h5">Create Row</h2>
            <select class="form-select mb-2" name="section_id" required><?php foreach ($sections as $s): ?><option value="<?= (int)$s['section_id'] ?>"><?= e($s['venue_name']) ?> / <?= e($s['name']) ?></option><?php endforeach; ?></select>
            <input class="form-control mb-2" name="row_label" placeholder="Row label" required>
            <button class="btn btn-primary">Add Row</button>
        </form>
    </div>
    <div class="col-lg-4">
        <form method="post" class="admin-form">
            <input type="hidden" name="type" value="seat">
            <h2 class="h5">Create Seat</h2>
            <select class="form-select mb-2" name="row_id" required><?php foreach ($rows as $r): ?><option value="<?= (int)$r['row_id'] ?>"><?= e($r['venue_name']) ?> / <?= e($r['section_name']) ?> / <?= e($r['row_label']) ?></option><?php endforeach; ?></select>
            <input class="form-control mb-2" name="seat_number" placeholder="Seat number" required>
            <button class="btn btn-primary">Add Seat</button>
        </form>
    </div>
</div>
<h2 class="h5">Seats</h2>
<div class="table-responsive">
    <table class="table table-sm">
        <thead><tr><th>Venue</th><th>Section</th><th>Row</th><th>Seat</th><th>Status</th></tr></thead>
        <tbody><?php foreach ($seats as $seat): ?><tr><td><?= e($seat['venue_name']) ?></td><td><?= e($seat['section_name']) ?></td><td><?= e($seat['row_label']) ?></td><td><?= e($seat['seat_number']) ?></td><td><?= $seat['is_active'] ? 'Active' : 'Inactive' ?></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<?php page_footer(); ?>
