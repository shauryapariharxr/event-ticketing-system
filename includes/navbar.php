<?php
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AeroTickets — Book events, shows, and concerts with real-time seat selection.">
    <title><?= e($title ?? 'AeroTickets') ?> — AeroTickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- ── NAVBAR ─────────────────────────────────────────── -->
<nav class="navbar-glass" id="mainNavbar">
    <div class="container navbar-shell">

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-1 text-decoration-none flex-shrink-0"
           href="<?= BASE_URL ?>/public/index.php">
            <i class="bi bi-ticket-perforated-fill fs-4 navbar-brand-icon"></i>
            <span class="navbar-brand"><span class="brand-aero">AERO</span><span class="brand-tickets">TICKETS</span></span>
        </a>

        <!-- City Picker -->
        <div class="dropdown d-none d-sm-block flex-shrink-0">
            <button class="city-picker-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="Choose a city">
                <i class="bi bi-geo-alt-fill city-picker-icon"></i>
                <span class="city-name">Pune</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-dark">
                <li><a class="dropdown-item active" href="<?= BASE_URL ?>/public/events.php?q=Pune"><i class="bi bi-check2 me-1"></i>Pune</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/events.php?q=Mumbai">Mumbai</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/events.php?q=Delhi">Delhi</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/events.php?q=Bangalore">Bangalore</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/events.php?q=Hyderabad">Hyderabad</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/events.php?q=Chennai">Chennai</a></li>
            </ul>
        </div>

        <!-- Search bar (desktop) -->
        <div class="navbar-search d-none d-lg-block">
            <form action="<?= BASE_URL ?>/public/events.php" method="get">
                <i class="bi bi-search search-icon"></i>
                <input type="search" name="q" placeholder="Search for movies, events, shows, sports…"
                       value="<?= e($_GET['q'] ?? '') ?>">
            </form>
        </div>

        <!-- Nav Links (desktop) -->
        <ul class="list-unstyled d-none d-lg-flex align-items-center gap-1 mb-0 ms-auto">
            <li>
                <a class="nav-link <?= ($title === 'Home') ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/public/index.php">Home</a>
            </li>
            <li>
                <a class="nav-link <?= ($title === 'Events') ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/public/events.php">Events</a>
            </li>
            <?php if ($user && in_array($user['role_name'], ['Administrator','Organizer'], true)): ?>
            <li>
                <a class="nav-link nav-link-admin" href="<?= BASE_URL ?>/admin/dashboard.php">
                    <i class="bi bi-speedometer2 me-1"></i>Admin
                </a>
            </li>
            <?php endif; ?>
            <?php if ($user && in_array($user['role_name'], ['Administrator','Gate Staff'], true)): ?>
            <li>
                <a class="nav-link nav-link-validate" href="<?= BASE_URL ?>/public/validate_ticket.php">
                    <i class="bi bi-qr-code-scan me-1"></i>Validate
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Auth buttons -->
        <div class="d-none d-lg-flex align-items-center gap-2 ms-1 flex-shrink-0">
            <?php if ($user): ?>
                <a class="nav-link d-flex align-items-center gap-2 py-1 px-2 rounded-pill user-pill"
                   href="<?= BASE_URL ?>/public/profile.php">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['name'],0,1)) ?>
                    </div>
                    <span class="user-name">
                        <?= e($user['name']) ?>
                    </span>
                </a>
                <a class="btn btn-outline-danger btn-sm rounded-pill px-3"
                   href="<?= BASE_URL ?>/public/logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            <?php else: ?>
                <a class="nav-link nav-link-signin" href="<?= BASE_URL ?>/public/login.php">Sign In</a>
                <a class="btn btn-primary btn-sm rounded-pill px-4"
                   href="<?= BASE_URL ?>/public/register.php">Register</a>
            <?php endif; ?>
        </div>

        <!-- Mobile toggle -->
        <button class="d-lg-none ms-auto mobile-nav-toggle"
                type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav"
                aria-label="Toggle navigation menu">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <!-- Mobile nav -->
    <div class="collapse" id="mobileNav">
        <div class="container pb-3">
            <!-- Mobile search -->
            <form action="<?= BASE_URL ?>/public/events.php" method="get" class="mb-3">
                <div class="navbar-search mobile-search">
                    <i class="bi bi-search search-icon"></i>
                    <input type="search" name="q" placeholder="Search events, shows…"
                           value="<?= e($_GET['q'] ?? '') ?>">
                </div>
            </form>
            <div class="d-flex flex-column gap-1">
                <a class="nav-link <?= ($title==='Home')?'active':'' ?>"
                   href="<?= BASE_URL ?>/public/index.php"><i class="bi bi-house me-2"></i>Home</a>
                <a class="nav-link <?= ($title==='Events')?'active':'' ?>"
                   href="<?= BASE_URL ?>/public/events.php"><i class="bi bi-calendar-event me-2"></i>Events</a>
                <?php if ($user && in_array($user['role_name'],['Administrator','Organizer'],true)): ?>
                <a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin</a>
                <?php endif; ?>
                <?php if ($user): ?>
                <a class="nav-link" href="<?= BASE_URL ?>/public/profile.php"><i class="bi bi-person me-2"></i>Profile</a>
                <a class="nav-link text-danger" href="<?= BASE_URL ?>/public/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                <?php else: ?>
                <a class="nav-link" href="<?= BASE_URL ?>/public/login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</a>
                <a class="nav-link" href="<?= BASE_URL ?>/public/register.php"><i class="bi bi-person-plus me-2"></i>Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Floating Toasts -->
<div class="floating-alerts">
    <?php if ($msg = flash('success')): ?>
        <div class="toast-alert alert-success">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <span class="small"><?= e($msg) ?></span>
            </div>
            <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.remove()"></button>
        </div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
        <div class="toast-alert alert-danger">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <span class="small"><?= e($msg) ?></span>
            </div>
            <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.remove()"></button>
        </div>
    <?php endif; ?>
</div>

<main class="container py-4">
