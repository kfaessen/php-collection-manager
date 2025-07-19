<?php

echo "Collection Manager - Direct Migration Tool\n";
echo "=========================================\n\n";

// Database configuration
$host = '127.0.0.1';
$port = 3306;
$database = 'collection_manager';
$username = 'root';
$password = 'password';

// Test database connection
try {
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please make sure MySQL is running.\n";
    exit(1);
}

// Create database if it doesn't exist
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
    echo "✓ Database '$database' ready\n";
} catch (PDOException $e) {
    echo "✗ Failed to create database: " . $e->getMessage() . "\n";
    exit(1);
}

// Connect to the specific database
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    echo "✓ Connected to database '$database'\n";
} catch (PDOException $e) {
    echo "✗ Failed to connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

// Create migrations table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL
        )
    ");
    echo "✓ Migrations table ready\n";
} catch (PDOException $e) {
    echo "✗ Failed to create migrations table: " . $e->getMessage() . "\n";
    exit(1);
}

// List migration files
$migrationFiles = glob('database/migrations/*.php');
echo "\nFound " . count($migrationFiles) . " migration files:\n";

foreach ($migrationFiles as $file) {
    echo "- " . basename($file) . "\n";
}

echo "\nMigration files found. To run migrations:\n";
echo "1. Make sure Laravel dependencies are installed: composer install\n";
echo "2. Run: php artisan migrate --force\n";
echo "3. Run: php artisan db:seed --force\n";

echo "\nOr use the admin interface:\n";
echo "1. Start PHP server: php -S localhost:8000 -t public\n";
echo "2. Visit: http://localhost:8000/admin/database\n";
echo "3. Use the admin interface to run migrations\n";

echo "\nDatabase is ready for migrations!\n"; 