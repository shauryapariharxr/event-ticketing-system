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
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-header">
            <h1><i class="bi bi-person-plus me-2 text-danger"></i>Create Account</h1>
            <p>Join now to reserve premium seats for live experiences.</p>
        </div>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input class="form-control" name="name" required placeholder="John Doe">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input class="form-control" type="email" name="email" required placeholder="name@example.com">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input class="form-control" name="phone" placeholder="9000000000">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input class="form-control" type="password" name="password" required minlength="6" placeholder="••••••••">
                </div>
            </div>
            <button class="btn btn-primary w-100 rounded-pill py-2.5">Create Account</button>
        </form>

        <div class="auth-divider text-center">
            <p class="small text-white-50 mb-0">Already have an account? <a href="login.php" class="text-danger text-decoration-none fw-semibold">Login here</a></p>
        </div>
    </div>
</div>
<?php page_footer(); ?>
