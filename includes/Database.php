<?php
namespace CollectionManager;

use CollectionManager\Environment;

class Database 
{
    private static $connection = null;
    private static $initialized = false;
    
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
            
            // Create tables if they don't exist
            self::createTables();
            
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
     * Create necessary tables
     */
    private static function createTables() 
    {
        // Create users table first (no dependencies)
        self::createUsersTable();
        
        // Create groups table (no dependencies)
        self::createGroupsTable();
        
        // Create permissions table (no dependencies)
        self::createPermissionsTable();
        
        // Create user_groups table (depends on users and groups)
        self::createUserGroupsTable();
        
        // Create group_permissions table (depends on groups and permissions)
        self::createGroupPermissionsTable();
        
        // Create collection_items table (depends on users)
        self::createCollectionItemsTable();
        
        // Create sessions table (depends on users)
        self::createSessionsTable();

        // Create shared_links table (depends on users)
        self::createSharedLinksTable();
    }
    
    /**
     * Create users table
     */
    private static function createUsersTable() 
    {
        $tableName = Environment::getTableName('users');
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        self::query($sql);
        
        // Add TOTP columns to existing table if they don't exist
        self::addTOTPColumnsIfNotExist($tableName);
    }
    
    /**
     * Create groups table
     */
    private static function createGroupsTable() 
    {
        $tableName = Environment::getTableName('groups');
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) UNIQUE NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        self::query($sql);
        
