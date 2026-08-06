<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['Administrator']);
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int)post('venue_id');
        if ($id) {
            $stmt = $db->prepare('UPDATE venues SET name=?, address=?, city=?, capacity=?, is_active=? WHERE venue_id=?');
            $stmt->execute([post('name'), post('address'), post('city'), (int)post('capacity'), isset($_POST['is_active']) ? 1 : 0, $id]);
            log_action(current_user()['user_id'], 'UPDATE_VENUE', 'venues', $id);
        } else {
            $stmt = $db->prepare('INSERT INTO venues(name, address, city, capacity) VALUES (?, ?, ?, ?)');
            $stmt->execute([post('name'), post('address'), post('city'), (int)post('capacity')]);
            log_action(current_user()['user_id'], 'CREATE_VENUE', 'venues', (int)$db->lastInsertId());
        }
        flash('success', 'Venue saved.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('/admin/venues.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare('DELETE FROM venues WHERE venue_id = ?')->execute([$id]);
    log_action(current_user()['user_id'], 'DELETE_VENUE', 'venues', $id);
    flash('success', 'Venue deleted.');
    redirect('/admin/venues.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM venues WHERE venue_id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$venues = $db->query('SELECT * FROM venues ORDER BY venue_id DESC')->fetchAll();
$title = 'Venues';
page_header($title);
?>
<h1 class="h3">Venue Management</h1>
<form method="post" class="row g-3 mb-4">
    <input type="hidden" name="venue_id" value="<?= (int)($edit['venue_id'] ?? 0) ?>">
    <div class="col-md-3"><input class="form-control" name="name" placeholder="Venue name" value="<?= e($edit['name'] ?? '') ?>" required></div>
    <div class="col-md-3"><input class="form-control" name="address" placeholder="Address" value="<?= e($edit['address'] ?? '') ?>" required></div>
    <div class="col-md-2"><input class="form-control" name="city" placeholder="City" value="<?= e($edit['city'] ?? '') ?>" required></div>
    <div class="col-md-2"><input class="form-control" type="number" name="capacity" placeholder="Capacity" value="<?= e((string)($edit['capacity'] ?? '')) ?>" min="1" required></div>
    <div class="col-md-1 form-check pt-2"><input class="form-check-input" type="checkbox" name="is_active" <?= checked(($edit['is_active'] ?? 1) == 1) ?>> Active</div>
    <div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div>
</form>
<table class="table table-striped">
    <thead><tr><th>Name</th><th>City</th><th>Capacity</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($venues as $venue): ?>
        <tr>
            <td><?= e($venue['name']) ?></td>
            <td><?= e($venue['city']) ?></td>
            <td><?= (int)$venue['capacity'] ?></td>
            <td><?= $venue['is_active'] ? 'Active' : 'Inactive' ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$venue['venue_id'] ?>">Edit</a> <a class="btn btn-sm btn-outline-danger" href="?delete=<?= (int)$venue['venue_id'] ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php page_footer(); ?>
