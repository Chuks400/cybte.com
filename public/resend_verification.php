<?php
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/utils/EmailVerification.php';
require_once __DIR__ . '/../src/models/User.php';

$email = $_GET['email'] ?? '';
$status = 'invalid_email';

if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        $database = new Database();
        $conn = $database->connect();

        $stmt = $conn->prepare("SELECT id, name, email_verified FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (!empty($user['email_verified'])) {
                $status = 'already_verified';
            } else {
                $emailVerify = new EmailVerification($conn);
                $name = $user['name'] ?: 'VPN User';
                $sent = $emailVerify->resendVerification($user['id'], $email, $name);
                $status = $sent ? 'resent' : 'resent_failed';
            }
        } else {
            $status = 'not_found';
        }
    } catch (Exception $e) {
        error_log('ResendVerification: ' . $e->getMessage());
        $status = 'error';
    }
}

header('Location: verification_pending.php?email=' . urlencode($email) . '&sent=' . ($status === 'resent' ? '1' : '0') . '&status=' . urlencode($status));
exit();