        // Insert default groups
        self::insertDefaultGroups();
    }
    
    /**
     * Create permissions table
     */
    private static function createPermissionsTable() 
    {
        $tableName = Environment::getTableName('permissions');
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) UNIQUE NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        self::query($sql);
        
        // Insert default permissions
        self::insertDefaultPermissions();
    }
    
    /**
     * Create user_groups table
     */
    private static function createUserGroupsTable() 
    {
        $tableName = Environment::getTableName('user_groups');
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                group_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES " . Environment::getTableName('users') . "(id) ON DELETE CASCADE,
                FOREIGN KEY (group_id) REFERENCES " . Environment::getTableName('groups') . "(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_group (user_id, group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        self::query($sql);
    }
    
    /**
     * Create group_permissions table
     */
    private static function createGroupPermissionsTable() 
    {
        $tableName = Environment::getTableName('group_permissions');
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id INT NOT NULL,
                permission_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (group_id) REFERENCES " . Environment::getTableName('groups') . "(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES " . Environment::getTableName('permissions') . "(id) ON DELETE CASCADE,
                UNIQUE KEY unique_group_permission (group_id, permission_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        self::query($sql);
        
        // Insert default group permissions
        self::insertDefaultGroupPermissions();
    }
    
    /**
     * Create collection_items table (with user_id)
     */
    private static function createCollectionItemsTable() 
    {
        $tableName = Environment::getTableName('collection_items');
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                type ENUM('game', 'film', 'serie') NOT NULL,
                barcode VARCHAR(13),
                platform VARCHAR(100),
                director VARCHAR(255),
                publisher VARCHAR(255),
                description TEXT,
                cover_image VARCHAR(500),
                metadata JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES " . Environment::getTableName('users') . "(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_type (type),
                INDEX idx_barcode (barcode),
                INDEX idx_created_at (created_at),
                INDEX idx_user_barcode (user_id, barcode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        self::query($sql);
    }
    
    /**
     * Create sessions table
     */
    private static function createSessionsTable() 
    {
        $tableName = Environment::getTableName('sessions');
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id VARCHAR(128) PRIMARY KEY,
                user_id INT,
                payload TEXT NOT NULL,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES " . Environment::getTableName('users') . "(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_last_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        self::query($sql);
    }

    /**
     * Create shared_links table
     */
    private static function createSharedLinksTable() 
    {
        $tableName = Environment::getTableName('shared_links');
        $sql = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) UNIQUE NOT NULL,
                expires_at DATETIME NOT NULL,
                revoked_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES " . Environment::getTableName('users') . "(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_token (token),
                INDEX idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        self::query($sql);
    }
    
    /**
     * Insert default groups
     */
    private static function insertDefaultGroups() 
    {
        $tableName = Environment::getTableName('groups');
        
        $groups = [
            ['admin', 'Administrators - Full system access'],
            ['user', 'Regular Users - Can manage their own collection'],
            ['moderator', 'Moderators - Can view all collections and help users']
        ];
        
        foreach ($groups as $group) {
            $sql = "INSERT IGNORE INTO `$tableName` (name, description) VALUES (?, ?)";
            self::query($sql, $group);
        }
    }
    
    /**
     * Insert default permissions
     */
    private static function insertDefaultPermissions() 
    {
        $tableName = Environment::getTableName('permissions');
        
        $permissions = [
            ['manage_users', 'Create, edit, and delete users'],
            ['manage_groups', 'Create, edit, and delete groups'],
            ['manage_permissions', 'Assign and revoke permissions'],
            ['view_all_collections', 'View all user collections'],
            ['manage_own_collection', 'Manage own collection items'],
            ['manage_all_collections', 'Manage all user collections'],
            ['system_admin', 'Full system administration access']
        ];
        
        foreach ($permissions as $permission) {
            $sql = "INSERT IGNORE INTO `$tableName` (name, description) VALUES (?, ?)";
            self::query($sql, $permission);
        }
    }
    
    /**
     * Insert default group permissions
     */
    private static function insertDefaultGroupPermissions() 
    {
        $groupsTable = Environment::getTableName('groups');
        $permissionsTable = Environment::getTableName('permissions');
        $groupPermissionsTable = Environment::getTableName('group_permissions');
        
        // Admin group gets all permissions
        $sql = "INSERT IGNORE INTO `$groupPermissionsTable` (group_id, permission_id) 
                SELECT g.id, p.id FROM `$groupsTable` g, `$permissionsTable` p 
                WHERE g.name = 'admin'";
        self::query($sql);
        
        // User group gets basic permissions
        $sql = "INSERT IGNORE INTO `$groupPermissionsTable` (group_id, permission_id) 
                SELECT g.id, p.id FROM `$groupsTable` g, `$permissionsTable` p 
                WHERE g.name = 'user' AND p.name = 'manage_own_collection'";
        self::query($sql);
        
        // Moderator group gets view and help permissions
        $sql = "INSERT IGNORE INTO `$groupPermissionsTable` (group_id, permission_id) 
                SELECT g.id, p.id FROM `$groupsTable` g, `$permissionsTable` p 
                WHERE g.name = 'moderator' AND p.name IN ('view_all_collections', 'manage_own_collection')";
        self::query($sql);
    }
    
    /**
     * Add TOTP columns to existing users table if they don't exist
     */
    private static function addTOTPColumnsIfNotExist($tableName) 
    {
        try {
            // Check if totp_secret column exists
            $sql = "SHOW COLUMNS FROM `$tableName` LIKE 'totp_secret'";
            $stmt = self::query($sql);
            if ($stmt->rowCount() == 0) {
                // Add TOTP columns
                $sql = "ALTER TABLE `$tableName` 
                        ADD COLUMN totp_secret VARCHAR(32) NULL AFTER locked_until,
                        ADD COLUMN totp_enabled BOOLEAN DEFAULT FALSE AFTER totp_secret,
                        ADD COLUMN totp_backup_codes TEXT NULL AFTER totp_enabled";
                self::query($sql);
            }
        } catch (\Exception $e) {
            // Column might already exist, ignore error
            error_log("TOTP column check failed: " . $e->getMessage());
        }
    }
    
    /**
     * Check if setup is needed (no users exist)
     */
    public static function needsSetup() 
    {
        $tableName = Environment::getTableName('users');
        $sql = "SELECT COUNT(*) as count FROM `$tableName`";
        $stmt = self::query($sql);
        $result = $stmt->fetch();
        return $result['count'] == 0;
    }
} 