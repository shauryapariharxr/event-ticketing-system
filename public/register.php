<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (strlen(post('password')) < 6) {
            throw new RuntimeException('Password must be at least 6 characters.');
        }
        register_customer(post('name'), post('email'), post('password'), post('phone'));
        log_action(null, 'REGISTER', 'users', null, post('email'));
        flash('success', 'Registration successful. Please login.');
        redirect('/public/login.php');
    } catch (Throwable $e) {
        flash('error', 'Registration failed: ' . $e->getMessage());
    }
}

$title = 'Register';
page_header($title);
?>
<div class="auth-box">
    <h1 class="h3 mb-3">Create Account</h1>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input class="form-control" name="phone">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" required minlength="6">
        </div>
        <button class="btn btn-primary w-100">Register</button>
    </form>
</div>
<?php page_footer(); ?>
