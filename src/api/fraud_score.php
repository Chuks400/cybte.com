<?php

require_once __DIR__ . '/../controllers/FraudController.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $transaction_id = $_POST['transaction_id'];
    $amount = $_POST['amount'];
    $location = $_POST['location'];
    $device = $_POST['device'];

    $fraud = new FraudController();

    $result = $fraud->analyzeTransaction(
        $transaction_id,
        $amount,
        $location,
        $device
    );

    echo "Risk Score: " . $result["risk_score"] . "<br>";
    echo "Status: " . $result["status"];

}