<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../security.php';

class AuthController
{
    public function login(string $email, string $password, string $redirect = 'dashboard.php'): bool
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            return false;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, (string)$user['password'])) {
            // Keep timing closer between valid and invalid accounts.
            if (!$user) {
                password_verify($password, '$2y$12$7qHjP4YhXblTzwdl2m/ESeInhiMFpvz8k7bWqKZe0gcW09dAOJ0ti');
            }
            return false;
        }

        if (array_key_exists('email_verified', $user) && empty($user['email_verified'])) {
            header('Location: verification_pending.php?email=' . urlencode($email));
            exit();
        }

        security_start_session();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = (string)($user['name'] ?? '');
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['authenticated_at'] = time();
        security_clear_rate_limit('login');

        header('Location: ' . $redirect);
        exit();
    }
}
