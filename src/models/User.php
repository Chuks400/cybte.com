<?php

require_once __DIR__ . '/../config/database.php';

class User {

    private $conn;
    private $table = "users";

    public $id;
    public $name;
    public $email;
    public $password;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function findByEmail($email){

        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":email", $email);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}