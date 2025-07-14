<?php
namespace CollectionManager;

use CollectionManager\Environment;

class Database 
{
    private static $connection = null;
    private static $initialized = false;
    private static $currentVersion = 9; // Huidige database versie
    
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
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
                        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Group permissions table
                    "CREATE TABLE IF NOT EXISTS `group_permissions` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        group_id INT NOT NULL,
                        permission_id INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_group_permission (group_id, permission_id),
                        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
                        FOREIGN KEY (permission_id) REFERENCES `permissions`(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Sessions table
                    "CREATE TABLE IF NOT EXISTS `sessions` (
                        id VARCHAR(128) PRIMARY KEY,
                        user_id INT NULL,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        payload TEXT NOT NULL,
                        last_activity INT NOT NULL,
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Shared links table
                    "CREATE TABLE IF NOT EXISTS `shared_links` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        item_id INT NOT NULL,
                        token VARCHAR(64) UNIQUE NOT NULL,
                        expires_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
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
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
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
            ],
            5 => [
                'name' => 'Add OAuth Social Login Support',
                'sql' => [
                    // Social logins table
                    "CREATE TABLE IF NOT EXISTS `social_logins` (
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
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
                        INDEX idx_user_id (user_id),
                        INDEX idx_provider (provider),
                        INDEX idx_provider_id (provider_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Add OAuth state sessions table for security
                    "CREATE TABLE IF NOT EXISTS `oauth_states` (
                        id VARCHAR(128) PRIMARY KEY,
                        provider VARCHAR(50) NOT NULL,
                        state_data TEXT,
                        expires_at TIMESTAMP NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_expires (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Add optional OAuth columns to users table
                    "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `avatar_url` VARCHAR(500) NULL",
                    "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email_verified` BOOLEAN DEFAULT FALSE",
                    "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `registration_method` ENUM('local', 'google', 'facebook') DEFAULT 'local'"
                ]
            ],
            6 => [
                'name' => 'Add Internationalization (i18n) Support',
                'sql' => [
                    // Languages table
                    "CREATE TABLE IF NOT EXISTS `languages` (
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
                    "CREATE TABLE IF NOT EXISTS `translation_keys` (
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
                    "CREATE TABLE IF NOT EXISTS `translations` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        key_id INT NOT NULL,
                        language_code VARCHAR(10) NOT NULL,
                        translation TEXT NOT NULL,
                        is_completed BOOLEAN DEFAULT TRUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_key_language (key_id, language_code),
                        FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
                        FOREIGN KEY (language_code) REFERENCES languages(code) ON DELETE CASCADE,
                        INDEX idx_language (language_code),
                        INDEX idx_completed (is_completed)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Add user language preference
                    "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `preferred_language` VARCHAR(10) DEFAULT 'nl'",
                    
                    // Insert default languages
                    "INSERT IGNORE INTO `languages` (code, name, native_name, is_rtl, is_active, sort_order) VALUES 
                        ('nl', 'Dutch', 'Nederlands', 0, 1, 1),
                        ('en', 'English', 'English', 0, 1, 2),
                        ('de', 'German', 'Deutsch', 0, 0, 3),
                        ('fr', 'French', 'Français', 0, 0, 4),
                        ('es', 'Spanish', 'Español', 0, 0, 5),
                        ('ar', 'Arabic', 'العربية', 1, 0, 6),
                        ('he', 'Hebrew', 'עברית', 1, 0, 7)"
                ]
            ],
            7 => [
                'name' => 'Add API Integration for Cover Images and Metadata',
                'sql' => [
                    // API Providers table
                    "CREATE TABLE IF NOT EXISTS `api_providers` (
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
                    
                    // API Cache table for storing API responses
                    "CREATE TABLE IF NOT EXISTS `api_cache` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        provider_id INT NOT NULL,
                        cache_key VARCHAR(255) NOT NULL,
                        request_url TEXT,
                        response_data LONGTEXT,
                        expires_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_provider_key (provider_id, cache_key),
                        FOREIGN KEY (provider_id) REFERENCES api_providers(id) ON DELETE CASCADE,
                        INDEX idx_cache_key (cache_key),
                        INDEX idx_expires (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Item Metadata table for storing enriched data
                    "CREATE TABLE IF NOT EXISTS `item_metadata` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        item_id INT NOT NULL,
                        provider_id INT,
                        external_id VARCHAR(255),
                        metadata_type ENUM('game', 'movie', 'tv_series', 'book', 'music') NOT NULL,
                        title VARCHAR(255),
                        description TEXT,
                        release_date DATE,
                        genre VARCHAR(255),
                        developer VARCHAR(255),
                        publisher VARCHAR(255),
                        director VARCHAR(255),
                        actors TEXT,
                        rating VARCHAR(10),
                        imdb_rating DECIMAL(3,1),
                        metacritic_score INT,
                        runtime_minutes INT,
                        language VARCHAR(10),
                        country VARCHAR(100),
                        platforms TEXT,
                        tags TEXT,
                        price_info TEXT,
                        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_item_provider (item_id, provider_id),
                        FOREIGN KEY (item_id) REFERENCES collection_items(id) ON DELETE CASCADE,
                        FOREIGN KEY (provider_id) REFERENCES api_providers(id) ON DELETE SET NULL,
                        INDEX idx_item_id (item_id),
                        INDEX idx_external_id (external_id),
                        INDEX idx_metadata_type (metadata_type)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Cover Images table for managing multiple image sizes
                    "CREATE TABLE IF NOT EXISTS `cover_images` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        item_id INT NOT NULL,
                        provider_id INT,
                        image_type ENUM('cover', 'poster', 'banner', 'screenshot', 'logo') DEFAULT 'cover',
                        size_type ENUM('thumb', 'small', 'medium', 'large', 'original') DEFAULT 'medium',
                        original_url TEXT,
                        local_path VARCHAR(500),
                        width INT,
                        height INT,
                        file_size INT,
                        mime_type VARCHAR(50),
                        is_primary BOOLEAN DEFAULT FALSE,
                        download_status ENUM('pending', 'downloading', 'completed', 'failed') DEFAULT 'pending',
                        download_attempts INT DEFAULT 0,
                        last_download_attempt TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (item_id) REFERENCES collection_items(id) ON DELETE CASCADE,
                        FOREIGN KEY (provider_id) REFERENCES api_providers(id) ON DELETE SET NULL,
                        INDEX idx_item_id (item_id),
                        INDEX idx_image_type (image_type),
                        INDEX idx_is_primary (is_primary),
                        INDEX idx_download_status (download_status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // API Rate Limiting table
                    "CREATE TABLE IF NOT EXISTS `api_rate_limits` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        provider_id INT NOT NULL,
                        ip_address VARCHAR(45),
                        request_count INT DEFAULT 1,
                        window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_provider_ip (provider_id, ip_address),
                        FOREIGN KEY (provider_id) REFERENCES api_providers(id) ON DELETE CASCADE,
                        INDEX idx_window_start (window_start)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Add new columns to collection_items for API integration
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `api_enriched` BOOLEAN DEFAULT FALSE",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `auto_cover_enabled` BOOLEAN DEFAULT TRUE",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `last_api_check` TIMESTAMP NULL",
                    "ALTER TABLE `collection_items` ADD COLUMN IF NOT EXISTS `api_match_confidence` DECIMAL(3,2) DEFAULT 0.00",
                    
                    // Insert default API providers
                    "INSERT IGNORE INTO `api_providers` (name, description, base_url, requires_auth, rate_limit_per_minute, is_active) VALUES 
                        ('IGDB', 'Internet Game Database - Game metadata and covers', 'https://api.igdb.com/v4/', 1, 30, 1),
                        ('OMDb', 'Open Movie Database - Movie and TV series metadata', 'http://www.omdbapi.com/', 1, 60, 1),
                        ('OpenLibrary', 'Open Library - Book metadata and covers', 'https://openlibrary.org/', 0, 100, 1),
                        ('TMDb', 'The Movie Database - Movie and TV metadata with high quality images', 'https://api.themoviedb.org/3/', 1, 40, 0),
                        ('Spotify', 'Spotify Web API - Music album metadata and covers', 'https://api.spotify.com/v1/', 1, 100, 0)"
                ]
            ],
            8 => [
                'name' => 'Add Push Notifications Support',
                'sql' => [
                    // Push subscriptions table
                    "CREATE TABLE IF NOT EXISTS `push_subscriptions` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        endpoint TEXT NOT NULL,
                        p256dh_key TEXT NOT NULL,
                        auth_key TEXT NOT NULL,
                        user_agent TEXT,
                        is_active BOOLEAN DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
                        INDEX idx_user_active (user_id, is_active),
                        INDEX idx_endpoint (endpoint(255))
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Notification logs table
                    "CREATE TABLE IF NOT EXISTS `notification_logs` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        title VARCHAR(255) NOT NULL,
                        body TEXT,
                        status ENUM('sent', 'failed', 'error') NOT NULL,
                        error_message TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
                        INDEX idx_user_date (user_id, created_at),
                        INDEX idx_status_date (status, created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Scheduled notifications table
                    "CREATE TABLE IF NOT EXISTS `scheduled_notifications` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        body TEXT,
                        data JSON,
                        options JSON,
                        send_at TIMESTAMP NOT NULL,
                        sent BOOLEAN DEFAULT 0,
                        sent_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
                        INDEX idx_send_at (send_at, sent),
                        INDEX idx_user_scheduled (user_id, send_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Notification preferences table
                    "CREATE TABLE IF NOT EXISTS `notification_preferences` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL UNIQUE,
                        item_added BOOLEAN DEFAULT 1,
                        item_updated BOOLEAN DEFAULT 1,
                        collection_shared BOOLEAN DEFAULT 1,
                        reminders BOOLEAN DEFAULT 1,
                        marketing BOOLEAN DEFAULT 0,
                        quiet_hours_start TIME DEFAULT '22:00:00',
                        quiet_hours_end TIME DEFAULT '08:00:00',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Insert notification translation keys
                    "INSERT IGNORE INTO translation_keys (key_name, description, category) VALUES
                        ('notification_permission_request', 'Request notification permission message', 'notifications'),
                        ('notification_permission_granted', 'Notification permission granted message', 'notifications'),
                        ('notification_permission_denied', 'Notification permission denied message', 'notifications'),
                        ('notification_subscribed', 'Successfully subscribed to notifications', 'notifications'),
                        ('notification_unsubscribed', 'Successfully unsubscribed from notifications', 'notifications'),
                        ('test_notification_title', 'Test notification title', 'notifications'),
                        ('test_notification_body', 'Test notification body', 'notifications'),
                        ('notification_item_added_title', 'Item added notification title', 'notifications'),
                        ('notification_item_added_body', 'Item added notification body', 'notifications'),
                        ('notification_item_updated_title', 'Item updated notification title', 'notifications'),
                        ('notification_item_updated_body', 'Item updated notification body', 'notifications'),
                        ('notification_collection_shared_title', 'Collection shared notification title', 'notifications'),
                        ('notification_collection_shared_body', 'Collection shared notification body', 'notifications'),
                        ('notification_reminder_title', 'Reminder notification title', 'notifications'),
                        ('notification_reminder_body', 'Reminder notification body', 'notifications'),
                        ('open_app', 'Open application', 'notifications'),
                        ('close', 'Close', 'notifications'),
                        ('enable_notifications', 'Enable notifications', 'notifications'),
                        ('disable_notifications', 'Disable notifications', 'notifications'),
                        ('notification_settings', 'Notification settings', 'notifications'),
                        ('quiet_hours', 'Quiet hours', 'notifications'),
                        ('notification_types', 'Notification types', 'notifications')
                    ",
                    
                    // Insert default translations
                    "INSERT IGNORE INTO translations (key_id, language_code, translation) VALUES
                        (1, 'nl', 'Wil je meldingen ontvangen voor nieuwe items en updates?'),
                        (2, 'nl', 'Meldingen zijn ingeschakeld'),
                        (3, 'nl', 'Meldingen zijn uitgeschakeld'),
                        (4, 'nl', 'Je ontvangt nu meldingen'),
                        (5, 'nl', 'Meldingen zijn uitgeschakeld'),
                        (6, 'nl', 'Test melding'),
                        (7, 'nl', 'Dit is een test melding van Collectiebeheer'),
                        (8, 'nl', 'Nieuw item toegevoegd'),
                        (9, 'nl', '{{item}} is toegevoegd aan je collectie'),
                        (10, 'nl', 'Item bijgewerkt'),
                        (11, 'nl', '{{item}} is bijgewerkt in je collectie'),
                        (12, 'nl', 'Collectie gedeeld'),
                        (13, 'nl', 'Je collectie is succesvol gedeeld'),
                        (14, 'nl', 'Herinnering'),
                        (15, 'nl', 'Vergeet niet {{item}} te bekijken'),
                        (16, 'nl', 'App openen'),
                        (17, 'nl', 'Sluiten'),
                        (18, 'nl', 'Meldingen inschakelen'),
                        (19, 'nl', 'Meldingen uitschakelen'),
                        (20, 'nl', 'Melding instellingen'),
                        (21, 'nl', 'Stille uren'),
                        (22, 'nl', 'Melding types')
                    "
                ]
            ],
            
            9 => [
                'name' => 'Email verification system',
                'sql' => [
                    // Email verification tokens table
                    "CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        token VARCHAR(128) UNIQUE NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        expires_at TIMESTAMP NOT NULL,
                        verified_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE,
                        INDEX idx_token (token),
                        INDEX idx_user_id (user_id),
                        INDEX idx_expires (expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                    
                    // Add email verification columns to users table
                    "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `email_verified_at` TIMESTAMP NULL AFTER `email_verified`",
                    "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `verification_reminder_sent` BOOLEAN DEFAULT FALSE AFTER `email_verified_at`",
                    
                    // Update existing users with email_verified = true to have verified_at timestamp
                    "UPDATE `users` SET `email_verified_at` = `created_at` WHERE `email_verified` = 1 AND `email_verified_at` IS NULL"
                ]
            ],
            
            10 => [
                'name' => 'Add system provider for API tokens',
                'sql' => [
                    // Insert system provider for storing API tokens
                    "INSERT IGNORE INTO `api_providers` (id, name, description, base_url, requires_auth, rate_limit_per_minute, is_active) VALUES 
                        (0, 'System', 'System provider for storing API tokens and system data', '', 0, 0, 1)",
                    
                    // Update api_cache table to allow provider_id 0 for system tokens
                    "ALTER TABLE `api_cache` MODIFY COLUMN `provider_id` INT NOT NULL DEFAULT 0",
                    
                    // Add comment to clarify the purpose of provider_id 0
                    "ALTER TABLE `api_cache` COMMENT = 'provider_id 0 is reserved for system tokens and data'"
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
            // Ensure we have a valid connection
            if (!$connection) {
                throw new \Exception("Database connection not available");
            }
            
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
            if ($transactionStarted && $connection && $connection->inTransaction()) {
                try {
                    $connection->rollBack();
                } catch (\Exception $rollbackException) {
                    error_log("Failed to rollback transaction: " . $rollbackException->getMessage());
                }
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