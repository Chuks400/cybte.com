<?php

declare(strict_types=1);

require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../controllers/FraudController.php';

security_start_session();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode(['error' => 'Invalid or expired session token']);
    exit();
}

$transactionId = trim((string)($_POST['transaction_id'] ?? ''));
$amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
$location = (string)($_POST['location'] ?? 'known');
$device = (string)($_POST['device'] ?? 'trusted');

if ($transactionId === '' || $amount === false || $amount < 0 || !in_array($location, ['known', 'unknown'], true) || !in_array($device, ['trusted', 'new'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid assessment input']);
    exit();
}

try {
    $fraud = new FraudController();
    $result = $fraud->analyzeTransaction((int)$_SESSION['user_id'], $transactionId, (float)$amount, $location, $device);
    echo json_encode($result, JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Fraud API error: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Fraud assessment is temporarily unavailable']);
}
