<?php

// Script om migraties uit te voeren via admin API
echo "Collection Manager - Admin API Migration Tool\n";
echo "============================================\n\n";

// Simuleer een POST request naar de admin migrate endpoint
$url = 'http://localhost:8000/admin/database/migrate';
$data = [];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);

echo "Attempting to run migrations via admin API...\n";
echo "URL: $url\n\n";

try {
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        echo "✗ Failed to connect to admin API\n";
        echo "Make sure the PHP development server is running:\n";
        echo "php -S localhost:8000 -t public\n\n";
        
        echo "Alternative: Manual database setup\n";
        echo "1. Create database: CREATE DATABASE collection_manager;\n";
        echo "2. Run migrations manually via artisan or admin interface\n";
    } else {
        echo "✓ Admin API response received\n";
        echo "Response: $result\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nManual Setup Instructions:\n";
echo "==========================\n";
echo "1. Start MySQL server\n";
echo "2. Create database: CREATE DATABASE collection_manager;\n";
echo "3. Start PHP server: php -S localhost:8000 -t public\n";
echo "4. Visit: http://localhost:8000/admin/database\n";
echo "5. Use the admin interface to run migrations\n";
echo "\nOr if PHP is available in PATH:\n";
echo "php artisan migrate --force\n";
echo "php artisan db:seed --force\n"; 