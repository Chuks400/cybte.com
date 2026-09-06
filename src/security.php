<?php

declare(strict_types=1);

function security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function security_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function csrf_token(): string
{
    security_start_session();

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(?string $token): bool
{
    security_start_session();
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function security_rate_limit(string $bucket, int $limit, int $windowSeconds): bool
{
    security_start_session();
    $now = time();
    $key = 'rate_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $bucket);
    $events = $_SESSION[$key] ?? [];

    if (!is_array($events)) {
        $events = [];
    }

    $events = array_values(array_filter($events, static fn($time) => is_int($time) && ($now - $time) < $windowSeconds));

    if (count($events) >= $limit) {
        $_SESSION[$key] = $events;
        return false;
    }

    $events[] = $now;
    $_SESSION[$key] = $events;
    return true;
}

function security_clear_rate_limit(string $bucket): void
{
    security_start_session();
    $key = 'rate_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $bucket);
    unset($_SESSION[$key]);
}

function security_flash(string $key, ?string $value = null): ?string
{
    security_start_session();

    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    $result = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return is_string($result) ? $result : null;
}

security_headers();
