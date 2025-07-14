<?php
/**
 * Database Update Script
 * This script safely checks and updates the database structure
 * without causing data loss
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
    
    // Initialize database (this will run migrations automatically)
    echo "\nInitializing database...\n";
    Database::init();
    echo "✓ Database initialized\n";
    
    // Check current database version
    $installedVersion = Database::getCurrentVersion();
    $targetVersion = Database::getTargetVersion();
    
    echo "\nVersion Information:\n";
    echo "- Installed version: $installedVersion\n";
    echo "- Target version: $targetVersion\n";
    
    if ($installedVersion >= $targetVersion) {
        echo "✓ Database is up to date\n";
    } else {
        echo "⚠ Database was updated from version $installedVersion to $targetVersion\n";
    }
    
    // Safely check and update collection_items table structure
    echo "\nChecking collection_items table structure...\n";
    $collectionItemsTable = Environment::getTableName('collection_items');
    
    // Check if table exists
    $sql = "SHOW TABLES LIKE '$collectionItemsTable'";
    $stmt = Database::query($sql);
    
    if ($stmt->rowCount() > 0) {
        echo "✓ Collection items table exists\n";
        
        // Check if table has data before making structural changes
        $sql = "SELECT COUNT(*) as count FROM `$collectionItemsTable`";
        $stmt = Database::query($sql);
        $itemCount = $stmt->fetch()['count'];
        
        if ($itemCount > 0) {
            echo "⚠ Table contains $itemCount items - using safe migration approach\n";
        }
        
        // Use the new safe update method
        $requiredColumns = [
            'user_id' => "INT NOT NULL",
            'title' => "VARCHAR(255) NOT NULL", 
            'description' => "TEXT",
            'type' => "VARCHAR(50) NOT NULL",
            'platform' => "VARCHAR(100)",
            'category' => "VARCHAR(100)",
            'condition_rating' => "INT DEFAULT 5",
            'purchase_date' => "DATE NULL",
            'purchase_price' => "DECIMAL(10,2) NULL",
            'current_value' => "DECIMAL(10,2) NULL",
            'location' => "VARCHAR(255)",
            'notes' => "TEXT",
            'cover_image' => "VARCHAR(255)",
            'barcode' => "VARCHAR(50)",
            'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        $result = Database::safelyUpdateTableStructure($collectionItemsTable, $requiredColumns);
        
        if ($result['success']) {
            if (isset($result['added_columns']) && !empty($result['added_columns'])) {
                echo "✓ " . $result['message'] . "\n";
                
                // Add foreign key if user_id column was added
                if (in_array('user_id', $result['added_columns'])) {
                    try {
                        $usersTable = Environment::getTableName('users');
                        $sql = "ALTER TABLE `$collectionItemsTable` 
                                ADD CONSTRAINT fk_collection_items_user_id 
                                FOREIGN KEY (user_id) REFERENCES `$usersTable`(id) ON DELETE CASCADE";
                        Database::query($sql);
                        echo "✓ Added foreign key constraint for user_id\n";
                    } catch (\Exception $e) {
                        echo "⚠ Warning: Could not add foreign key constraint: " . $e->getMessage() . "\n";
                        echo "   This might be because existing data doesn't have valid user_id values\n";
                        echo "   Consider manually updating the data or setting a default user_id\n";
                    }
                }
                
                // Add indexes for newly added columns
                $indexes = [
                    'idx_user_id' => 'user_id',
                    'idx_type' => 'type', 
                    'idx_category' => 'category',
                    'idx_barcode' => 'barcode'
                ];
                
                foreach ($indexes as $indexName => $columnName) {
                    if (in_array($columnName, $result['added_columns'])) {
                        try {
                            $sql = "CREATE INDEX IF NOT EXISTS `$indexName` ON `$collectionItemsTable` (`$columnName`)";
                            Database::query($sql);
                            echo "✓ Added index: $indexName\n";
                        } catch (\Exception $e) {
                            echo "ℹ Index $indexName already exists or could not be created\n";
                        }
                    }
                }
            } else {
                echo "✓ " . $result['message'] . "\n";
            }
        } else {
            echo "⚠ Warning: " . $result['error'] . "\n";
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
        
        // Ensure admin group exists
        $groupsTable = Environment::getTableName('groups');
        $sql = "SELECT id FROM `$groupsTable` WHERE name = 'admin'";
        $stmt = Database::query($sql);
        
        if ($stmt->rowCount() == 0) {
            echo "Creating admin group...\n";
            $sql = "INSERT INTO `$groupsTable` (name, description) VALUES (?, ?)";
            Database::query($sql, ['admin', 'Administrator group with full access']);
            $adminGroupId = Database::lastInsertId();
            echo "✓ Admin group created with ID: $adminGroupId\n";
        } else {
            $adminGroupId = $stmt->fetch()['id'];
            echo "✓ Admin group already exists with ID: $adminGroupId\n";
        }
        
        // Create default admin user
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO `$usersTable` (username, email, password_hash, first_name, last_name, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        Database::query($sql, ['admin', 'admin@example.com', $passwordHash, 'Admin', 'User', 1]);
        
        $userId = Database::lastInsertId();
        
        // Add admin user to admin group
        $userGroupsTable = Environment::getTableName('user_groups');
        $sql = "INSERT INTO `$userGroupsTable` (user_id, group_id) VALUES (?, ?)";
        Database::query($sql, [$userId, $adminGroupId]);
        
        echo "✓ Default admin user created (username: admin, password: admin123)\n";
        echo "✓ Admin user assigned to admin group\n";
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
        $sql = "SELECT COUNT(*) as count FROM `$collectionItemsTable`";
        $stmt = Database::query($sql);
        $itemCount = $stmt->fetch()['count'];
        echo "- Collection items table exists and is accessible ($itemCount items)\n";
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