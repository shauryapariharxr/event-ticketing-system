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
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-brand-mark"><i class="bi bi-building"></i></div>
            <div>
                <p class="admin-sidebar-title">Setup</p>
                <p class="admin-sidebar-name">Venue Catalog</p>
            </div>
        </div>

        <nav class="admin-nav">
            <a class="admin-nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
            <a class="admin-nav-link active" href="venues.php"><i class="bi bi-building"></i>Venues</a>
            <a class="admin-nav-link" href="events.php"><i class="bi bi-calendar-event"></i>Events</a>
            <a class="admin-nav-link" href="seats.php"><i class="bi bi-layout-text-window"></i>Seats</a>
            <?php if (user_has_role('Administrator')): ?>
                <a class="admin-nav-link" href="users.php"><i class="bi bi-people"></i>Users</a>
                <a class="admin-nav-link" href="refunds.php"><i class="bi bi-cash-stack"></i>Refunds</a>
            <?php endif; ?>
            <a class="admin-nav-link" href="reports.php"><i class="bi bi-bar-chart"></i>Reports</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-panel">
            <div class="page-section-header">
                <div>
                    <h1 class="page-section-title">Venue Management</h1>
                    <div class="page-section-subtitle">Add, update, and maintain event locations</div>
                </div>
            </div>

            <form method="post" class="admin-form-box row g-3 mb-4">
                <input type="hidden" name="venue_id" value="<?= (int)($edit['venue_id'] ?? 0) ?>">
                <div class="col-md-3"><input class="form-control" name="name" placeholder="Venue name" value="<?= e($edit['name'] ?? '') ?>" required></div>
                <div class="col-md-3"><input class="form-control" name="address" placeholder="Address" value="<?= e($edit['address'] ?? '') ?>" required></div>
                <div class="col-md-2"><input class="form-control" name="city" placeholder="City" value="<?= e($edit['city'] ?? '') ?>" required></div>
                <div class="col-md-2"><input class="form-control" type="number" name="capacity" placeholder="Capacity" value="<?= e((string)($edit['capacity'] ?? '')) ?>" min="1" required></div>
                <div class="col-md-1 form-check pt-2"><input class="form-check-input" type="checkbox" name="is_active" <?= checked(($edit['is_active'] ?? 1) == 1) ?>> <span class="small text-white-50">Active</span></div>
                <div class="col-md-1"><button class="btn btn-primary w-100 rounded-pill">Save</button></div>
            </form>
            <table class="admin-table">
                <thead><tr><th>Name</th><th>City</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($venues as $venue): ?>
                    <tr>
                        <td><?= e($venue['name']) ?></td>
                        <td><?= e($venue['city']) ?></td>
                        <td><?= (int)$venue['capacity'] ?></td>
                        <td><span class="status-badge <?= $venue['is_active'] ? 'success' : 'warning' ?>"><?= $venue['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="?edit=<?= (int)$venue['venue_id'] ?>">Edit</a> <a class="btn btn-sm btn-outline-danger rounded-pill px-3" href="?delete=<?= (int)$venue['venue_id'] ?>">Delete</a></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<?php page_footer(); ?>
