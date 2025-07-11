<?php
/**
 * Database Setup Script
 * This script creates the database and tables if they don't exist
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Database Setup Script\n";
echo "=====================\n\n";

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

// Step 1: Connect to MySQL server (without database)
try {
    $dsn = "mysql:host=" . $config['DB_HOST'] . ";charset=" . $config['DB_CHARSET'];
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to MySQL server\n";
} catch (PDOException $e) {
    die("✗ Failed to connect to MySQL server: " . $e->getMessage() . "\n");
}

// Step 2: Create database if it doesn't exist
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $config['DB_NAME'] . "` CHARACTER SET " . $config['DB_CHARSET'] . " COLLATE " . $config['DB_CHARSET'] . "_unicode_ci");
    echo "✓ Database '" . $config['DB_NAME'] . "' created or already exists\n";
} catch (PDOException $e) {
    die("✗ Failed to create database: " . $e->getMessage() . "\n");
}

// Step 3: Connect to the specific database
try {
    $dsn = "mysql:host=" . $config['DB_HOST'] . ";dbname=" . $config['DB_NAME'] . ";charset=" . $config['DB_CHARSET'];
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database '" . $config['DB_NAME'] . "'\n";
} catch (PDOException $e) {
    die("✗ Failed to connect to database: " . $e->getMessage() . "\n");
}

// Step 4: Create tables
echo "\nCreating tables...\n";

// Users table
try {
    $sql = "
        CREATE TABLE IF NOT EXISTS `users` (
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
    $pdo->exec($sql);
    echo "✓ Users table created\n";
} catch (PDOException $e) {
    echo "✗ Failed to create users table: " . $e->getMessage() . "\n";
}

// Groups table
try {
    $sql = "
        CREATE TABLE IF NOT EXISTS `groups` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
    echo "✓ Groups table created\n";
} catch (PDOException $e) {
    echo "✗ Failed to create groups table: " . $e->getMessage() . "\n";
}

// Permissions table
try {
    $sql = "
        CREATE TABLE IF NOT EXISTS `permissions` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
    echo "✓ Permissions table created\n";
} catch (PDOException $e) {
    echo "✗ Failed to create permissions table: " . $e->getMessage() . "\n";
}

// User groups table
try {
    $sql = "
        CREATE TABLE IF NOT EXISTS `user_groups` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            group_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_group (user_id, group_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
    echo "✓ User groups table created\n";
} catch (PDOException $e) {
    echo "✗ Failed to create user_groups table: " . $e->getMessage() . "\n";
}

// Group permissions table
try {
    $sql = "
        CREATE TABLE IF NOT EXISTS `group_permissions` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            permission_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_group_permission (group_id, permission_id),
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
    echo "✓ Group permissions table created\n";
} catch (PDOException $e) {
    echo "✗ Failed to create group_permissions table: " . $e->getMessage() . "\n";
}

// Collection items table
try {
    $sql = "
        CREATE TABLE IF NOT EXISTS `collection_items` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100),
            condition_rating INT DEFAULT 5,
            purchase_date DATE NULL,
            purchase_price DECIMAL(10,2) NULL,
            current_value DECIMAL(10,2) NULL,
            location VARCHAR(255),
            notes TEXT,
            image_path VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
    echo "✓ Collection items table created\n";
} catch (PDOException $e) {
    echo "✗ Failed to create collection_items table: " . $e->getMessage() . "\n";
}

// Sessions table
try {
    $sql = "
        CREATE TABLE IF NOT EXISTS `sessions` (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            payload TEXT NOT NULL,
            last_activity INT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
    echo "✓ Sessions table created\n";
} catch (PDOException $e) {
    echo "✗ Failed to create sessions table: " . $e->getMessage() . "\n";
}

// Shared links table
try {
    $sql = "
        CREATE TABLE IF NOT EXISTS `shared_links` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            item_id INT NOT NULL,
            token VARCHAR(64) UNIQUE NOT NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES collection_items(id) ON DELETE CASCADE,
            INDEX idx_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sql);
    echo "✓ Shared links table created\n";
} catch (PDOException $e) {
    echo "✗ Failed to create shared_links table: " . $e->getMessage() . "\n";
}

// Step 5: Insert default data
echo "\nInserting default data...\n";

// Insert default groups
try {
    $groups = [
        ['name' => 'admin', 'description' => 'Systeembeheerders'],
        ['name' => 'user', 'description' => 'Gewone gebruikers'],
        ['name' => 'moderator', 'description' => 'Moderators']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO groups (name, description) VALUES (?, ?)");
    foreach ($groups as $group) {
        $stmt->execute([$group['name'], $group['description']]);
    }
    echo "✓ Default groups inserted\n";
} catch (PDOException $e) {
    echo "✗ Failed to insert default groups: " . $e->getMessage() . "\n";
}

// Insert default permissions
try {
    $permissions = [
        ['name' => 'system_admin', 'description' => 'Systeembeheerder rechten'],
        ['name' => 'user_management', 'description' => 'Gebruikersbeheer'],
        ['name' => 'group_management', 'description' => 'Groepenbeheer'],
        ['name' => 'collection_view', 'description' => 'Collectie bekijken'],
        ['name' => 'collection_edit', 'description' => 'Collectie bewerken'],
        ['name' => 'collection_delete', 'description' => 'Collectie verwijderen']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)");
    foreach ($permissions as $permission) {
        $stmt->execute([$permission['name'], $permission['description']]);
    }
    echo "✓ Default permissions inserted\n";
} catch (PDOException $e) {
    echo "✗ Failed to insert default permissions: " . $e->getMessage() . "\n";
}

// Insert default group permissions
try {
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
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO group_permissions (group_id, permission_id) 
        SELECT g.id, p.id 
        FROM groups g, permissions p 
        WHERE g.name = ? AND p.name = ?
    ");
    
    foreach ($groupPermissions as $gp) {
        $stmt->execute([$gp['group'], $gp['permission']]);
    }
    echo "✓ Default group permissions inserted\n";
} catch (PDOException $e) {
    echo "✗ Failed to insert default group permissions: " . $e->getMessage() . "\n";
}

// Create default admin user if no users exist
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount == 0) {
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name, is_active) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', 'admin@example.com', $passwordHash, 'Admin', 'User', 1]);
        
        $userId = $pdo->lastInsertId();
        
        // Add admin user to admin group
        $stmt = $pdo->prepare("
            INSERT INTO user_groups (user_id, group_id) 
            SELECT ?, id FROM groups WHERE name = 'admin'
        ");
        $stmt->execute([$userId]);
        
        echo "✓ Default admin user created (username: admin, password: admin123)\n";
    } else {
        echo "✓ Users already exist, skipping default admin creation\n";
    }
} catch (PDOException $e) {
    echo "✗ Failed to create default admin user: " . $e->getMessage() . "\n";
}

echo "\nDatabase setup completed successfully!\n";
echo "You can now access the application.\n";
?>