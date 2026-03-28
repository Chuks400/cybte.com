<?php
/**
 * Database Migration Runner
 * Execute this to create payment tables
 */

require_once __DIR__ . '/../src/config/database.php';

echo "Running TrustShield Payment Migration...\n\n";

try {
    $database = new Database();
    $conn = $database->connect();
    
    // Read migration file
    $migrationSql = file_get_contents(__DIR__ . '/../database/migrations/create_payments_table.sql');
    
    if (!$migrationSql) {
        throw new Exception("Could not read migration file");
    }
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
    
    foreach ($statements as $index => $sql) {
        if (empty($sql)) continue;
        
        echo "Executing statement " . ($index + 1) . "... ";
        
        try {
            $conn->exec($sql);
            echo "✓ OK\n";
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "✓ Already exists (skipped)\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n✓ Migration completed successfully!\n\n";
    
    // Verify tables exist
    $tables = ['payments', 'payment_plans', 'payment_webhook_logs'];
    echo "Verifying tables:\n";
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ {$table}\n";
        } else {
            echo "  ✗ {$table} NOT FOUND\n";
        }
    }
    
    echo "\nPayment system is ready to use!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
