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
<h1 class="h3">User Management</h1>
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Active</th><th></th></tr></thead>
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
                    <td><button class="btn btn-sm btn-primary">Save</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php page_footer(); ?>
