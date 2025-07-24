<?php

echo "Collection Manager - APP_KEY Fix\n";
echo "===============================\n\n";

// Load Laravel application
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

echo "1. Checking current APP_KEY...\n";

// Check if APP_KEY is set in .env
$envFile = '.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (strpos($envContent, 'APP_KEY=base64:') !== false) {
        echo "✓ APP_KEY is already set in .env\n";
    } else {
        echo "⚠️  APP_KEY is missing or not properly formatted\n";
    }
} else {
    echo "✗ .env file not found\n";
    exit(1);
}

echo "\n2. Generating new APP_KEY...\n";

try {
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    // Generate application key
    $exitCode = \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
    
    if ($exitCode === 0) {
        echo "✓ Application key generated successfully!\n";
        echo \Illuminate\Support\Facades\Artisan::output();
    } else {
        echo "✗ Application key generation failed!\n";
        echo \Illuminate\Support\Facades\Artisan::output();
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Error generating key: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3. Testing application...\n";

try {
    // Test if the application can now start without the MissingAppKeyException
    $app = require_once 'bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    // Try to access a simple Laravel service
    $config = $app->make('config');
    $appName = $config->get('app.name');
    
    echo "✓ Application started successfully!\n";
    echo "✓ App name: {$appName}\n";
    
} catch (Exception $e) {
    echo "✗ Application still has issues: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n4. Clearing caches...\n";

try {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "✓ Caches cleared successfully!\n";
} catch (Exception $e) {
    echo "⚠️  Cache clearing error: " . $e->getMessage() . "\n";
}

echo "\n✅ APP_KEY issue fixed!\n";
echo "\nThe application should now work without the MissingAppKeyException.\n";
echo "You can test by visiting your website.\n";

echo "\nIf you still get errors, the next step would be to:\n";
echo "1. Check database configuration\n";
echo "2. Run migrations: php artisan migrate --force\n";
echo "3. Check file permissions on storage/ and bootstrap/cache/\n"; 