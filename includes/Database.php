<?php
namespace CollectionManager;

use CollectionManager\Environment;

class Database 
{
    private static $connection = null;
    private static $initialized = false;
    private static $currentVersion = 4; // Huidige database versie
    
    /**
     * Initialize database connection
     */
    public static function init() 
    {
        if (self::$initialized) {
            return;
        }
        
        try {
            $host = Environment::get('DB_HOST', 'localhost');
            $dbname = Environment::get('DB_NAME', 'collection_manager');
            $username = Environment::get('DB_USER', 'root');
            $password = Environment::get('DB_PASS', '');
            $charset = Environment::get('DB_CHARSET', 'utf8mb4');
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            self::$connection = new \PDO($dsn, $username, $password, $options);
            self::$initialized = true;
            
            // Check and run database migrations
            self::checkAndRunMigrations();
            
        } catch (\PDOException $e) {
            // Always show detailed error in development
            if (Environment::isDevelopment()) {
                die("Database connection failed: " . $e->getMessage() . "\n\nPlease run setup_database.php to create the database and tables.");
            } else {
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection failed. Please check your configuration and run setup_database.php if needed.");
            }
        }
    }
    
    /**
     * Check and run database migrations
     */
    private static function checkAndRunMigrations() 
    {
        try {
            // Create migrations table if it doesn't exist
            self::createMigrationsTable();
            
            // Get current database version
            $currentVersion = self::getCurrentDatabaseVersion();
            
            // Run migrations if needed
            if ($currentVersion < self::$currentVersion) {
                self::runMigrations($currentVersion);
            }
            
        } catch (\Exception $e) {
            error_log("Migration error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create migrations table
     */
    private static function createMigrationsTable() 
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `database_migrations` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                version INT NOT NULL,
                migration_name VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_version (version)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        self::query($sql);
    }
    
    /**
     * Get current database version
     */
    private static function getCurrentDatabaseVersion() 
    {
        try {
            $sql = "SELECT MAX(version) as current_version FROM database_migrations";
            $stmt = self::query($sql);
            $result = $stmt->fetch();
            return $result['current_version'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Run migrations from current version to target version
     */
    private static function runMigrations($fromVersion) 
    {
        $migrations = self::getMigrations();
        
        foreach ($migrations as $version => $migration) {
            if ($version > $fromVersion && $version <= self::$currentVersion) {
                self::executeMigration($version, $migration);
                error_log("Migration v$version executed successfully");
            }
        }
    }
    
    /**
     * Get all available migrations
     */
    private static function getMigrations() 
    {
        return [
            1 => [
                'name' => 'Initial database setup',
                'sql' => [
                    // Users table
                    "CREATE TABLE IF NOT EXISTS `users` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(50) UNIQUE NOT NULL,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        password_hash VARCHAR(255) NOT NULL,
                        first_name VARCHAR(100) NOT NULL,
                        last_name VARCHAR(100) NOT NULL,
                        is_active BOOLEAN DEFAULT TRUE,
                        last_login TIMESTAMP NULL,
                        failed_login_attempts INT DEFAULT 0,
                        locked_until TIMESTAMP NULL,
                        totp_secret VARCHAR(32) NULL,
                        totp_enabled BOOLEAN DEFAULT FALSE,
                        totp_backup_codes TEXT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_username (username),
                        INDEX idx_email (email),
                        INDEX idx_active (is_active)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Groups table
                    "CREATE TABLE IF NOT EXISTS `groups` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(50) UNIQUE NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Permissions table
                    "CREATE TABLE IF NOT EXISTS `permissions` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(50) UNIQUE NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // User groups table
                    "CREATE TABLE IF NOT EXISTS `user_groups` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        group_id INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_user_group (user_id, group_id),
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Group permissions table
                    "CREATE TABLE IF NOT EXISTS `group_permissions` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        group_id INT NOT NULL,
                        permission_id INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_group_permission (group_id, permission_id),
                        FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Sessions table
                    "CREATE TABLE IF NOT EXISTS `sessions` (
                        id VARCHAR(128) PRIMARY KEY,
                        user_id INT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        payload TEXT NOT NULL,
                        last_activity INT NOT NULL,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Shared links table
                    "CREATE TABLE IF NOT EXISTS `shared_links` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        item_id INT NOT NULL,
                        token VARCHAR(64) UNIQUE NOT NULL,
                        expires_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        INDEX idx_token (token)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                ]
            ],
            2 => [
                'name' => 'Add collection_items table',
                'sql' => [
                    // Collection items table
                    "CREATE TABLE IF NOT EXISTS `collection_items` (
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
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        INDEX idx_user_id (user_id),
                        INDEX idx_type (type),
                        INDEX idx_category (category),
                        INDEX idx_barcode (barcode)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                ]
            ],
            3 => [
                'name' => 'Safely update collection_items table structure',
                'sql' => [
                    // Add missing columns safely if they don't exist
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `condition_rating` INT DEFAULT 5",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `purchase_date` DATE NULL",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `purchase_price` DECIMAL(10,2) NULL",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `current_value` DECIMAL(10,2) NULL",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `location` VARCHAR(255)",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `notes` TEXT",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(255)",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `barcode` VARCHAR(50)",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
                    
                    // Add indexes if they don't exist
                    "CREATE INDEX IF NOT EXISTS `idx_type` ON `collection_items` (`type`)",
                    "CREATE INDEX IF NOT EXISTS `idx_category` ON `collection_items` (`category`)",
                    "CREATE INDEX IF NOT EXISTS `idx_barcode` ON `collection_items` (`barcode`)"
                ]
            ],
            4 => [
                'name' => 'Safely add user_id column to collection_items table',
                'sql' => [
                    // Add user_id column with default value
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `user_id` INT NOT NULL DEFAULT 1",
                    
                    // Update all existing records to use the default user (admin)
                    "UPDATE `collection_items` SET user_id = 1 WHERE user_id = 0",
                    
                    // Add index if it doesn't exist
                    "CREATE INDEX IF NOT EXISTS `idx_user_id` ON `collection_items` (`user_id`)"
                ]
            ]
        ];
    }
    
    /**
     * Execute a specific migration
     */
    private static function executeMigration($version, $migration) 
    {
        $connection = self::getConnection();
        $transactionStarted = false;
        
        try {
            // Check if there's already an active transaction
            if (!$connection->inTransaction()) {
                $connection->beginTransaction();
                $transactionStarted = true;
            }
            
            // Execute all SQL statements for this migration
            foreach ($migration['sql'] as $sql) {
                try {
                    self::query($sql);
                } catch (\Exception $e) {
                    // Check if this is a non-critical error (like column already exists)
                    if (strpos($sql, 'ADD COLUMN IF NOT EXISTS') !== false || 
                        strpos($sql, 'CREATE INDEX IF NOT EXISTS') !== false ||
                        strpos($e->getMessage(), 'Duplicate column name') !== false ||
                        strpos($e->getMessage(), 'Duplicate key name') !== false ||
                        strpos($e->getMessage(), 'already exists') !== false) {
                        error_log("Migration v$version warning (non-critical): " . $e->getMessage() . " (SQL: $sql)");
                        // Continue with the migration - don't fail the entire migration
                    } else {
                        // For other errors, re-throw the exception
                        throw $e;
                    }
                }
            }
            
            // Record migration as executed (within the same transaction)
            $sql = "INSERT INTO database_migrations (version, migration_name) VALUES (?, ?)";
            self::query($sql, [$version, $migration['name']]);
            
            // Commit transaction only if we started it
            if ($transactionStarted) {
                $connection->commit();
            }
            
        } catch (\Exception $e) {
            // Rollback if we started the transaction and it's still active
            if ($transactionStarted && $connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Get database connection
     */
    public static function getConnection() 
    {
        if (!self::$initialized) {
            self::init();
        }
        
        return self::$connection;
    }
    
    /**
     * Execute a query
     */
    public static function query($sql, $params = []) 
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId() 
    {
        return self::getConnection()->lastInsertId();
    }
    
    /**
     * Create necessary tables (legacy method - kept for backward compatibility)
     */
    private static function createTables() 
    {
        // This method is now deprecated - migrations handle table creation
        // But we keep it for backward compatibility
        self::checkAndRunMigrations();
    }
    
    /**
     * Create users table (legacy method)
     */
    private static function createUsersTable() 
    {
        // Deprecated - handled by migrations
    }
    
    /**
     * Create groups table (legacy method)
     */
    private static function createGroupsTable() 
    {
        // Deprecated - handled by migrations
    }
    
    /**
     * Create permissions table (legacy method)
     */
    private static function createPermissionsTable() 
    {
        // Deprecated - handled by migrations
    }
    
    /**
     * Create user_groups table (legacy method)
     */
    private static function createUserGroupsTable() 
    {
        // Deprecated - handled by migrations
    }
    
    /**
     * Create group_permissions table (legacy method)
     */
    private static function createGroupPermissionsTable() 
    {
        // Deprecated - handled by migrations
    }
    
    /**
     * Create collection_items table (legacy method)
     */
    private static function createCollectionItemsTable() 
    {
        // Deprecated - handled by migrations
    }
    
    /**
     * Create sessions table (legacy method)
     */
    private static function createSessionsTable() 
    {
        // Deprecated - handled by migrations
    }
    
    /**
     * Create shared_links table (legacy method)
     */
    private static function createSharedLinksTable() 
    {
        // Deprecated - handled by migrations
    }
    
    /**
     * Insert default groups (legacy method)
     */
    private static function insertDefaultGroups() 
    {
        $groupsTable = Environment::getTableName('groups');
        
        $groups = [
            ['name' => 'admin', 'description' => 'Systeembeheerders'],
            ['name' => 'user', 'description' => 'Gewone gebruikers'],
            ['name' => 'moderator', 'description' => 'Moderators']
        ];
        
        $stmt = self::getConnection()->prepare("INSERT IGNORE INTO `$groupsTable` (name, description) VALUES (?, ?)");
        foreach ($groups as $group) {
            $stmt->execute([$group['name'], $group['description']]);
        }
    }
    
    /**
     * Insert default permissions (legacy method)
     */
    private static function insertDefaultPermissions() 
    {
        $permissionsTable = Environment::getTableName('permissions');
        
        $permissions = [
            ['name' => 'system_admin', 'description' => 'Systeembeheerder rechten'],
            ['name' => 'user_management', 'description' => 'Gebruikersbeheer'],
            ['name' => 'group_management', 'description' => 'Groepenbeheer'],
            ['name' => 'collection_view', 'description' => 'Collectie bekijken'],
            ['name' => 'collection_edit', 'description' => 'Collectie bewerken'],
            ['name' => 'collection_delete', 'description' => 'Collectie verwijderen']
        ];
        
        $stmt = self::getConnection()->prepare("INSERT IGNORE INTO `$permissionsTable` (name, description) VALUES (?, ?)");
        foreach ($permissions as $permission) {
            $stmt->execute([$permission['name'], $permission['description']]);
        }
    }
    
    /**
     * Insert default group permissions (legacy method)
     */
    private static function insertDefaultGroupPermissions() 
    {
        $groupsTable = Environment::getTableName('groups');
        $permissionsTable = Environment::getTableName('permissions');
        $groupPermissionsTable = Environment::getTableName('group_permissions');
        
        $groupPermissions = [
            ['group' => 'admin', 'permission' => 'system_admin'],
            ['group' => 'admin', 'permission' => 'user_management'],
            ['group' => 'admin', 'permission' => 'group_management'],
            ['group' => 'admin', 'permission' => 'collection_view'],
            ['group' => 'admin', 'permission' => 'collection_edit'],
            ['group' => 'admin', 'permission' => 'collection_delete'],
            ['group' => 'moderator', 'permission' => 'user_management'],
            ['group' => 'moderator', 'permission' => 'collection_view'],
            ['group' => 'moderator', 'permission' => 'collection_edit'],
            ['group' => 'user', 'permission' => 'collection_view'],
            ['group' => 'user', 'permission' => 'collection_edit']
        ];
        
        $stmt = self::getConnection()->prepare("
            INSERT IGNORE INTO `$groupPermissionsTable` (group_id, permission_id) 
            SELECT g.id, p.id 
            FROM `$groupsTable` g, `$permissionsTable` p 
            WHERE g.name = ? AND p.name = ?
        ");
        
        foreach ($groupPermissions as $gp) {
            $stmt->execute([$gp['group'], $gp['permission']]);
        }
    }
    
    /**
     * Add TOTP columns to existing table if they don't exist (legacy method)
     */
    private static function addTOTPColumnsIfNotExist($tableName) 
    {
        // Check if TOTP columns exist
        $sql = "SHOW COLUMNS FROM `$tableName` LIKE 'totp_secret'";
        $stmt = self::query($sql);
        
        if ($stmt->rowCount() == 0) {
            $sql = "ALTER TABLE `$tableName` 
                    ADD COLUMN totp_secret VARCHAR(32) NULL,
                    ADD COLUMN totp_enabled BOOLEAN DEFAULT FALSE,
                    ADD COLUMN totp_backup_codes TEXT NULL";
            self::query($sql);
        }
    }
    
    /**
     * Check if database needs setup (legacy method)
     */
    public static function needsSetup() 
    {
        try {
            $currentVersion = self::getCurrentDatabaseVersion();
            return $currentVersion < self::$currentVersion;
        } catch (\Exception $e) {
            return true;
        }
    }
    
    /**
     * Get current database version (public method)
     */
    public static function getCurrentVersion() 
    {
        return self::$currentVersion;
    }
    
    /**
     * Get installed database version (public method)
     */
    public static function getInstalledVersion() 
    {
        return self::getCurrentDatabaseVersion();
    }
    
    /**
     * Safely update table structure without data loss
     */
    public static function safelyUpdateTableStructure($tableName, $requiredColumns) 
    {
        try {
            // Get current table structure
            $sql = "DESCRIBE `$tableName`";
            $stmt = self::query($sql);
            $columns = $stmt->fetchAll();
            $columnNames = array_column($columns, 'Field');
            
            // Check for missing columns and add them safely
            $missingColumns = [];
            foreach ($requiredColumns as $columnName => $columnDef) {
                if (!in_array($columnName, $columnNames)) {
                    $missingColumns[$columnName] = $columnDef;
                }
            }
            
            if (empty($missingColumns)) {
                return ['success' => true, 'message' => 'Table structure is up to date'];
            }
            
            // Special handling for user_id column if it's missing
            if (in_array('user_id', array_keys($missingColumns))) {
                $result = self::safelyAddUserIdColumn($tableName);
                if (!$result['success']) {
                    return $result;
                }
                // Remove user_id from missing columns since it's handled separately
                unset($missingColumns['user_id']);
            }
            
            // Add remaining missing columns safely
            $addedColumns = [];
            foreach ($missingColumns as $columnName => $columnDef) {
                try {
                    $sql = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $columnDef";
                    self::query($sql);
                    $addedColumns[] = $columnName;
                } catch (\Exception $e) {
                    error_log("Could not add column $columnName to table $tableName: " . $e->getMessage());
                }
            }
            
            // Include user_id in added columns if it was added
            if (isset($result) && $result['success'] && isset($result['added_columns'])) {
                $addedColumns = array_merge($result['added_columns'], $addedColumns);
            }
            
            return [
                'success' => true, 
                'message' => 'Added columns: ' . implode(', ', $addedColumns),
                'added_columns' => $addedColumns
            ];
            
        } catch (\Exception $e) {
            error_log("Error updating table structure for $tableName: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Safely add user_id column to collection_items table without data loss
     */
    private static function safelyAddUserIdColumn($tableName) 
    {
        try {
            // Check if table has data
            $sql = "SELECT COUNT(*) as count FROM `$tableName`";
            $stmt = self::query($sql);
            $itemCount = $stmt->fetch()['count'];
            
            if ($itemCount > 0) {
                // Table has data - we need to handle this carefully
                // First, check if there's a default admin user we can assign items to
                $usersTable = Environment::getTableName('users');
                $sql = "SELECT id FROM `$usersTable` WHERE username = 'admin' LIMIT 1";
                $stmt = self::query($sql);
                
                if ($stmt->rowCount() > 0) {
                    $adminUserId = $stmt->fetch()['id'];
                    
                    // Add user_id column with default value
                    $sql = "ALTER TABLE `$tableName` ADD COLUMN `user_id` INT NOT NULL DEFAULT ?";
                    self::query($sql, [$adminUserId]);
                    
                    // Update all existing records to use the admin user
                    $sql = "UPDATE `$tableName` SET user_id = ? WHERE user_id = ?";
                    self::query($sql, [$adminUserId, $adminUserId]);
                    
                    return [
                        'success' => true,
                        'message' => "Added user_id column and assigned $itemCount items to admin user",
                        'added_columns' => ['user_id']
                    ];
                } else {
                    // No admin user exists - create one first
                    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                    $sql = "INSERT INTO `$usersTable` (username, email, password_hash, first_name, last_name, is_active) VALUES (?, ?, ?, ?, ?, ?)";
                    self::query($sql, ['admin', 'admin@example.com', $passwordHash, 'Admin', 'User', 1]);
                    $adminUserId = self::lastInsertId();
                    
                    // Add user_id column with default value
                    $sql = "ALTER TABLE `$tableName` ADD COLUMN `user_id` INT NOT NULL DEFAULT ?";
                    self::query($sql, [$adminUserId]);
                    
                    // Update all existing records to use the admin user
                    $sql = "UPDATE `$tableName` SET user_id = ? WHERE user_id = ?";
                    self::query($sql, [$adminUserId, $adminUserId]);
                    
                    return [
                        'success' => true,
                        'message' => "Added user_id column, created admin user, and assigned $itemCount items",
                        'added_columns' => ['user_id']
                    ];
                }
            } else {
                // Table is empty - safe to add column
                $sql = "ALTER TABLE `$tableName` ADD COLUMN `user_id` INT NOT NULL";
                self::query($sql);
                
                return [
                    'success' => true,
                    'message' => 'Added user_id column to empty table',
                    'added_columns' => ['user_id']
                ];
            }
            
        } catch (\Exception $e) {
            error_log("Error adding user_id column to $tableName: " . $e->getMessage());
            return [
                'success' => false, 
                'error' => "Could not add user_id column: " . $e->getMessage()
            ];
        }
    }
} 