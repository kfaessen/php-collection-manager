<?php

// Load Laravel application
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set database configuration
putenv('DB_CONNECTION=mysql');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=collection_manager');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=password');

// Clear config cache
Artisan::call('config:clear');

// Run migrations
echo "Starting database migrations...\n";
$exitCode = Artisan::call('migrate', ['--force' => true]);

if ($exitCode === 0) {
    echo "Migrations completed successfully!\n";
    echo Artisan::output();
} else {
    echo "Migrations failed!\n";
    echo Artisan::output();
    exit(1);
}

// Run seeders if needed
echo "\nRunning database seeders...\n";
$exitCode = Artisan::call('db:seed', ['--force' => true]);

if ($exitCode === 0) {
    echo "Seeders completed successfully!\n";
    echo Artisan::output();
} else {
    echo "Seeders failed!\n";
    echo Artisan::output();
    exit(1);
}

echo "\nDatabase setup completed successfully!\n"; 