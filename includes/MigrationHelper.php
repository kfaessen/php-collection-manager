<?php
namespace CollectionManager;

use CollectionManager\Environment;
use CollectionManager\Database;

/**
 * Migration Helper Class
 * Provides Entity Framework-like migration functionality for PHP
 */
class MigrationHelper 
{
    /**
     * Generate a new migration file template
     */
    public static function generateMigration($name, $description = '') 
    {
        $timestamp = date('YmdHis');
        $className = 'Migration_' . $timestamp . '_' . self::toCamelCase($name);
        $version = self::getNextVersion();
        
        $template = self::getMigrationTemplate($className, $name, $description, $version);
        
        $filename = "migration_{$version}_{$timestamp}_{$name}.php";
        $filepath = __DIR__ . '/../migrations/' . $filename;
        
        // Create migrations directory if it doesn't exist
        if (!is_dir(__DIR__ . '/../migrations/')) {
            mkdir(__DIR__ . '/../migrations/', 0755, true);
        }
        
        file_put_contents($filepath, $template);
        
        return [
            'version' => $version,
            'filename' => $filename,
            'filepath' => $filepath,
            'class' => $className
        ];
    }
    
    /**
     * Get the next migration version number
     */
    private static function getNextVersion() 
    {
        try {
            $currentVersion = Database::getCurrentVersion();
            return $currentVersion + 1;
        } catch (\Exception $e) {
            return 1;
        }
    }
    
