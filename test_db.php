<?php
// Test script om database connectie te controleren
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n";

try {
    require_once 'includes/functions.php';
    echo "Functions loaded successfully\n";
    
    // Test database connection
    $connection = Database::getConnection();
    echo "Database connection successful\n";
    
    // Test if tables exist
    $usersTable = Environment::getTableName('users');
    $sql = "SHOW TABLES LIKE '$usersTable'";
    $stmt = Database::query($sql);
    
    if ($stmt->rowCount() > 0) {
        echo "Users table exists\n";
    } else {
        echo "Users table does not exist\n";
    }
    
    // Test if setup is needed
    if (Database::needsSetup()) {
        echo "Setup is needed - no users exist\n";
    } else {
        echo "Setup is not needed - users exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>