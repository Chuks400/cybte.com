<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/config/database.php';

security_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#contact');
    exit();
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    security_flash('contact_error', 'Your session expired. Please submit the form again.');
    header('Location: index.php#contact');
    exit();
}

if (!security_rate_limit('enterprise_contact', 4, 1800)) {
    security_flash('contact_error', 'Too many requests were submitted. Please wait before trying again.');
    header('Location: index.php#contact');
    exit();
}

$name = trim((string)($_POST['name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$company = trim((string)($_POST['company'] ?? ''));
$role = trim((string)($_POST['role'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$website = trim((string)($_POST['website'] ?? '')); // honeypot

if ($website !== '') {
    header('Location: index.php#contact');
    exit();
}

if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 20 || mb_strlen($message) > 5000) {
    security_flash('contact_error', 'Please provide a valid name, business email and a message of at least 20 characters.');
    header('Location: index.php#contact');
    exit();
}

try {
    $db = new Database();
    $conn = $db->connect();
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ipHash = $ip !== '' ? hash('sha256', $ip . '|' . (getenv('APP_KEY') ?: 'cybte')) : null;

    $stmt = $conn->prepare('INSERT INTO enterprise_contacts (name, email, company, role, message, source_ip_hash) VALUES (:name, :email, :company, :role, :message, :source_ip_hash)');
    $stmt->execute([
        ':name' => mb_substr($name, 0, 120),
        ':email' => mb_substr($email, 0, 190),
        ':company' => $company !== '' ? mb_substr($company, 0, 190) : null,
        ':role' => $role !== '' ? mb_substr($role, 0, 120) : null,
        ':message' => $message,
        ':source_ip_hash' => $ipHash,
    ]);

    security_clear_rate_limit('enterprise_contact');
    security_flash('contact_success', 'Thank you. Your request has been received by the Cybte AI team.');
} catch (Throwable $e) {
    error_log('Enterprise contact error: ' . $e->getMessage());
    security_flash('contact_error', 'We could not submit your request right now. Please contact security@cybte.com directly.');
}

header('Location: index.php#contact');
exit();
