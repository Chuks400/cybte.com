<?php

require_once __DIR__ . '/../controllers/VerifyController.php';

session_start();

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $user_id = $_SESSION['user_id'];
    $document_type = $_POST['document_type'];
    $document_number = $_POST['document_number'];

    $controller = new VerifyController();
    $controller->submitVerification($user_id, $document_type, $document_number);

}