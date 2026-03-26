<?php

require_once __DIR__ . '/../config/database.php';

class ApiKey {

    private $conn;
    private $table = "api_keys";

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function generate($user_id){

        $api_key = bin2hex(random_bytes(32));

        $query = "INSERT INTO " . $this->table . "
                  (user_id, api_key)
                  VALUES (:user_id, :api_key)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":api_key", $api_key);

        $stmt->execute();

        return $api_key;
    }

}