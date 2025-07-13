<?php
/**
 * Database Migration Runner
 * This script runs database migrations and inserts default data
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Database Migration Runner\n";
echo "========================\n\n";

try {
    // Include the functions file
    require_once 'includes/functions.php';
    echo "✓ Functions loaded successfully\n";
    
    // Initialize database (this will run migrations automatically)
    echo "\nInitializing database...\n";
    Database::init();
    echo "✓ Database initialized\n";
    
    // Get version information
    $currentVersion = Database::getCurrentVersion();
    $installedVersion = Database::getInstalledVersion();
    
    echo "\nVersion Information:\n";
    echo "- Current version: $currentVersion\n";
    echo "- Installed version: $installedVersion\n";
    
    if ($installedVersion >= $currentVersion) {
        echo "✓ Database is up to date\n";
    } else {
        echo "✓ Migrations completed successfully\n";
    }
    
    // Insert default data if no users exist
    echo "\nChecking for default data...\n";
    $usersTable = Environment::getTableName('users');
    $sql = "SELECT COUNT(*) as count FROM `$usersTable`";
    $stmt = Database::query($sql);
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount == 0) {
        echo "No users found, inserting default data...\n";
        
        // Insert default groups
        echo "- Inserting default groups...\n";
        Database::insertDefaultGroups();
        
        // Insert default permissions
        echo "- Inserting default permissions...\n";
        Database::insertDefaultPermissions();
        
        // Insert default group permissions
        echo "- Inserting default group permissions...\n";
        Database::insertDefaultGroupPermissions();
        
        // Create default admin user
        echo "- Creating default admin user...\n";
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO `$usersTable` (username, email, password_hash, first_name, last_name, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        Database::query($sql, ['admin', 'admin@example.com', $passwordHash, 'Admin', 'User', 1]);
        
        $userId = Database::lastInsertId();
        
        // Add admin user to admin group
        $groupsTable = Environment::getTableName('groups');
        $userGroupsTable = Environment::getTableName('user_groups');
        $sql = "INSERT INTO `$userGroupsTable` (user_id, group_id) SELECT ?, id FROM `$groupsTable` WHERE name = 'admin'";
        Database::query($sql, [$userId]);
        
        echo "✓ Default admin user created (username: admin, password: admin123)\n";
    } else {
        echo "✓ Users already exist, skipping default data creation\n";
    }
    
    // Test database functionality
    echo "\nTesting database functionality...\n";
    
    // Test users
    $users = UserManager::getAllUsers(5, 0);
    echo "- Found " . count($users) . " users\n";
    
    // Test groups
    $groups = UserManager::getAllGroups();
    echo "- Found " . count($groups) . " groups\n";
    
    // Test permissions
    $permissions = UserManager::getAllPermissions();
    echo "- Found " . count($permissions) . " permissions\n";
    
    // Test collection items table
    $collectionItemsTable = Environment::getTableName('collection_items');
    $sql = "SHOW TABLES LIKE '$collectionItemsTable'";
    $stmt = Database::query($sql);
    if ($stmt->rowCount() > 0) {
        echo "- Collection items table exists\n";
    } else {
        echo "- Collection items table missing\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    echo "You can now access the application.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error during migration:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
    exit(1);
}
?> 