<?php
/**
 * Database Update Script
 * This script checks and updates the database structure
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Database Update Script\n";
echo "=====================\n\n";

try {
    // Include the functions file
    require_once 'includes/functions.php';
    echo "✓ Functions loaded successfully\n";
    
    // Initialize database (this will check and update structure)
    echo "\nInitializing database...\n";
    Database::init();
    echo "✓ Database initialized\n";
    
    // Check if collection_items table exists and has correct structure
    echo "\nChecking database structure...\n";
    $collectionItemsTable = Environment::getTableName('collection_items');
    $sql = "SHOW TABLES LIKE '$collectionItemsTable'";
    $stmt = Database::query($sql);
    
    if ($stmt->rowCount() > 0) {
        echo "✓ Collection items table exists\n";
        
        // Check if it has the user_id column
        $sql = "SHOW COLUMNS FROM `$collectionItemsTable` LIKE 'user_id'";
        $stmt = Database::query($sql);
        
        if ($stmt->rowCount() > 0) {
            echo "✓ Collection items table has correct structure\n";
        } else {
            echo "⚠ Collection items table needs update\n";
            echo "Updating table structure...\n";
            
            // Drop and recreate the table
            $sql = "DROP TABLE IF EXISTS `$collectionItemsTable`";
            Database::query($sql);
            
            // Recreate with correct structure
            $sql = "
                CREATE TABLE `$collectionItemsTable` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    type VARCHAR(50) NOT NULL,
                    platform VARCHAR(100),
                    category VARCHAR(100),
                    condition_rating INT DEFAULT 5,
                    purchase_date DATE NULL,
                    purchase_price DECIMAL(10,2) NULL,
                    current_value DECIMAL(10,2) NULL,
                    location VARCHAR(255),
                    notes TEXT,
                    cover_image VARCHAR(255),
                    barcode VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES " . Environment::getTableName('users') . "(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_type (type),
                    INDEX idx_category (category),
                    INDEX idx_barcode (barcode)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            Database::query($sql);
            echo "✓ Collection items table updated successfully\n";
        }
    } else {
        echo "⚠ Collection items table missing, creating...\n";
        
        $sql = "
            CREATE TABLE `$collectionItemsTable` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                type VARCHAR(50) NOT NULL,
                platform VARCHAR(100),
                category VARCHAR(100),
                condition_rating INT DEFAULT 5,
                purchase_date DATE NULL,
                purchase_price DECIMAL(10,2) NULL,
                current_value DECIMAL(10,2) NULL,
                location VARCHAR(255),
                notes TEXT,
                cover_image VARCHAR(255),
                barcode VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES " . Environment::getTableName('users') . "(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_type (type),
                INDEX idx_category (category),
                INDEX idx_barcode (barcode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        Database::query($sql);
        echo "✓ Collection items table created successfully\n";
    }
    
    // Check if default data exists
    echo "\nChecking default data...\n";
    $usersTable = Environment::getTableName('users');
    $sql = "SELECT COUNT(*) as count FROM `$usersTable`";
    $stmt = Database::query($sql);
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount == 0) {
        echo "No users found, creating default admin user...\n";
        
        // Create default admin user
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
        echo "✓ Users already exist\n";
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
    $sql = "SHOW TABLES LIKE '$collectionItemsTable'";
    $stmt = Database::query($sql);
    if ($stmt->rowCount() > 0) {
        echo "- Collection items table exists and is accessible\n";
    } else {
        echo "- Collection items table missing\n";
    }
    
    echo "\n✅ Database update completed successfully!\n";
    echo "You can now access the application.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error during database update:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nPlease check your database configuration and try again.\n";
    exit(1);
}
?> 