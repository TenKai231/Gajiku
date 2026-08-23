<?php

declare(strict_types=1);

function ensureSessionStarted(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

ensureSessionStarted();

function isAuthenticated(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function currentUser(): ?array
{
    return isAuthenticated() ? $_SESSION['user'] : null;
}

function loginUser(array $user): void
{
    ensureSessionStarted();
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'       => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ''),
        'role'     => (string) ($user['role'] ?? ''),
    ];
}

function logoutUser(): void
{
    ensureSessionStarted();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function setFlashMessage(string $type, string $message): void
{
    ensureSessionStarted();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlashMessage(): ?array
{
    ensureSessionStarted();

    if (!isset($_SESSION['flash_message']) || !is_array($_SESSION['flash_message'])) {
        return null;
    }

    $flashMessage = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    return $flashMessage;
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        redirect('/login.php');
    }
}

function requireRole(string $role): void
{
    requireAuth();

    $userRole = $_SESSION['user']['role'] ?? null;
    if ($userRole !== $role) {
        http_response_code(403);
        exit('Forbidden');
    }
}
