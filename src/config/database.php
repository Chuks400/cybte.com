<?php
// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    require_once __DIR__ . '/../../.env';
}
class Database {

    private $host = "127.0.0.1";
    private $db_name = "cybte";
    private $username = "root";
    private $password = "Cjohn22@";
    private $port = 3308;

    private $lastError = null;

    public $conn;

    public function getLastError(){
        return $this->lastError;
    }

    public function connect(){

        $this->conn = null;
        $this->lastError = null;

        $envHost = getenv('DB_HOST');
        $envName = getenv('DB_NAME');
        $envUser = getenv('DB_USER');
        $envPass = getenv('DB_PASS');
        $envPort = getenv('DB_PORT');

        if($envHost){ $this->host = $envHost; }
        if($envName){ $this->db_name = $envName; }
        if($envUser){ $this->username = $envUser; }
        if($envPass !== false && $envPass !== null && $envPass !== ''){ $this->password = $envPass; }
        if($envPort){ $this->port = (int)$envPort; }

        $portsToTry = [$this->port];
        if($this->port === 3308){
            $portsToTry[] = 3306;
        }

        $hostsToTry = [$this->host];
        // Add localhost fallback for 127.0.0.1 TCP issues
        if($this->host === '127.0.0.1'){
            $hostsToTry[] = 'localhost';
        }

        foreach($hostsToTry as $host){
            foreach($portsToTry as $port){
                try{
                    $this->conn = new PDO(
                        "mysql:host=".$host.";port=".$port.";dbname=".$this->db_name.";charset=utf8mb4",
                        $this->username,
                        $this->password
                    );

                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $this->conn;
                }catch(PDOException $e){
                    $this->lastError = $e->getMessage();
                    $this->conn = null;
                }
            }
        }

        throw new RuntimeException('Database connection failed. Please contact support.');
    }
}
