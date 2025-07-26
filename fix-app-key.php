<?php

echo "Collection Manager - APP_KEY Fix\n";
echo "===============================\n\n";

// Load Laravel application
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

echo "1. Checking current APP_KEY...\n";

// Function to properly check APP_KEY in .env file
function checkAppKey($envContent) {
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Check if this line defines APP_KEY
        if (preg_match('/^APP_KEY\s*=\s*(.*)$/', $line, $matches)) {
            $value = trim($matches[1]);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Check if value is empty or just whitespace
            if (empty($value) || $value === 'null' || $value === 'NULL') {
                return ['status' => 'empty', 'message' => 'APP_KEY is empty or set to null'];
            }
            
            // Check if it's a valid base64 key (starts with base64: and has content)
            if (strpos($value, 'base64:') === 0) {
                $base64Content = substr($value, 7); // Remove 'base64:' prefix
                if (strlen($base64Content) >= 32 && base64_decode($base64Content, true) !== false) {
                    return ['status' => 'valid_base64', 'message' => 'APP_KEY is set with valid base64 format'];
                } else {
                    return ['status' => 'invalid_base64', 'message' => 'APP_KEY has base64: prefix but invalid content'];
                }
            }
            
            // Check if it's a plain string key (32+ characters)
            if (strlen($value) >= 32) {
                return ['status' => 'valid_plain', 'message' => 'APP_KEY is set with plain string format'];
            }
            
            // Invalid format
            return ['status' => 'invalid', 'message' => 'APP_KEY has invalid format'];
        }
    }
    
    return ['status' => 'missing', 'message' => 'APP_KEY is not defined'];
}

// Check if APP_KEY is set in .env
$envFile = '.env';
if (!file_exists($envFile)) {
    echo "✗ .env file not found\n";
    exit(1);
}

$envContent = file_get_contents($envFile);
$appKeyStatus = checkAppKey($envContent);

switch ($appKeyStatus['status']) {
    case 'valid_base64':
    case 'valid_plain':
        echo "✓ " . $appKeyStatus['message'] . "\n";
        break;
    case 'empty':
    case 'invalid_base64':
    case 'invalid':
    case 'missing':
        echo "⚠️  " . $appKeyStatus['message'] . "\n";
        break;
}

// Bootstrap Laravel application for environment variable access
echo "\n2. Bootstrapping Laravel application...\n";
try {
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo "✓ Laravel application bootstrapped successfully\n";
} catch (Exception $e) {
    echo "⚠️  Bootstrap warning: " . $e->getMessage() . "\n";
    echo "Continuing anyway...\n";
}

// Only generate new key if current one is invalid
if (in_array($appKeyStatus['status'], ['valid_base64', 'valid_plain'])) {
    echo "\n3. APP_KEY is already valid, skipping key generation...\n";
    echo "✓ No action needed\n";
    
    // Ensure environment is reloaded even when skipping key generation
    // This is necessary for the testing phase to work correctly
    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        echo "✓ Configuration cache cleared to ensure environment variables are fresh\n";
    } catch (Exception $e) {
        echo "⚠️  Warning: Could not clear configuration cache: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n3. Generating new APP_KEY...\n";

    try {
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
}

echo "\n4. Testing application...\n";

try {
    // Ensure Laravel is properly bootstrapped before testing env() function
    if (!function_exists('env')) {
        // Re-bootstrap Laravel if env() function is not available
        echo "Re-bootstrapping Laravel for environment access...\n";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    }
    
    // Simple test: try to access environment variables
    $appKey = env('APP_KEY');
    if (!empty($appKey)) {
        echo "✓ APP_KEY environment variable is accessible\n";
        echo "✓ Key length: " . strlen($appKey) . " characters\n";
    } else {
        echo "⚠️  APP_KEY environment variable is empty\n";
    }
    
    // Test if we can load the .env file
    if (file_exists('.env')) {
        echo "✓ .env file is accessible\n";
    } else {
        echo "✗ .env file not found\n";
    }
    
    echo "✓ Basic application test completed\n";
    
} catch (Exception $e) {
    echo "✗ Application test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n5. Clearing caches...\n";

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