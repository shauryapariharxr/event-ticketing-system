<?php
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Event Ticketing System') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= BASE_URL ?>/public/index.php">Event Ticketing</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/public/events.php">Events</a></li>
                <?php if ($user && in_array($user['role_name'], ['Administrator','Organizer'], true)): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a></li>
                <?php endif; ?>
                <?php if ($user && in_array($user['role_name'], ['Administrator','Gate Staff'], true)): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/public/validate_ticket.php">Validate Ticket</a></li>
                <?php endif; ?>
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/public/profile.php">Profile</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user): ?>
                    <li class="nav-item"><span class="navbar-text me-3"><?= e($user['name']) ?> (<?= e($user['role_name']) ?>)</span></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>/public/logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/public/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-warning btn-sm" href="<?= BASE_URL ?>/public/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>
