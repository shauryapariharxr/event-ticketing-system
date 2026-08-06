<?php
require_once __DIR__ . '/../includes/functions.php';

$title = 'Setup Check';
$checks = [];
$message = null;
$error = null;

function add_check(array &$checks, string $name, bool $ok, string $detail): void
{
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
}

try {
    $db = get_db();
    add_check($checks, 'Database connection', true, 'Connected to ' . DB_NAME . ' on ' . DB_HOST . '.');

    $requiredTables = [
        'roles', 'users', 'venues', 'sections', 'seat_rows', 'seats', 'events',
        'ticket_types', 'bookings', 'booking_seats', 'payments', 'tickets',
        'refunds', 'qr_scans', 'audit_logs',
    ];
    $tableStmt = $db->query('SHOW TABLES');
    $existingTables = array_map('current', $tableStmt->fetchAll(PDO::FETCH_NUM));
    $missingTables = array_diff($requiredTables, $existingTables);
    add_check(
        $checks,
        'Required tables',
        count($missingTables) === 0,
        count($missingTables) === 0 ? 'All required tables exist.' : 'Missing: ' . implode(', ', $missingTables)
    );

    if (!$missingTables) {
        $userCount = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $eventCount = (int)$db->query('SELECT COUNT(*) FROM events')->fetchColumn();
        add_check($checks, 'Seed users', $userCount >= 4, $userCount . ' users found.');
        add_check($checks, 'Seed events', $eventCount >= 2, $eventCount . ' events found.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'reset_demo_passwords') {
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $emails = ['admin@example.com', 'organizer@example.com', 'customer@example.com', 'gate@example.com'];
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        foreach ($emails as $email) {
            $stmt->execute([$hash, $email]);
        }
        log_action(null, 'RESET_DEMO_PASSWORDS', 'users', null, 'Setup page reset demo passwords.');
        $message = 'Demo passwords were reset. Use password: password';
    }
} catch (Throwable $e) {
    add_check($checks, 'Database connection', false, $e->getMessage());
    $error = 'Fix database credentials in includes/config.php, then import schema.sql and seed.sql.';
}

page_header($title);
?>
<h1 class="h3">Setup Check</h1>
<p class="text-muted">Use this page after importing the SQL files to confirm the project is ready.</p>

<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<div class="table-responsive mb-4">
    <table class="table align-middle">
        <thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead>
        <tbody>
        <?php foreach ($checks as $check): ?>
            <tr>
                <td><?= e($check['name']) ?></td>
                <td><span class="badge <?= $check['ok'] ? 'text-bg-success' : 'text-bg-danger' ?>"><?= $check['ok'] ? 'OK' : 'Needs Fix' ?></span></td>
                <td><?= e($check['detail']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-form">
    <h2 class="h5">Demo Passwords</h2>
    <p class="mb-3">After running `seed.sql`, click this once to generate valid PHP password hashes for the demo accounts.</p>
    <form method="post">
        <input type="hidden" name="action" value="reset_demo_passwords">
        <button class="btn btn-primary">Reset Demo Passwords</button>
    </form>
</div>

<div class="mt-4">
    <h2 class="h5">Launch URLs</h2>
    <ul>
        <li>Setup: <code><?= e(BASE_URL) ?>/public/setup.php</code></li>
        <li>App: <code><?= e(BASE_URL) ?>/public/index.php</code></li>
        <li>Admin: <code><?= e(BASE_URL) ?>/admin/dashboard.php</code></li>
    </ul>
</div>
<?php page_footer(); ?>
