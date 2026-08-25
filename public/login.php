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
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="bi bi-box-arrow-in-right me-2 text-danger"></i>Welcome Back</h1>
            <p>Login to book seats and access your event passes.</p>
        </div>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input class="form-control" type="email" name="email" required placeholder="name@example.com">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input class="form-control" type="password" name="password" required placeholder="••••••••">
                </div>
            </div>
            <button class="btn btn-primary w-100 rounded-pill py-2.5">Sign In</button>
        </form>

        <div class="auth-divider text-center">
            <p class="small text-muted mb-2">Demo accounts share password <code class="text-danger">password</code></p>
            <p class="small text-white-50 mb-0">Don't have an account? <a href="register.php" class="text-danger text-decoration-none fw-semibold">Register here</a></p>
        </div>
    </div>
</div>
<?php page_footer(); ?>
