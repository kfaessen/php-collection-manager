<?php

// Simple script to run migrations via admin interface
echo "Collection Manager - Database Migration Tool\n";
echo "============================================\n\n";

// Check if we can connect to the database
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=collection_manager',
        'root',
        'password'
    );
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please make sure MySQL is running and the database 'collection_manager' exists.\n";
    exit(1);
}

// Check if migrations table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
    if ($stmt->rowCount() == 0) {
        echo "Creating migrations table...\n";
        $pdo->exec("
            CREATE TABLE migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL
            )
        ");
        echo "✓ Migrations table created\n";
    } else {
        echo "✓ Migrations table already exists\n";
    }
} catch (PDOException $e) {
    echo "✗ Error creating migrations table: " . $e->getMessage() . "\n";
    exit(1);
}

// List migration files
$migrationFiles = glob('database/migrations/*.php');
echo "\nFound " . count($migrationFiles) . " migration files:\n";

foreach ($migrationFiles as $file) {
    echo "- " . basename($file) . "\n";
}

echo "\nMigration files found. Please run the following commands manually:\n";
echo "1. php artisan migrate --force\n";
echo "2. php artisan db:seed --force\n";
echo "\nOr use the admin interface at: http://localhost:8000/admin/database\n";

echo "\nDatabase setup instructions:\n";
echo "1. Make sure MySQL is running\n";
echo "2. Create database: CREATE DATABASE collection_manager;\n";
echo "3. Run migrations via admin interface or artisan command\n";
echo "4. Access the application at: http://localhost:8000\n"; 