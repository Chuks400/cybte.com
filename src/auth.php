<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';

function require_login(string $redirect = 'login.php'): void
{
    security_start_session();

    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit();
    }
}

function require_role($roles, string $redirect = 'login.php'): void
{
    require_login($redirect);

    $userRole = $_SESSION['role'] ?? null;
    $roles = is_array($roles) ? $roles : [$roles];

    if (!$userRole || !in_array($userRole, $roles, true)) {
        http_response_code(403);
        header('Location: ' . $redirect);
        exit();
    }
}