    /**
     * Convert string to CamelCase
     */
    private static function toCamelCase($string) 
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
    }
    
    /**
     * Get migration template
     */
    private static function getMigrationTemplate($className, $name, $description, $version) 
    {
        return "<?php
/**
 * Migration: {$name}
 * Version: {$version}
 * Description: {$description}
 * Generated: " . date('Y-m-d H:i:s') . "
 */

namespace CollectionManager\\Migrations;

use CollectionManager\\Environment;

class {$className} 
{
    /**
     * Run the migration (UP)
     */
    public static function up() 
    {
        return [
            // Add your SQL statements here
            // Example:
            // \"CREATE TABLE IF NOT EXISTS `\" . Environment::getTableName('example') . \"` (
            //     id INT AUTO_INCREMENT PRIMARY KEY,
            //     name VARCHAR(255) NOT NULL,
            //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            // ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\",
            
            // \"ALTER TABLE `\" . Environment::getTableName('users') . \"` ADD COLUMN IF NOT EXISTS `example_field` VARCHAR(255) NULL\",
            
            // \"INSERT IGNORE INTO `\" . Environment::getTableName('example') . \"` (name) VALUES ('Example Data')\"
        ];
    }
    
    /**
     * Reverse the migration (DOWN) - Optional
     */
    public static function down() 
    {
        return [
            // Add your rollback SQL statements here
            // Example:
            // \"DROP TABLE IF EXISTS `\" . Environment::getTableName('example') . \"`\",
            // \"ALTER TABLE `\" . Environment::getTableName('users') . \"` DROP COLUMN IF EXISTS `example_field`\"
        ];
    }
}
";
    }
    
    /**
     * Run pending migrations
     */
    public static function runPendingMigrations() 
    {
        $currentVersion = Database::getCurrentVersion();
        $targetVersion = Database::getTargetVersion();
        
        if ($currentVersion >= $targetVersion) {
            return ['status' => 'success', 'message' => 'No pending migrations'];
        }
        
        $executedMigrations = [];
        
        // Load and execute migration files
        $migrationFiles = self::getMigrationFiles();
        
        foreach ($migrationFiles as $file) {
            $version = self::extractVersionFromFilename($file);
            
            if ($version > $currentVersion && $version <= $targetVersion) {
                try {
                    $result = self::executeMigrationFile($file, $version);
                    $executedMigrations[] = $result;
                } catch (\Exception $e) {
                    return [
                        'status' => 'error',
                        'message' => 'Migration failed: ' . $e->getMessage(),
                        'failed_migration' => $file,
                        'executed_migrations' => $executedMigrations
                    ];
                }
            }
        }
        
        return [
            'status' => 'success',
            'message' => 'All migrations executed successfully',
            'executed_migrations' => $executedMigrations
        ];
    }
    
    /**
     * Get all migration files
     */
    private static function getMigrationFiles() 
    {
        $migrationDir = __DIR__ . '/../migrations/';
        
        if (!is_dir($migrationDir)) {
            return [];
        }
        
        $files = glob($migrationDir . 'migration_*.php');
        sort($files);
        
        return $files;
    }
    
    /**
     * Extract version number from filename
     */
    private static function extractVersionFromFilename($filename) 
    {
        if (preg_match('/migration_(\d+)_/', basename($filename), $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
    
    /**
     * Execute a migration file
     */
    private static function executeMigrationFile($filepath, $version) 
    {
        require_once $filepath;
        
        $filename = basename($filepath);
        $className = self::getClassNameFromFilename($filename);
        $fullClassName = "CollectionManager\\Migrations\\{$className}";
        
        if (!class_exists($fullClassName)) {
            throw new \Exception("Migration class {$fullClassName} not found in {$filename}");
        }
        
        if (!method_exists($fullClassName, 'up')) {
            throw new \Exception("Migration class {$fullClassName} must have an 'up' method");
        }
        
        // Get SQL statements from migration
        $sqlStatements = $fullClassName::up();
        
        if (!is_array($sqlStatements)) {
            throw new \Exception("Migration 'up' method must return an array of SQL statements");
        }
        
        // Execute SQL statements
        foreach ($sqlStatements as $sql) {
            if (!empty(trim($sql))) {
                Database::query($sql);
            }
        }
        
        // Record migration in database
        $migrationName = self::getMigrationNameFromFilename($filename);
        $sql = "INSERT INTO " . Environment::getTableName('database_migrations') . " (version, migration_name) VALUES (?, ?)";
        Database::query($sql, [$version, $migrationName]);
        
        return [
            'version' => $version,
            'name' => $migrationName,
            'file' => $filename,
            'executed_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get class name from filename
     */
    private static function getClassNameFromFilename($filename) 
    {
        if (preg_match('/migration_(\d+)_(\d+)_(.+)\.php$/', $filename, $matches)) {
            $version = $matches[1];
            $timestamp = $matches[2];
            $name = $matches[3];
            return 'Migration_' . $timestamp . '_' . self::toCamelCase($name);
        }
        return '';
    }
    
    /**
     * Get migration name from filename
     */
    private static function getMigrationNameFromFilename($filename) 
    {
        if (preg_match('/migration_\d+_\d+_(.+)\.php$/', $filename, $matches)) {
            return str_replace('_', ' ', $matches[1]);
        }
        return $filename;
    }
    
    /**
     * Get migration status
     */
    public static function getStatus() 
    {
        $currentVersion = Database::getCurrentVersion();
        $targetVersion = Database::getTargetVersion();
        
        $executedMigrations = self::getExecutedMigrations();
        $pendingMigrations = self::getPendingMigrations();
        
        return [
            'current_version' => $currentVersion,
            'target_version' => $targetVersion,
            'needs_migration' => $currentVersion < $targetVersion,
            'executed_migrations' => $executedMigrations,
            'pending_migrations' => $pendingMigrations
        ];
    }
    
    /**
     * Get executed migrations
     */
    private static function getExecutedMigrations() 
    {
        try {
            $sql = "SELECT * FROM " . Environment::getTableName('database_migrations') . " ORDER BY version ASC";
            $stmt = Database::query($sql);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get pending migrations
     */
    private static function getPendingMigrations() 
    {
        $currentVersion = Database::getCurrentVersion();
        $targetVersion = Database::getTargetVersion();
        
        $pending = [];
        
        for ($version = $currentVersion + 1; $version <= $targetVersion; $version++) {
            $pending[] = [
                'version' => $version,
                'status' => 'pending'
            ];
        }
        
        return $pending;
    }
    
    /**
     * Rollback to specific version (if down methods are implemented)
     */
    public static function rollback($toVersion) 
    {
        $currentVersion = Database::getCurrentVersion();
        
        if ($toVersion >= $currentVersion) {
            return ['status' => 'error', 'message' => 'Cannot rollback to higher or same version'];
        }
        
        $rolledBack = [];
        
        // Execute rollbacks in reverse order
        for ($version = $currentVersion; $version > $toVersion; $version--) {
            $migrationFile = self::findMigrationFile($version);
            
            if ($migrationFile) {
                try {
                    $result = self::executeRollback($migrationFile, $version);
                    $rolledBack[] = $result;
                } catch (\Exception $e) {
                    return [
                        'status' => 'error',
                        'message' => 'Rollback failed: ' . $e->getMessage(),
                        'failed_version' => $version,
                        'rolled_back' => $rolledBack
                    ];
                }
            }
        }
        
        return [
            'status' => 'success',
            'message' => 'Rollback completed successfully',
            'rolled_back' => $rolledBack
        ];
    }
    
    /**
     * Find migration file by version
     */
    private static function findMigrationFile($version) 
    {
        $migrationFiles = self::getMigrationFiles();
        
        foreach ($migrationFiles as $file) {
            if (self::extractVersionFromFilename($file) === $version) {
                return $file;
            }
        }
        
        return null;
    }
    
    /**
     * Execute rollback for a migration
     */
    private static function executeRollback($filepath, $version) 
    {
        require_once $filepath;
        
        $filename = basename($filepath);
        $className = self::getClassNameFromFilename($filename);
        $fullClassName = "CollectionManager\\Migrations\\{$className}";
        
        if (!method_exists($fullClassName, 'down')) {
            throw new \Exception("Migration class {$fullClassName} does not support rollback (no 'down' method)");
        }
        
        // Get rollback SQL statements
        $sqlStatements = $fullClassName::down();
        
        if (!is_array($sqlStatements)) {
            throw new \Exception("Migration 'down' method must return an array of SQL statements");
        }
        
        // Execute rollback SQL statements
        foreach ($sqlStatements as $sql) {
            if (!empty(trim($sql))) {
                Database::query($sql);
            }
        }
        
        // Remove migration record from database
        $sql = "DELETE FROM " . Environment::getTableName('database_migrations') . " WHERE version = ?";
        Database::query($sql, [$version]);
        
        return [
            'version' => $version,
            'name' => self::getMigrationNameFromFilename($filename),
            'file' => $filename,
            'rolled_back_at' => date('Y-m-d H:i:s')
        ];
    }
} 