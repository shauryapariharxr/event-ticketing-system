<?php
require_once __DIR__ . '/functions.php';

function login_user(string $email, string $password): bool
{
    $stmt = get_db()->prepare(
        'SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.email = ? AND u.is_active = 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'user_id' => (int)$user['user_id'],
        'role_id' => (int)$user['role_id'],
        'role_name' => $user['role_name'],
        'name' => $user['name'],
        'email' => $user['email'],
    ];
    log_action((int)$user['user_id'], 'LOGIN', 'users', (int)$user['user_id']);
    return true;
}

function register_customer(string $name, string $email, string $password, string $phone): bool
{
    $roleStmt = get_db()->prepare('SELECT role_id FROM roles WHERE role_name = ?');
    $roleStmt->execute(['Customer']);
    $roleId = (int)$roleStmt->fetchColumn();

    $stmt = get_db()->prepare('INSERT INTO users(role_id, name, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)');
    return $stmt->execute([$roleId, $name, $email, password_hash($password, PASSWORD_DEFAULT), $phone]);
}

function require_login(): void
{
    if (!current_user()) {
        flash('error', 'Please login to continue.');
        redirect('/public/login.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_user()['role_name'], $roles, true)) {
        flash('error', 'You do not have permission to access that page.');
        redirect('/public/index.php');
    }
}
