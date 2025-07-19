<?php
/**
 * Server Status Check Script
 * This script checks the current server configuration and Laravel status
 */

echo "=== Server Status Check ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Path: " . __FILE__ . "\n";

// Check if Laravel files exist
$laravelFiles = [
    'artisan',
    'composer.json',
    'app/Http/Controllers/Controller.php',
    'config/app.php',
    '.env'
];

echo "\n=== Laravel Files Check ===\n";
foreach ($laravelFiles as $file) {
    $exists = file_exists($file);
    echo ($exists ? "✓" : "✗") . " $file\n";
}

// Check if .env exists and has database config
if (file_exists('.env')) {
    echo "\n=== .env Database Configuration ===\n";
    $envContent = file_get_contents('.env');
    $lines = explode("\n", $envContent);
    
    $dbConfig = [];
    foreach ($lines as $line) {
        if (strpos($line, 'DB_') === 0) {
            $dbConfig[] = trim($line);
        }
    }
    
    foreach ($dbConfig as $config) {
        echo "$config\n";
    }
} else {
    echo "\n✗ .env file not found\n";
}

// Check if we can connect to database
echo "\n=== Database Connection Test ===\n";
try {
    if (file_exists('.env')) {
        // Load .env manually for testing
        $envContent = file_get_contents('.env');
        $lines = explode("\n", $envContent);
        $env = [];
        
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }
        
        $host = $env['DB_HOST'] ?? 'localhost';
        $database = $env['DB_DATABASE'] ?? '';
        $username = $env['DB_USERNAME'] ?? '';
        $password = $env['DB_PASSWORD'] ?? '';
        
        echo "Host: $host\n";
        echo "Database: $database\n";
        echo "Username: $username\n";
        
        $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        echo "✓ Database connection successful!\n";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '$database'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Tables in database: " . $result['count'] . "\n";
        
    } else {
        echo "✗ Cannot test database connection - no .env file\n";
    }
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== End of Status Check ===\n";
?> 