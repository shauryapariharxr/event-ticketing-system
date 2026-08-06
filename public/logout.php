<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_user()) {
    log_action(current_user()['user_id'], 'LOGOUT', 'users', current_user()['user_id']);
}
session_destroy();
session_start();
flash('success', 'Logged out successfully.');
redirect('/public/login.php');
