<?php

require_once __DIR__ . '/../config/database.php';

class Identity {

    private $conn;
    private $table = "identity_verifications";

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create($user_id, $document_type, $document_number){

        $query = "INSERT INTO " . $this->table . "
                  (user_id, document_type, document_number, status)
                  VALUES (:user_id, :document_type, :document_number, 'pending')";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":document_type", $document_type);
        $stmt->bindParam(":document_number", $document_number);

        return $stmt->execute();
    }

}