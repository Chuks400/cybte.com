<?php
/**
 * Setup VPN Server - Web Version
 * Run via: http://localhost/trustshield-ai/public/setup_vpn_server.php
 */

require_once __DIR__ . '/../src/config/database.php';

header('Content-Type: text/plain');

echo "=== VPN Server Setup ===\n\n";

function getDbConfig(): array {
    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3308',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: 'Cjohn22@',
        'dbname' => getenv('DB_NAME') ?: 'cybte',
    ];
}

function connectToMysqlServer(array $config): PDO {
    $ports = [$config['port']];
    if ((int)$config['port'] === 3308) {
        $ports[] = 3306;
    }

    foreach ($ports as $port) {
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$port};charset=utf8mb4",
                $config['username'],
                $config['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
        }
    }

    throw new RuntimeException("Could not connect to MySQL server: {$lastError}");
}

function importSqlFile(PDO $pdo, string $sqlFile, string $dbName): void {
    if (!file_exists($sqlFile)) {
        throw new RuntimeException('SQL schema file not found: ' . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);
    $sql = preg_replace('/^\s*(CREATE DATABASE|USE) .*$/mi', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");

    foreach ($statements as $statement) {
        if ($statement === '' || preg_match('/^\s*(--|\/\*)/', $statement)) {
            continue;
        }
        $pdo->exec($statement);
    }
}

try {
    $database = new Database();
    $conn = $database->connect();
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    $lastError = $database->getLastError();
    if (stripos($lastError, 'Unknown database') !== false || stripos($e->getMessage(), 'Unknown database') !== false) {
        echo "Database missing, creating schema...\n";
        $dbConfig = getDbConfig();

        try {
            $mysql = connectToMysqlServer($dbConfig);
            importSqlFile($mysql, __DIR__ . '/../database/trustshield.sql', $dbConfig['dbname']);
            echo "✓ Database created and schema imported. Reconnecting...\n\n";
            $conn = $database->connect();
            echo "✓ Database connected\n\n";
        } catch (Exception $createException) {
            echo "✗ Failed to create database/schema: " . $createException->getMessage() . "\n";
            exit;
        }
    } else {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n";
        echo "Last error: " . $lastError . "\n";
        exit;
    }
}

// VPS Configuration
$username = getenv('USERNAME');
$serverConfig = [
    'name' => 'USA-1',
    'location' => 'New York',
    'country' => 'US',
    'flag' => '🇺🇸',
    'ip_address' => '178.104.139.94',
    'status' => 'active',
    'load_percent' => 45,
    'panel_type' => '3x-ui',
    'api_url' => 'http://178.104.139.94:54321',
    'api_username' => '',
    'api_password' => '',
    'inbound_id' => 1,
    'protocol' => 'vless',
    'domain' => null,
    'use_https' => false,
    'panel_port' => '54321',
    'web_base_path' => '/JE2fu7rGygZsRGQwEW/',
    'ssh_key_path' => "C:\\Users\\$username\\.ssh\\id_rsa"
];

try {
    // Ensure the vpn_servers table has the required 3x-ui columns
    $conn->exec("
        ALTER TABLE vpn_servers
            ADD COLUMN IF NOT EXISTS country VARCHAR(2) NULL AFTER location,
            ADD COLUMN IF NOT EXISTS flag VARCHAR(10) NULL AFTER country,
            ADD COLUMN IF NOT EXISTS panel_type VARCHAR(20) NOT NULL DEFAULT '3x-ui' AFTER flag,
            ADD COLUMN IF NOT EXISTS api_url VARCHAR(255) NULL AFTER panel_type,
            ADD COLUMN IF NOT EXISTS api_token VARCHAR(255) NULL AFTER api_url,
            ADD COLUMN IF NOT EXISTS api_username VARCHAR(100) NULL AFTER api_token,
            ADD COLUMN IF NOT EXISTS api_password VARCHAR(255) NULL AFTER api_username,
            ADD COLUMN IF NOT EXISTS inbound_id INT DEFAULT 1 AFTER api_password,
            ADD COLUMN IF NOT EXISTS protocol VARCHAR(20) DEFAULT 'vless' AFTER inbound_id,
            ADD COLUMN IF NOT EXISTS domain VARCHAR(255) NULL AFTER protocol,
            ADD COLUMN IF NOT EXISTS use_https TINYINT(1) DEFAULT 0 AFTER domain,
            ADD COLUMN IF NOT EXISTS panel_port VARCHAR(10) NULL AFTER use_https,
            ADD COLUMN IF NOT EXISTS web_base_path VARCHAR(255) NULL AFTER panel_port
    ");

    // Check if server already exists
    $stmt = $conn->prepare("SELECT id FROM vpn_servers WHERE ip_address = :ip");
    $stmt->bindParam(':ip', $serverConfig['ip_address']);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "Server with IP {$serverConfig['ip_address']} already exists.\n";
        
        // Update existing server
        $params = $serverConfig;
        unset($params['ssh_key_path']);

        $stmt = $conn->prepare("
            UPDATE vpn_servers SET
                name = :name,
                location = :location,
                country = :country,
                flag = :flag,
                status = :status,
                load_percent = :load_percent,
                panel_type = :panel_type,
                api_url = :api_url,
                inbound_id = :inbound_id,
                protocol = :protocol,
                domain = :domain,
                use_https = :use_https,
                panel_port = :panel_port,
                web_base_path = :web_base_path
            WHERE ip_address = :ip_address
        ");
        
        $stmt->execute($params);
        echo "✓ Server updated successfully!\n";
    } else {
        // Insert new server
        $params = $serverConfig;
        unset($params['ssh_key_path']);

        $stmt = $conn->prepare("
            INSERT INTO vpn_servers 
            (name, location, country, flag, ip_address, status, load_percent, panel_type, api_url, 
             api_username, api_password, inbound_id, protocol, domain, use_https, panel_port, web_base_path)
            VALUES 
            (:name, :location, :country, :flag, :ip_address, :status, :load_percent, :panel_type, :api_url,
             :api_username, :api_password, :inbound_id, :protocol, :domain, :use_https, :panel_port, :web_base_path)
        ");
        
        $stmt->execute($params);
        echo "✓ Server added successfully with ID: " . $conn->lastInsertId() . "\n";
    }
    
    echo "\n=== VPS Configuration ===\n";
    echo "- Name: {$serverConfig['name']}\n";
    echo "- IP: {$serverConfig['ip_address']}\n";
    echo "- Panel URL: {$serverConfig['api_url']}\n";
    echo "- SSH Key: {$serverConfig['ssh_key_path']}\n";
    echo "\n=== Next Steps ===\n";
    echo "1. Ensure 3x-ui is installed on your VPS: https://github.com/MHSanaei/3x-ui\n";
    echo "2. Verify SSH works: ssh root@{$serverConfig['ip_address']}\n";
    echo "3. Check 3x-ui panel at: {$serverConfig['api_url']}\n";
    echo "4. Go to http://localhost/trustshield-ai/public/vpn_dashboard.php\n";
    echo "5. Click 'Reset Link' to generate real subscription link\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
