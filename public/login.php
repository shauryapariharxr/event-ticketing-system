<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login_user(post('email'), post('password'))) {
        flash('success', 'Logged in successfully.');
        redirect('/public/index.php');
    }
    flash('error', 'Invalid email or password.');
}

$title = 'Login';
page_header($title);
?>
<div class="auth-box">
    <h1 class="h3 mb-3">Login</h1>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required>
        </div>
        <button class="btn btn-primary w-100">Login</button>
    </form>
    <p class="mt-3 small text-muted">Demo password for seeded users: <code>password</code></p>
</div>
<?php page_footer(); ?>
