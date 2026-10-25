<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['user'])
        && is_array($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();

    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function redirect_by_role(): void
{
    $role = $_SESSION['user']['role'] ?? '';

    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($role === 'owner') {
        header('Location: owner/dashboard.php');
    } elseif ($role === 'rent_seeker') {
        header('Location: index.php');
    } else {
        http_response_code(403);
        exit('Invalid account role.');
    }
    exit;
}
