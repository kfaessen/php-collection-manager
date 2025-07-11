<?php
namespace CollectionManager;

class Environment 
{
    private static $config = [];
    
    /**
     * Initialize environment configuration
     */
    public static function init() 
    {
        // Default configuration
        self::$config = [
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'collection_manager',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'DB_PREFIX' => '',
            'DB_CHARSET' => 'utf8mb4',
            'UPLOAD_DIR' => __DIR__ . '/../uploads/',
            'MAX_FILE_SIZE' => 5 * 1024 * 1024, // 5MB
            'ALLOWED_EXTENSIONS' => ['jpg', 'jpeg', 'png', 'gif'],
            'API_TIMEOUT' => 30,
            'ITEMS_PER_PAGE' => 12,
            'APP_ENV' => 'production',
            'SMTP_HOST' => '',
            'SMTP_PORT' => 587,
            'SMTP_USER' => '',
            'SMTP_PASS' => '',
            'SMTP_SECURE' => 'tls',
            'SMTP_FROM' => '',
            'SMTP_FROM_NAME' => 'Collectiebeheer',
            'TOTP_ISSUER' => 'Collectiebeheer',
            'TOTP_WINDOW' => 1,
            'TOTP_BACKUP_CODES_COUNT' => 10
        ];
        
        // Load .env file if exists
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    self::$config[trim($key)] = trim($value);
                }
            }
        }
        
        // Auto-set prefix based on environment if not explicitly set
        if (empty(self::$config['DB_PREFIX'])) {
            $env = self::$config['APP_ENV'];
            $prefixMap = [
                'development' => 'dev_',
                'test' => 'tst_',
                'acceptance' => 'acc_',
                'production' => 'prd_'
            ];
            
            if (isset($prefixMap[$env])) {
                self::$config['DB_PREFIX'] = $prefixMap[$env];
            }
        }
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null) 
    {
        if (empty(self::$config)) {
            self::init();
        }
        
        return isset(self::$config[$key]) ? self::$config[$key] : $default;
    }
    
    /**
     * Set configuration value
     */
    public static function set($key, $value) 
    {
        self::$config[$key] = $value;
    }
    
    /**
     * Check if we're in development mode
     */
    public static function isDevelopment() 
    {
        return self::get('APP_ENV', 'production') === 'development';
    }
    
    /**
     * Get table name with prefix
     */
    public static function getTableName($tableName) 
    {
        $prefix = self::get('DB_PREFIX', '');
        return $prefix . $tableName;
    }
} 