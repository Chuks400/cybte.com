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

    /**
     * Create a new user with hashed password
     * @param string $name User name
     * @param string $email User email
     * @param string $password Plain text password (will be hashed)
     * @return int|false User ID on success, false on failure
     */
    public function createUser($name, $email, $password){
        
        // Hash the password using bcrypt (default algorithm)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        if (!$hashedPassword) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . " (name, email, password, role, created_at) 
                  VALUES (:name, :email, :password, 'user', NOW())";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashedPassword);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Update user password with hashing
     * @param int $userId User ID
     * @param string $newPassword Plain text new password
     * @return bool Success status
     */
    public function updatePassword($userId, $newPassword){
        
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        if (!$hashedPassword) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET password = :password WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":id", $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

}