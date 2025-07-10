<?php
/**
 * Database Connection Handler
 * 
 * Beheert database connectie met omgevingsspecifieke prefixes
 */

require_once __DIR__ . '/env.php';

class Database 
{
    private static $pdo = null;
    private static $prefix = '';
    
    /**
     * Krijg database connectie
     */
    public static function getConnection() 
    {
        if (self::$pdo === null) {
            self::connect();
        }
        
        return self::$pdo;
    }
    
    /**
     * Maak database connectie
     */
    private static function connect() 
    {
        try {
            $host = Environment::get('DB_HOST', 'localhost');
            $dbname = Environment::get('DB_NAME');
            $username = Environment::get('DB_USER');
            $password = Environment::get('DB_PASS');
            
            if (!$dbname || !$username) {
                throw new Exception("Database configuratie ontbreekt in .env bestand");
            }
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            self::$pdo = new PDO($dsn, $username, $password, $options);
            self::$prefix = Environment::getDbPrefix();
            
        } catch (PDOException $e) {
            if (Environment::isDebug()) {
                throw new Exception("Database connectie mislukt: " . $e->getMessage());
            } else {
                throw new Exception("Database connectie mislukt");
            }
        }
    }
    
    /**
     * Haal de tabel prefix op
     */
    public static function getPrefix() 
    {
        if (self::$pdo === null) {
            self::connect();
        }
        return self::$prefix;
    }
    
    /**
     * Krijg volledige tabelnaam met prefix
     */
    public static function table($tableName) 
    {
        return self::getPrefix() . $tableName;
    }
    
    /**
     * Voer een query uit met automatische prefix
     */
    public static function query($sql, $params = []) 
    {
        $pdo = self::getConnection();
        
        // Vervang {prefix} placeholders in SQL
        $sql = str_replace('{prefix}', self::getPrefix(), $sql);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }
    
    /**
     * Haal één rij op
     */
    public static function fetchOne($sql, $params = []) 
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Haal alle rijen op
     */
    public static function fetchAll($sql, $params = []) 
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert en return laatste ID
     */
    public static function insert($sql, $params = []) 
    {
        self::query($sql, $params);
        return self::getConnection()->lastInsertId();
    }
    
    /**
     * Update/Delete en return aantal affected rows
     */
    public static function execute($sql, $params = []) 
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin transactie
     */
    public static function beginTransaction() 
    {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Commit transactie
     */
    public static function commit() 
    {
        return self::getConnection()->commit();
    }
    
    /**
     * Rollback transactie
     */
    public static function rollback() 
    {
        return self::getConnection()->rollback();
    }
    
    /**
     * Maak tabellen aan als ze niet bestaan
     */
    public static function createTables() 
    {
        $prefix = self::getPrefix();
        
        // Collectie items tabel
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            type ENUM('game', 'film', 'serie') NOT NULL,
            barcode VARCHAR(20),
            platform VARCHAR(100),
            director VARCHAR(100),
            publisher VARCHAR(100),
            description TEXT,
            cover_image VARCHAR(255),
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_barcode (barcode),
            UNIQUE KEY unique_barcode (barcode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        self::query($sql);
        
        // API cache tabel voor metadata
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}api_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            barcode VARCHAR(20) NOT NULL,
            api_source VARCHAR(20) NOT NULL,
            metadata JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            UNIQUE KEY unique_cache (barcode, api_source),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        self::query($sql);
    }
}
?> 