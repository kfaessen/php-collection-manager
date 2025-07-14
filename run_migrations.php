<?php
/**
 * Database Migration Runner
 * This script runs database migrations and inserts default data
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Safely escape database identifiers (database names, table names, column names)
 * This prevents SQL injection in DDL statements where parameterized queries are not available
 */
function escapeIdentifier($identifier) {
    // Remove any backticks and escape them properly
    $identifier = str_replace('`', '', $identifier);
    
    // Only allow alphanumeric characters, underscores, and hyphens
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $identifier)) {
        throw new InvalidArgumentException('Invalid database identifier: ' . $identifier);
    }
    
    return '`' . $identifier . '`';
}

/**
 * Safely escape charset names
 * This prevents SQL injection in charset specifications
 */
function escapeCharset($charset) {
    // Remove any quotes and escape them properly
    $charset = str_replace(['"', "'"], '', $charset);
    
    // Only allow alphanumeric characters and underscores
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
        throw new InvalidArgumentException('Invalid charset name: ' . $charset);
    }
    
    return $charset;
}

echo "Database Migration Runner\n";
echo "========================\n\n";

try {
    // Include the functions file
    require_once 'includes/functions.php';
    echo "✓ Functions loaded successfully\n";
    
    // Load environment configuration
    $envFile = __DIR__ . '/.env';
    $config = [
        'DB_HOST' => 'localhost',
        'DB_NAME' => 'collection_manager',
        'DB_USER' => 'root',
        'DB_PASS' => '',
        'DB_CHARSET' => 'utf8mb4'
    ];

    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                list($key, $value) = explode('=', $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
    }

    echo "Configuration loaded:\n";
    echo "- Host: " . $config['DB_HOST'] . "\n";
    echo "- Database: " . $config['DB_NAME'] . "\n";
    echo "- User: " . $config['DB_USER'] . "\n";
    echo "- Charset: " . $config['DB_CHARSET'] . "\n\n";

    // Step 1: Check if database exists, if not create it
    try {
        // Safely escape configuration values
        $safeDbName = escapeIdentifier($config['DB_NAME']);
        $safeCharset = escapeCharset($config['DB_CHARSET']);
        
        // Connect to MySQL server (without database)
        $dsn = "mysql:host=" . $config['DB_HOST'] . ";charset=" . $safeCharset;
        $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ Connected to MySQL server\n";
        
        // Check if database exists using prepared statement
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$config['DB_NAME']]);
        if ($stmt->rowCount() == 0) {
            echo "Database does not exist, creating it...\n";
            // Use safely escaped identifiers for CREATE DATABASE
            $createDbSql = "CREATE DATABASE IF NOT EXISTS $safeDbName CHARACTER SET $safeCharset COLLATE {$safeCharset}_unicode_ci";
            $pdo->exec($createDbSql);
            echo "✓ Database '" . $config['DB_NAME'] . "' created\n";
        } else {
            echo "✓ Database '" . $config['DB_NAME'] . "' already exists\n";
        }
    } catch (PDOException $e) {
        die("✗ Failed to connect to MySQL server or create database: " . $e->getMessage() . "\n");
    } catch (InvalidArgumentException $e) {
        die("✗ Invalid configuration: " . $e->getMessage() . "\n");
    }
    
    // Initialize database (this will run migrations automatically)
    echo "\nInitializing database...\n";
    Database::init();
    echo "✓ Database initialized\n";
    
    // Get version information
    $installedVersion = Database::getCurrentVersion();
    $targetVersion = Database::getTargetVersion();
    
    echo "\nVersion Information:\n";
    echo "- Installed version: $installedVersion\n";
    echo "- Target version: $targetVersion\n";
    
    if ($installedVersion >= $targetVersion) {
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
    echo "You can also run setup_database.php to create the database and tables.\n";
    exit(1);
}
?> 