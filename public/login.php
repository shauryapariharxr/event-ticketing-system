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
<div class="glass-card auth-box">
    <h1 class="h3 mb-3 fw-bold text-white text-center">
        <i class="bi bi-box-arrow-in-right text-danger me-2"></i>Welcome Back
    </h1>
    <p class="text-white-50 text-center small mb-4">Login to book seats and access tickets.</p>
    
    <form method="post">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="bi bi-envelope"></i></span>
                <input class="form-control" type="email" name="email" required placeholder="name@example.com">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="bi bi-key"></i></span>
                <input class="form-control" type="password" name="password" required placeholder="••••••••">
            </div>
        </div>
        <button class="btn btn-primary w-100 rounded-pill py-2.5">Sign In</button>
    </form>
    
    <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
        <p class="small text-muted mb-2">Demo accounts share password <code>password</code></p>
        <p class="small text-white-50 mb-0">Don't have an account? <a href="register.php" class="text-danger text-decoration-none fw-semibold">Register here</a></p>
    </div>
</div>
<?php page_footer(); ?>
