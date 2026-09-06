<?php

declare(strict_types=1);

require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../models/Identity.php';

security_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Authentication required');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid session token');
}

if (!security_rate_limit('identity_submit', 5, 3600)) {
    http_response_code(429);
    exit('Too many submissions');
}

$documentType = strtolower(trim((string)($_POST['document_type'] ?? '')));
$documentNumber = trim((string)($_POST['document_number'] ?? ''));
$allowedTypes = ['passport', 'national_id', 'drivers_license'];

if (!in_array($documentType, $allowedTypes, true) || mb_strlen($documentNumber) < 4 || mb_strlen($documentNumber) > 100) {
    http_response_code(422);
    exit('Invalid identity submission');
}

try {
    $identity = new Identity();
    if (!$identity->create((int)$_SESSION['user_id'], $documentType, $documentNumber)) {
        throw new RuntimeException('Identity case insert failed.');
    }
    security_clear_rate_limit('identity_submit');
    security_flash('identity_success', 'Identity case submitted successfully.');
    header('Location: ../../public/verify.php');
    exit();
} catch (Throwable $e) {
    error_log('Legacy identity API error: ' . $e->getMessage());
    security_flash('identity_error', 'Identity verification is not available right now.');
    header('Location: ../../public/verify.php');
    exit();
}
