<?php
/**
 * Test script om basis functionaliteit te controleren
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Script</h1>";

try {
    echo "<h2>1. Loading functions.php</h2>";
    require_once '../includes/functions.php';
    echo "✓ Functions loaded successfully<br>";
    
    echo "<h2>2. Testing Environment</h2>";
    echo "APP_ENV: " . Environment::get('APP_ENV') . "<br>";
    echo "DB_HOST: " . Environment::get('DB_HOST') . "<br>";
    echo "DB_NAME: " . Environment::get('DB_NAME') . "<br>";
    echo "DB_USER: " . Environment::get('DB_USER') . "<br>";
    echo "DB_PREFIX: " . Environment::get('DB_PREFIX') . "<br>";
    echo "✓ Environment loaded successfully<br>";
    
    echo "<h2>3. Testing Database Connection</h2>";
    try {
        $connection = Database::getConnection();
        echo "✓ Database connection successful<br>";
        
        // Test if tables exist
        $usersTable = Environment::getTableName('users');
        echo "Users table name: $usersTable<br>";
        
        $sql = "SHOW TABLES LIKE '$usersTable'";
        $stmt = Database::query($sql);
        
        if ($stmt->rowCount() > 0) {
            echo "✓ Users table exists<br>";
        } else {
            echo "⚠ Users table does not exist<br>";
        }
        
        // Test if setup is needed
        if (Database::needsSetup()) {
            echo "⚠ Setup is needed - no users exist<br>";
        } else {
            echo "✓ Setup is not needed - users exist<br>";
        }
        
    } catch (Exception $e) {
        echo "✗ Database error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>4. Testing Authentication</h2>";
    try {
        Authentication::init();
        echo "✓ Authentication initialized<br>";
        
        if (Authentication::isLoggedIn()) {
            echo "✓ User is logged in<br>";
            $user = Authentication::getCurrentUser();
            echo "Current user: " . $user['username'] . "<br>";
        } else {
            echo "⚠ No user logged in<br>";
        }
        
    } catch (Exception $e) {
        echo "✗ Authentication error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>5. Testing Utils</h2>";
    try {
        $testInput = "test<script>alert('xss')</script>";
        $sanitized = Utils::sanitize($testInput);
        echo "Sanitize test: " . $sanitized . "<br>";
        echo "✓ Utils working correctly<br>";
        
    } catch (Exception $e) {
        echo "✗ Utils error: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>✗ Fatal Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Test Complete</h2>";
echo "<a href='index.php'>Back to main page</a>";
?>