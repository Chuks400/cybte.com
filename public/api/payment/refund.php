<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/auth.php';

require_role(['admin'], '../../../login.php');

// IMPORTANT: A refund must be confirmed by the payment provider before local
// payment/subscription state changes. The current provider interface does not
// implement refunds, so this endpoint deliberately fails closed rather than
// pretending money was returned by only updating the database.
http_response_code(501);
echo json_encode([
    'success' => false,
    'error' => 'Provider refunds are not enabled. Process the refund through the payment provider and reconcile the transaction after provider confirmation.'
]);
