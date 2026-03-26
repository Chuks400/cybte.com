<?php

require_once __DIR__ . '/../config/database.php';

class ScanResult {

    private $conn;
    private $table = "scan_results";

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create($target_url, $vulnerabilities, $severity){

        $query = "INSERT INTO " . $this->table . "
                 (target_url, vulnerabilities, severity)
                 VALUES (:target_url, :vulnerabilities, :severity)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":target_url", $target_url);
        $stmt->bindParam(":vulnerabilities", $vulnerabilities);
        $stmt->bindParam(":severity", $severity);

        return $stmt->execute();
    }

}