<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAuthenticated(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        http_response_code(401);
        exit('Unauthorized');
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

function currentUser(): ?array
{
    return isAuthenticated() ? $_SESSION['user'] : null;
}
