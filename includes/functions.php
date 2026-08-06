<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function user_has_role(string $role): bool
{
    $user = current_user();
    return $user && $user['role_name'] === $role;
}

function log_action(?int $userId, string $action, ?string $table = null, ?int $recordId = null, ?string $details = null): void
{
    $stmt = get_db()->prepare('INSERT INTO audit_logs(user_id, action, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $table, $recordId, $details]);
}

function post(string $key, string $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

function all_roles(): array
{
    return get_db()->query('SELECT * FROM roles ORDER BY role_id')->fetchAll();
}

function page_header(string $title): void
{
    require __DIR__ . '/navbar.php';
}

function page_footer(): void
{
    require __DIR__ . '/footer.php';
}

function selected($left, $right): string
{
    return (string)$left === (string)$right ? 'selected' : '';
}

function checked(bool $value): string
{
    return $value ? 'checked' : '';
}
