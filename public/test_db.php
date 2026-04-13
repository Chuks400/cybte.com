<?php
header('Content-Type: text/plain');

echo "=== Database Connection Test ===\n\n";

// Test 1: Check if .env is loaded
echo "1. Environment Variables:\n";
echo "   DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
echo "   DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "\n";
echo "   DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "\n";
echo "   DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "\n";
echo "   DB_PASS: " . (getenv('DB_PASS') ? '***SET***' : 'NOT SET') . "\n\n";

// Test 2: Load .env manually
$envPath = __DIR__ . '/../.env';
echo "2. Loading .env file: $envPath\n";
if (file_exists($envPath)) {
    echo "   File exists - loading...\n";
    require_once $envPath;
    echo "   Loaded.\n\n";
    
    echo "3. Environment Variables After Load:\n";
    echo "   DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
    echo "   DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "\n";
    echo "   DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET') . "\n";
    echo "   DB_USER: " . (getenv('DB_USER') ?: 'NOT SET') . "\n";
    echo "   DB_PASS: " . (getenv('DB_PASS') ? '***SET***' : 'NOT SET') . "\n\n";
} else {
    echo "   File NOT found!\n\n";
}

// Test 3: Try direct connection
echo "4. Testing Direct Connection:\n";
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'Cjohn22@';
$name = getenv('DB_NAME') ?: 'cybte';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✓ Connection successful!\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) FROM vpn_servers");
    $count = $stmt->fetchColumn();
    echo "   ✓ VPN servers in database: $count\n";
} catch (PDOException $e) {
    echo "   ✗ Connection failed: " . $e->getMessage() . "\n";
    
    // Try without database name
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
        echo "   ✓ Server connection OK (no DB). Database '$name' may not exist.\n";
    } catch (PDOException $e2) {
        echo "   ✗ Server connection also failed: " . $e2->getMessage() . "\n";
    }
}
