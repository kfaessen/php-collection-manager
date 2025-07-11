<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing PHP configuration...\n";

// Test if we can include the functions file
try {
    require_once 'includes/functions.php';
    echo "✓ Functions file loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Error loading functions: " . $e->getMessage() . "\n";
}

// Test database connection
try {
    $host = 'localhost';
    $dbname = 'collection_manager';
    $username = 'root';
    $password = '';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test if we can create a database
try {
    $dsn = "mysql:host=localhost;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS collection_manager");
    echo "✓ Database created successfully\n";
} catch (PDOException $e) {
    echo "✗ Database creation failed: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>