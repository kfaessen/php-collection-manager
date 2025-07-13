<?php
/**
 * Database Connection Test
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

// Load environment configuration
$envFile = __DIR__ . '/../.env';
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

echo "<h2>Configuration:</h2>";
echo "<ul>";
echo "<li>Host: " . htmlspecialchars($config['DB_HOST']) . "</li>";
echo "<li>Database: " . htmlspecialchars($config['DB_NAME']) . "</li>";
echo "<li>User: " . htmlspecialchars($config['DB_USER']) . "</li>";
echo "<li>Charset: " . htmlspecialchars($config['DB_CHARSET']) . "</li>";
echo "</ul>";

// Test 1: Connect to MySQL server (without database)
echo "<h2>Test 1: MySQL Server Connection</h2>";
try {
    $dsn = "mysql:host=" . $config['DB_HOST'] . ";charset=" . $config['DB_CHARSET'];
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Connected to MySQL server successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Failed to connect to MySQL server: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Check if database exists
echo "<h2>Test 2: Database Existence</h2>";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . $config['DB_NAME'] . "'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Database '" . htmlspecialchars($config['DB_NAME']) . "' exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Database '" . htmlspecialchars($config['DB_NAME']) . "' does not exist</p>";
        echo "<p>Run setup_database.php to create it.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error checking database: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Connect to specific database
echo "<h2>Test 3: Database Connection</h2>";
try {
    $dsn = "mysql:host=" . $config['DB_HOST'] . ";dbname=" . $config['DB_NAME'] . ";charset=" . $config['DB_CHARSET'];
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Connected to database '" . htmlspecialchars($config['DB_NAME']) . "' successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Failed to connect to database: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 4: Check if tables exist
echo "<h2>Test 4: Table Existence</h2>";
$requiredTables = ['users', 'groups', 'permissions', 'user_groups', 'group_permissions', 'collection_items', 'sessions', 'shared_links'];
$existingTables = [];

try {
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch()) {
        $existingTables[] = $row[0];
    }
    
    echo "<ul>";
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "<li style='color: green;'>✓ Table '$table' exists</li>";
        } else {
            echo "<li style='color: red;'>✗ Table '$table' missing</li>";
        }
    }
    echo "</ul>";
    
    if (count(array_diff($requiredTables, $existingTables)) > 0) {
        echo "<p style='color: orange;'>⚠ Some tables are missing. Run setup_database.php to create them.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error checking tables: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Check if users exist
echo "<h2>Test 5: User Data</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "<p>Total users: $userCount</p>";
    
    if ($userCount > 0) {
        $stmt = $pdo->query("SELECT username, email, first_name, last_name FROM users LIMIT 5");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Username</th><th>Email</th><th>Name</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No users found. Run setup_database.php to create default admin user.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error checking users: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If any tests failed, run <code>php setup_database.php</code> from the root directory</li>";
echo "<li>If all tests pass, try accessing <a href='profile.php'>profile.php</a> and <a href='admin.php'>admin.php</a></li>";
echo "<li>If you still get errors, check the debug files: <a href='debug_profile.php'>debug_profile.php</a> and <a href='debug_admin.php'>debug_admin.php</a></li>";
echo "</ol>";
?>