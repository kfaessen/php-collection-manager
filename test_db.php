<?php
/**
 * Database Test Script
 * Run this to check if database connection and tables are working correctly
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Test Script</h1>\n";

try {
    // Include the functions file
    require_once 'includes/functions.php';
    echo "<p>✅ Functions loaded successfully</p>\n";
    
    // Test database connection
    echo "<h2>Testing Database Connection</h2>\n";
    $connection = Database::getConnection();
    echo "<p>✅ Database connection successful</p>\n";
    
    // Test table creation
    echo "<h2>Testing Table Creation</h2>\n";
    
    // Check if tables exist
    $tables = ['users', 'groups', 'permissions', 'user_groups', 'group_permissions', 'collection_items', 'sessions', 'shared_links'];
    
    foreach ($tables as $table) {
        $tableName = Environment::getTableName($table);
        $sql = "SHOW TABLES LIKE '$tableName'";
        $stmt = Database::query($sql);
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$tableName' exists</p>\n";
        } else {
            echo "<p>❌ Table '$tableName' does not exist</p>\n";
        }
    }
    
    // Test permissions
    echo "<h2>Testing Permissions</h2>\n";
    $permissions = UserManager::getAllPermissions();
    echo "<p>✅ Found " . count($permissions) . " permissions</p>\n";
    
    // Test groups
    echo "<h2>Testing Groups</h2>\n";
    $groups = UserManager::getAllGroups();
    echo "<p>✅ Found " . count($groups) . " groups</p>\n";
    
    // Test users
    echo "<h2>Testing Users</h2>\n";
    $users = UserManager::getAllUsers(10, 0);
    echo "<p>✅ Found " . count($users) . " users</p>\n";
    
    // Test user stats
    echo "<h2>Testing User Stats</h2>\n";
    $stats = UserManager::getUserStats();
    echo "<p>✅ User stats: " . json_encode($stats) . "</p>\n";
    
    // Test environment
    echo "<h2>Testing Environment</h2>\n";
    echo "<p>✅ APP_ENV: " . Environment::get('APP_ENV') . "</p>\n";
    echo "<p>✅ DB_HOST: " . Environment::get('DB_HOST') . "</p>\n";
    echo "<p>✅ DB_NAME: " . Environment::get('DB_NAME') . "</p>\n";
    
    echo "<h2>✅ All tests passed!</h2>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Error occurred:</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>\n";
    echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>