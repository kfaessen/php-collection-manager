<?php
namespace CollectionManager;

use CollectionManager\Environment;

class Database 
{
    private static $connection = null;
    private static $initialized = false;
    private static $currentVersion = 2; // Huidige database versie
    
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
            // Check if this is a "table doesn't exist" error
            if (strpos($e->getMessage(), "doesn't exist") !== false || 
                strpos($e->getMessage(), "Unknown table") !== false ||
                strpos($e->getMessage(), "Table") !== false && strpos($e->getMessage(), "doesn't exist") !== false) {
                
                // Try to automatically setup the database
                try {
                    self::autoSetup();
                    // Retry connection after setup
                    self::init();
                    return;
                } catch (\Exception $setupError) {
                    // If auto-setup fails, show setup instructions
                    if (Environment::isDevelopment()) {
                        die("Database connection failed: " . $e->getMessage() . "\n\nAuto-setup failed: " . $setupError->getMessage() . "\n\nPlease visit: /public/setup.php?token=setup_" . date('Ymd'));
                    } else {
                        error_log("Database connection failed: " . $e->getMessage());
                        error_log("Auto-setup failed: " . $setupError->getMessage());
                        die("Database connection failed. Please visit: /public/setup.php?token=setup_" . date('Ymd') . " to setup the database.");
                    }
                }
            }
            
