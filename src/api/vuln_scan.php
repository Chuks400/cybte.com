<?php

require_once __DIR__ . '/../controllers/ScanController.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $url = $_POST['target_url'];

    $scanner = new ScanController();

    $result = $scanner->scanWebsite($url);

    if(isset($result["error"])){
        echo $result["error"];
        exit();
    }

    echo "<h3>Scan Result</h3>";

    foreach($result["vulnerabilities"] as $vuln){
        echo "<p>" . $vuln . "</p>";
    }

    echo "<strong>Severity: " . $result["severity"] . "</strong>";

}