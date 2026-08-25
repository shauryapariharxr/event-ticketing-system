<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['Administrator']);
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('UPDATE users SET role_id=?, name=?, phone=?, is_active=? WHERE user_id=?');
    $stmt->execute([(int)post('role_id'), post('name'), post('phone'), isset($_POST['is_active']) ? 1 : 0, (int)post('user_id')]);
    log_action(current_user()['user_id'], 'UPDATE_USER', 'users', (int)post('user_id'));
    flash('success', 'User updated.');
    redirect('/admin/users.php');
}

$roles = all_roles();
$users = $db->query('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id=u.role_id ORDER BY u.user_id DESC')->fetchAll();
$title = 'Users';
page_header($title);
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <div class="admin-brand-mark"><i class="bi bi-people"></i></div>
            <div>
                <p class="admin-sidebar-title">Access</p>
                <p class="admin-sidebar-name">User Roles</p>
            </div>
        </div>

        <nav class="admin-nav">
            <a class="admin-nav-link" href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
            <a class="admin-nav-link" href="venues.php"><i class="bi bi-building"></i>Venues</a>
            <a class="admin-nav-link" href="events.php"><i class="bi bi-calendar-event"></i>Events</a>
            <a class="admin-nav-link" href="seats.php"><i class="bi bi-layout-text-window"></i>Seats</a>
            <a class="admin-nav-link active" href="users.php"><i class="bi bi-people"></i>Users</a>
            <a class="admin-nav-link" href="refunds.php"><i class="bi bi-cash-stack"></i>Refunds</a>
            <a class="admin-nav-link" href="reports.php"><i class="bi bi-bar-chart"></i>Reports</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-panel">
            <div class="page-section-header">
                <div>
                    <h1 class="page-section-title">User Management</h1>
                    <div class="page-section-subtitle">Update roles and account activity</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="admin-table align-middle">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Active</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <form method="post">
                                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                <td><input class="form-control form-control-sm" name="name" value="<?= e($u['name']) ?>"></td>
                                <td><?= e($u['email']) ?></td>
                                <td><select class="form-select form-select-sm" name="role_id"><?php foreach ($roles as $role): ?><option value="<?= (int)$role['role_id'] ?>" <?= selected($u['role_id'], $role['role_id']) ?>><?= e($role['role_name']) ?></option><?php endforeach; ?></select></td>
                                <td><input class="form-control form-control-sm" name="phone" value="<?= e($u['phone']) ?>"></td>
                                <td><input class="form-check-input" type="checkbox" name="is_active" <?= checked((bool)$u['is_active']) ?>></td>
                                <td><button class="btn btn-sm btn-primary rounded-pill px-3">Save</button></td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php page_footer(); ?>