            // For other database errors, show normal error
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
            CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('database_migrations') . "` (
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
            $sql = "SELECT MAX(version) as current_version FROM " . Environment::getTableName('database_migrations');
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
                'name' => 'Initial database setup with all tables',
                'sql' => [
                    // Users table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('users') . "` (
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
                        email_verified BOOLEAN DEFAULT FALSE,
                        email_verification_token VARCHAR(64) NULL,
                        email_verification_expires TIMESTAMP NULL,
                        avatar_url VARCHAR(500) NULL,
                        registration_method ENUM('local', 'google', 'facebook') DEFAULT 'local',
                        preferred_language VARCHAR(10) DEFAULT 'nl',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_username (username),
                        INDEX idx_email (email),
                        INDEX idx_active (is_active),
                        INDEX idx_email_verified (email_verified),
                        INDEX idx_email_verification_token (email_verification_token)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Groups table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('groups') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(50) UNIQUE NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Permissions table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('permissions') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(50) UNIQUE NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // User groups table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('user_groups') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        group_id INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_user_group (user_id, group_id),
                        FOREIGN KEY (user_id) REFERENCES `" . Environment::getTableName('users') . "`(id) ON DELETE CASCADE,
                        FOREIGN KEY (group_id) REFERENCES `" . Environment::getTableName('groups') . "`(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Group permissions table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('group_permissions') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        group_id INT NOT NULL,
                        permission_id INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_group_permission (group_id, permission_id),
                        FOREIGN KEY (group_id) REFERENCES `" . Environment::getTableName('groups') . "`(id) ON DELETE CASCADE,
                        FOREIGN KEY (permission_id) REFERENCES `" . Environment::getTableName('permissions') . "`(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Sessions table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('sessions') . "` (
                        id VARCHAR(128) PRIMARY KEY,
                        user_id INT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        payload TEXT NOT NULL,
                        last_activity INT NOT NULL,
                        FOREIGN KEY (user_id) REFERENCES `" . Environment::getTableName('users') . "`(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Collection items table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('collection_items') . "` (
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
                        FOREIGN KEY (user_id) REFERENCES `" . Environment::getTableName('users') . "`(id) ON DELETE CASCADE,
                        INDEX idx_user_id (user_id),
                        INDEX idx_type (type),
                        INDEX idx_category (category),
                        INDEX idx_barcode (barcode)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Shared links table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('shared_links') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        item_id INT NOT NULL,
                        token VARCHAR(64) UNIQUE NOT NULL,
                        expires_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `" . Environment::getTableName('users') . "`(id) ON DELETE CASCADE,
                        INDEX idx_token (token)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Social logins table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('social_logins') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        provider VARCHAR(50) NOT NULL,
                        provider_id VARCHAR(255) NOT NULL,
                        provider_email VARCHAR(255),
                        provider_name VARCHAR(255),
                        provider_avatar VARCHAR(500),
                        access_token TEXT,
                        refresh_token TEXT,
                        expires_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_provider_user (provider, provider_id),
                        FOREIGN KEY (user_id) REFERENCES `" . Environment::getTableName('users') . "`(id) ON DELETE CASCADE,
                        INDEX idx_user_id (user_id),
                        INDEX idx_provider (provider),
                        INDEX idx_provider_id (provider_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // OAuth state sessions table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('oauth_states') . "` (
                        id VARCHAR(128) PRIMARY KEY,
                        provider VARCHAR(50) NOT NULL,
                        state_data TEXT,
                        expires_at TIMESTAMP NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_expires (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Languages table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('languages') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        code VARCHAR(10) UNIQUE NOT NULL,
                        name VARCHAR(100) NOT NULL,
                        native_name VARCHAR(100) NOT NULL,
                        is_rtl BOOLEAN DEFAULT FALSE,
                        is_active BOOLEAN DEFAULT TRUE,
                        sort_order INT DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_code (code),
                        INDEX idx_active (is_active)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Translation keys table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('translation_keys') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        key_name VARCHAR(255) UNIQUE NOT NULL,
                        description TEXT,
                        category VARCHAR(100) DEFAULT 'general',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_key_name (key_name),
                        INDEX idx_category (category)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Translations table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('translations') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        key_id INT NOT NULL,
                        language_code VARCHAR(10) NOT NULL,
                        translation TEXT NOT NULL,
                        is_completed BOOLEAN DEFAULT TRUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_key_language (key_id, language_code),
                        FOREIGN KEY (key_id) REFERENCES `" . Environment::getTableName('translation_keys') . "`(id) ON DELETE CASCADE,
                        FOREIGN KEY (language_code) REFERENCES `" . Environment::getTableName('languages') . "`(code) ON DELETE CASCADE,
                        INDEX idx_language (language_code),
                        INDEX idx_completed (is_completed)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // API Providers table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('api_providers') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(50) UNIQUE NOT NULL,
                        description TEXT,
                        base_url VARCHAR(255) NOT NULL,
                        requires_auth BOOLEAN DEFAULT FALSE,
                        rate_limit_per_minute INT DEFAULT 60,
                        is_active BOOLEAN DEFAULT TRUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_name (name),
                        INDEX idx_active (is_active)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // API Cache table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('api_cache') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        provider_id INT NOT NULL,
                        cache_key VARCHAR(255) NOT NULL,
                        request_url TEXT,
                        response_data LONGTEXT,
                        expires_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (provider_id) REFERENCES `" . Environment::getTableName('api_providers') . "`(id) ON DELETE CASCADE,
                        INDEX idx_provider_id (provider_id),
                        INDEX idx_cache_key (cache_key),
                        INDEX idx_expires (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Insert default languages
                    "INSERT IGNORE INTO `" . Environment::getTableName('languages') . "` (code, name, native_name, is_rtl, is_active, sort_order) VALUES 
                        ('nl', 'Dutch', 'Nederlands', 0, 1, 1),
                        ('en', 'English', 'English', 0, 1, 2),
                        ('de', 'German', 'Deutsch', 0, 0, 3),
                        ('fr', 'French', 'Français', 0, 0, 4),
                        ('es', 'Spanish', 'Español', 0, 0, 5),
                        ('ar', 'Arabic', 'العربية', 1, 0, 6),
                        ('he', 'Hebrew', 'עברית', 1, 0, 7)"
                ]
            ],
            2 => [
                'name' => 'Add notification system tables',
                'sql' => [
                    // Push subscriptions table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('push_subscriptions') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        endpoint TEXT NOT NULL,
                        p256dh_key TEXT NOT NULL,
                        auth_key TEXT NOT NULL,
                        user_agent TEXT,
                        is_active BOOLEAN DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `" . Environment::getTableName('users') . "`(id) ON DELETE CASCADE,
                        INDEX idx_user_active (user_id, is_active),
                        INDEX idx_endpoint (endpoint(255))
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Notification logs table
                    "CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('notification_logs') . "` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        title VARCHAR(255) NOT NULL,
                        body TEXT,
                        status ENUM('sent', 'failed', 'error') NOT NULL,
                        error_message TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `" . Environment::getTableName('users') . "`(id) ON DELETE CASCADE,
                        INDEX idx_user_date (user_id, created_at),
                        INDEX idx_status_date (status, created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Add notification preferences to users
                    "ALTER TABLE `" . Environment::getTableName('users') . "` ADD COLUMN `notifications_enabled` BOOLEAN DEFAULT TRUE",
                    "ALTER TABLE `" . Environment::getTableName('users') . "` ADD COLUMN `email_notifications` BOOLEAN DEFAULT TRUE",
                    "ALTER TABLE `" . Environment::getTableName('users') . "` ADD COLUMN `push_notifications` BOOLEAN DEFAULT TRUE"
                ]
            ]
        ];
    }
    
    /**
     * Execute migration
     */
    private static function executeMigration($version, $migration) 
    {
        try {
            // Check if migration already executed
            $sql = "SELECT COUNT(*) as count FROM " . Environment::getTableName('database_migrations') . " WHERE version = ?";
            $stmt = self::query($sql, [$version]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return; // Migration already executed
            }
            
            // Execute migration SQL
            if (isset($migration['sql']) && is_array($migration['sql'])) {
                foreach ($migration['sql'] as $sql) {
                    self::query($sql);
                }
            }
            
            // Record migration
            $sql = "INSERT INTO " . Environment::getTableName('database_migrations') . " (version, migration_name) VALUES (?, ?)";
            self::query($sql, [$version, $migration['name']]);
            
        } catch (\Exception $e) {
            error_log("Migration v$version failed: " . $e->getMessage());
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
     * Automatically setup database when tables don't exist
     */
    private static function autoSetup() 
    {
        try {
            // Connect to MySQL server without database
            $host = Environment::get('DB_HOST', 'localhost');
            $username = Environment::get('DB_USER', 'root');
            $password = Environment::get('DB_PASS', '');
            $charset = Environment::get('DB_CHARSET', 'utf8mb4');
            
            $dsn = "mysql:host=$host;charset=$charset";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new \PDO($dsn, $username, $password, $options);
            
            // Create database if it doesn't exist
            $dbname = Environment::get('DB_NAME', 'collection_manager');
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE {$charset}_unicode_ci");
            
            // Connect to the specific database
            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
            $pdo = new \PDO($dsn, $username, $password, $options);
            
            // Create migrations table first
            $sql = "
                CREATE TABLE IF NOT EXISTS `" . Environment::getTableName('database_migrations') . "` (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    version INT NOT NULL,
                    migration_name VARCHAR(255) NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_version (version)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $pdo->exec($sql);
            
            // Run all migrations to create tables
            $migrations = self::getMigrations();
            foreach ($migrations as $version => $migration) {
                if ($version <= self::$currentVersion) {
                    self::executeMigrationWithConnection($version, $migration, $pdo);
                }
            }
            
            error_log("Auto-setup completed successfully");
            
        } catch (\Exception $e) {
            error_log("Auto-setup failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Execute migration with provided connection
     */
    private static function executeMigrationWithConnection($version, $migration, $pdo) 
    {
        try {
            // Check if migration already executed
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM " . Environment::getTableName('database_migrations') . " WHERE version = ?");
            $stmt->execute([$version]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return; // Migration already executed
            }
            
            // Execute migration SQL
            if (isset($migration['sql']) && is_array($migration['sql'])) {
                foreach ($migration['sql'] as $sql) {
                    $pdo->exec($sql);
                }
            }
            
            // Record migration
            $stmt = $pdo->prepare("INSERT INTO " . Environment::getTableName('database_migrations') . " (version, migration_name) VALUES (?, ?)");
            $stmt->execute([$version, $migration['name']]);
            
        } catch (\Exception $e) {
            error_log("Migration v$version failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if database needs setup
     */
    public static function needsSetup() 
    {
        try {
            $currentVersion = self::getCurrentVersion();
            return $currentVersion < self::getTargetVersion();
        } catch (\Exception $e) {
            return true;
        }
    }
    
    /**
     * Get current database version (public method)
     */
    public static function getCurrentVersion() {
        try {
            if (!self::$initialized) {
                self::init();
            }
            return self::getCurrentDatabaseVersion();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get target database version (the version the code expects)
     */
    public static function getTargetVersion() {
        return self::$currentVersion;
    }
    
    /**
     * Get installed database version (alias for getCurrentVersion for clarity)
     */
    public static function getInstalledVersion() {
        return self::getCurrentVersion();
    }

    /**
     * Update database version
     */
    public static function updateVersion($version) {
        try {
            // This method is deprecated - versions are now tracked via migrations
            // Use executeMigration() instead for proper version tracking
            error_log("Warning: updateVersion() is deprecated. Use migrations instead.");
            return true;
        } catch (Exception $e) {
            error_log("Error updating database version: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Run database migrations
     */
    public static function migrate() {
        // This method is deprecated - migrations are now handled automatically
        // by checkAndRunMigrations() during database initialization
        error_log("Warning: migrate() is deprecated. Migrations are now automatic.");
        return true;
    }
} 