<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/Services/VaultService.php';

use Cybte\Services\VaultService;

require_login('login.php');

$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$documentId || $documentId < 1) {
    http_response_code(400);
    exit('Invalid document request.');
}

try {
    $db = new Database();
    $vault = new VaultService($db->connect());
    $document = $vault->download((int)$_SESSION['user_id'], (int)$documentId);

    $safeName = preg_replace('/[\r\n"\\\/]+/', '_', $document['name']);
    header('Content-Type: ' . $document['mime_type']);
    header('Content-Length: ' . strlen($document['content']));
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    echo $document['content'];
} catch (Throwable $e) {
    error_log('Vault download error: ' . $e->getMessage());
    http_response_code(404);
    exit('Document unavailable.');
}
