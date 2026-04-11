<?php
/**
 * Payment System Setup - Run this to create database tables
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $conn = $database->connect();
        
        // Read migration SQL
        $sql = file_get_contents(__DIR__ . '/../database/migrations/create_payments_table.sql');
        
        if (!$sql) {
            throw new Exception("Could not read migration file");
        }
        
        // Split and execute statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $executed = 0;
        $skipped = 0;
        
        foreach ($statements as $statement) {
            if (empty($statement)) continue;
            
            try {
                $conn->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Duplicate') !== false) {
                    $skipped++;
                } else {
                    throw $e;
                }
            }
        }
        
        $message = "Migration completed! {$executed} statements executed, {$skipped} skipped (already exist).";
        
    } catch (Exception $e) {
        $error = "Migration failed: " . $e->getMessage();
    }
}

// Check current status
try {
    $database = new Database();
    $conn = $database->connect();
    
    $tables = ['payments', 'payment_plans', 'payment_webhook_logs'];
    $tableStatus = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
        $tableStatus[$table] = $stmt->rowCount() > 0;
    }
} catch (Exception $e) {
    $error = "Cannot check table status: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment System Setup - Cybte VPN</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a1929;
            color: #fff;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        h1 { color: #00E5FF; }
        .card {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(0, 229, 255, 0.2);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .success { color: #00ff88; }
        .error { color: #ff7a7a; }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status.ok { background: rgba(0, 255, 136, 0.15); color: #00ff88; }
        .status.missing { background: rgba(255, 68, 68, 0.15); color: #ff7a7a; }
        button {
            background: linear-gradient(45deg, #00E5FF, #0099CC);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            color: #0a1929;
            font-weight: 800;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover { opacity: 0.9; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .test-link {
            color: #00E5FF;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <h1><i class="fas fa-cog"></i> Payment System Setup</h1>
    
    <?php if ($message): ?>
        <div class="card success">
            <strong>✓ <?php echo htmlspecialchars($message); ?></strong>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="card error">
            <strong>✗ <?php echo htmlspecialchars($error); ?></strong>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h3>Database Table Status</h3>
        <table>
            <?php foreach ($tableStatus as $table => $exists): ?>
                <tr>
                    <td><?php echo $table; ?></td>
                    <td>
                        <span class="status <?php echo $exists ? 'ok' : 'missing'; ?>">
                            <?php echo $exists ? '✓ Exists' : '✗ Missing'; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <?php if (in_array(false, $tableStatus)): ?>
            <form method="POST" style="margin-top: 20px;">
                <button type="submit">
                    <i class="fas fa-database"></i> Create Missing Tables
                </button>
            </form>
        <?php else: ?>
            <p class="success" style="margin-top: 20px;">
                ✓ All tables exist. Payment system is ready!
            </p>
            <p>
                <a href="api/payment/test.php" class="test-link">Run System Test →</a>
            </p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h3>Quick Links</h3>
        <ul>
            <li><a href="vpn_pricing.php" class="test-link">Pricing Page</a></li>
            <li><a href="vpn_dashboard.php" class="test-link">Dashboard</a></li>
            <li><a href="admin_payments.php" class="test-link">Admin Payments</a></li>
        </ul>
    </div>
</body>
</html>
