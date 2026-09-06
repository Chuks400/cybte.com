<?php

declare(strict_types=1);

require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../controllers/ScanController.php';

security_start_session();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Authentication required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request token.');
}

if (!security_rate_limit('cyber_posture_api', 10, 900)) {
    http_response_code(429);
    exit('Too many requests.');
}

try {
    $scanner = new ScanController();
    $result = $scanner->assessTarget((int)$_SESSION['user_id'], (string)($_POST['target_url'] ?? ''));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Cyber protection API error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Assessment unavailable.']);
}
