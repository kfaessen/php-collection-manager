<?php
/**
 * Server Diagnostics Script voor Collection Manager
 * 
 * Dit script helpt bij het diagnosticeren van server configuratie problemen
 * die kunnen voorkomen bij de setup wizard.
 */

echo "🔍 Collection Manager - Server Diagnostics\n";
echo "==========================================\n\n";

// Check PHP version
echo "📋 PHP Informatie:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n\n";

// Check required PHP extensions
echo "🔧 PHP Extensies:\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json'];
foreach ($requiredExtensions as $ext) {
    $status = extension_loaded($ext) ? "✅" : "❌";
    echo "$status $ext\n";
}
echo "\n";

// Check file permissions
echo "📁 Bestandsrechten:\n";
$paths = [
    '.env' => 'Environment configuratie',
    'storage/' => 'Storage directory',
    'storage/app/' => 'App storage',
    'storage/framework/' => 'Framework storage',
    'storage/logs/' => 'Logs directory',
    'bootstrap/cache/' => 'Bootstrap cache',
];

foreach ($paths as $path => $description) {
    if (file_exists($path)) {
        $readable = is_readable($path) ? "✅" : "❌";
        $writable = is_writable($path) ? "✅" : "❌";
        echo "$readable$writable $path ($description)\n";
    } else {
        echo "❌❌ $path ($description) - Bestaat niet\n";
    }
}
echo "\n";

// Check .env file locations
echo "🔍 .env Bestand Locaties:\n";
$possibleEnvFiles = [
    '.env',
    '../.env',
    '/var/www/.env',
    $_SERVER['DOCUMENT_ROOT'] . '/../.env',
    $_SERVER['DOCUMENT_ROOT'] . '/.env',
];

foreach ($possibleEnvFiles as $file) {
    if (file_exists($file)) {
        $readable = is_readable($file) ? "✅" : "❌";
        $writable = is_writable($file) ? "✅" : "❌";
        echo "$readable$writable $file\n";
    } else {
        echo "❌❌ $file - Bestaat niet\n";
    }
}
echo "\n";

// Check database connection
echo "🗄️ Database Verbinding Test:\n";
$dbConfigs = [
    'Laravel Config' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'collection_manager'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ]
];

foreach ($dbConfigs as $name => $config) {
    echo "$name:\n";
    echo "  Host: {$config['host']}:{$config['port']}\n";
    echo "  Database: {$config['database']}\n";
    echo "  Username: {$config['username']}\n";
    
    try {
        // Test connection without database
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "  ✅ Server verbinding: OK\n";
        
        // Test database creation
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}`");
        echo "  ✅ Database aanmaak: OK\n";
        
        // Test connection to specific database
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "  ✅ Database verbinding: OK\n";
        
        // Test table creation permissions
        $pdo->exec("CREATE TABLE IF NOT EXISTS `test_permissions` (id INT)");
        $pdo->exec("DROP TABLE IF EXISTS `test_permissions`");
        echo "  ✅ Tabel rechten: OK\n";
        
    } catch (PDOException $e) {
        echo "  ❌ Database fout: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Check Laravel application
echo "🚀 Laravel Applicatie:\n";
if (file_exists('bootstrap/app.php')) {
    echo "✅ Laravel bootstrap: OK\n";
} else {
    echo "❌ Laravel bootstrap: Niet gevonden\n";
}

if (file_exists('vendor/autoload.php')) {
    echo "✅ Composer autoload: OK\n";
} else {
    echo "❌ Composer autoload: Niet gevonden (run: composer install)\n";
}

if (file_exists('artisan')) {
    echo "✅ Artisan CLI: OK\n";
} else {
    echo "❌ Artisan CLI: Niet gevonden\n";
}

echo "\n";

// Check web server
echo "🌐 Web Server:\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Onbekend') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Onbekend') . "\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Onbekend') . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Ja' : 'Nee') . "\n";

echo "\n";

// Recommendations
echo "💡 Aanbevelingen:\n";
echo "1. Zorg dat alle PHP extensies geïnstalleerd zijn\n";
echo "2. Controleer bestandsrechten (644 voor bestanden, 755 voor mappen)\n";
echo "3. Zorg dat .env bestand leesbaar en schrijfbaar is\n";
echo "4. Controleer database gebruikersrechten\n";
echo "5. Run 'composer install' als autoload ontbreekt\n";
echo "6. Run 'php artisan key:generate' voor APP_KEY\n";

echo "\n";
echo "🔗 Setup Wizard: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . "/setup/database\n";
echo "\n"; 