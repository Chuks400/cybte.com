<?php

require_once __DIR__ . '/../config/database.php';

class RiskScore {

    private $conn;
    private $table = "fraud_scores";

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create($transaction_id, $risk_score, $status){

        $query = "INSERT INTO " . $this->table . "
                  (transaction_id, risk_score, status)
                  VALUES (:transaction_id, :risk_score, :status)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":transaction_id", $transaction_id);
        $stmt->bindParam(":risk_score", $risk_score);
        $stmt->bindParam(":status", $status);

        return $stmt->execute();
    }

}<?php

require_once __DIR__ . '/../config/database.php';

class RiskScore {

    private $conn;
    private $table = "fraud_scores";

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create($transaction_id, $risk_score, $status){

        $query = "INSERT INTO " . $this->table . "
                  (transaction_id, risk_score, status)
                  VALUES (:transaction_id, :risk_score, :status)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":transaction_id", $transaction_id);
        $stmt->bindParam(":risk_score", $risk_score);
        $stmt->bindParam(":status", $status);

        return $stmt->execute();
    }

}