<?php
/**
 * Build & Test Script voor Collection Manager
 * Dit script valideert het project en optimaliseert het voor deployment
 */

echo "🚀 Collection Manager Build Script\n";
echo "==================================\n\n";

$errors = [];
$warnings = [];
$startTime = microtime(true);

// Determine PHP executable path
$phpPath = 'php'; // Default for Unix/Linux
if (PHP_OS_FAMILY === 'Windows') {
    // Check if we're running from the specific PHP installation
    $possiblePaths = [
        'C:\Program Files\php-8.4.10\php.exe',
        'C:\Program Files\PHP\php.exe',
        'C:\php\php.exe',
        'php.exe'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path) || (strpos($path, 'php.exe') !== false && shell_exec("where php.exe 2>nul"))) {
            $phpPath = '"' . $path . '"';
            break;
        }
    }
    
    // If we can't find PHP, use the current running PHP
    if ($phpPath === 'php' && defined('PHP_BINARY')) {
        $phpPath = '"' . PHP_BINARY . '"';
    }
}

echo "Using PHP: $phpPath\n\n";

// Check PHP version
$requiredPHP = '8.0.0';
if (version_compare(PHP_VERSION, $requiredPHP, '<')) {
    $errors[] = "PHP version $requiredPHP or higher is required. Current: " . PHP_VERSION;
} else {
    echo "✅ PHP Version: " . PHP_VERSION . "\n";
}

// Check required PHP extensions
$requiredExtensions = ['pdo', 'json']; // Only check essentials that should always be available
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

// Check optional extensions
$optionalExtensions = ['pdo_mysql', 'mbstring', 'openssl', 'curl'];
$missingOptional = [];

foreach ($optionalExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingOptional[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    $errors[] = "Missing required PHP extensions: " . implode(', ', $missingExtensions);
} else {
    echo "✅ Required PHP extensions are available\n";
}

if (!empty($missingOptional)) {
    $warnings[] = "Missing optional PHP extensions (may affect functionality): " . implode(', ', $missingOptional);
}

// Validate PHP syntax in all files
echo "\n🔍 Validating PHP syntax...\n";
$phpFiles = [];
$directories = ['includes', 'public'];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
    }
}

$syntaxErrors = 0;
foreach ($phpFiles as $file) {
    $escapedFile = escapeshellarg($file);
    $output = shell_exec("$phpPath -l $escapedFile 2>&1");
    if ($output && strpos($output, 'No syntax errors detected') === false) {
        $errors[] = "Syntax error in $file: " . trim($output);
        $syntaxErrors++;
    }
}

if ($syntaxErrors === 0) {
    echo "✅ PHP syntax validation passed (" . count($phpFiles) . " files checked)\n";
} else {
    echo "❌ PHP syntax validation failed ($syntaxErrors errors)\n";
}

// Check core files exist
echo "\n📁 Checking core files...\n";
$coreFiles = [
    'includes/functions.php',
    'includes/Database.php',
    'includes/Environment.php',
    'includes/Authentication.php',
    'includes/UserManager.php',
    'includes/CollectionManager.php',
    'public/index.php',
    'public/login.php',
    'composer.json',
    '.htaccess'
];

foreach ($coreFiles as $file) {
    if (!file_exists($file)) {
        $errors[] = "Core file missing: $file";
    }
}

if (empty(array_filter($coreFiles, function($f) { return !file_exists($f); }))) {
    echo "✅ All core files present\n";
}

// Validate composer.json
echo "\n📦 Validating composer.json...\n";
if (file_exists('composer.json')) {
    $composerData = json_decode(file_get_contents('composer.json'), true);
    if ($composerData === null) {
        $errors[] = "Invalid composer.json syntax";
    } else {
        echo "✅ composer.json is valid\n";
        
        // Check for required dependencies
        $requiredDeps = ['php'];
        if (isset($composerData['require'])) {
            foreach ($requiredDeps as $dep) {
                if (!isset($composerData['require'][$dep])) {
                    $warnings[] = "Dependency '$dep' not specified in composer.json";
                }
            }
        }
    }
} else {
    $errors[] = "composer.json file not found";
}

// Check environment template
echo "\n⚙️  Checking environment configuration...\n";
if (file_exists('env.template')) {
    $envTemplate = file_get_contents('env.template');
    $requiredEnvVars = [
        'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
        'APP_ENV', 'SESSION_SECRET'
    ];
    
    foreach ($requiredEnvVars as $var) {
        if (strpos($envTemplate, $var) === false) {
            $warnings[] = "Environment variable '$var' not found in env.template";
        }
    }
    echo "✅ Environment template validated\n";
} else {
    $warnings[] = "env.template file not found";
}

// Check directory permissions
echo "\n🔐 Checking directory permissions...\n";
$writableDirs = ['uploads', 'uploads/covers'];
foreach ($writableDirs as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            $warnings[] = "Could not create directory: $dir";
        } else {
            echo "✅ Created directory: $dir\n";
        }
    }
    
    if (is_dir($dir) && !is_writable($dir)) {
        $warnings[] = "Directory not writable: $dir";
    }
}

// Validate .htaccess
echo "\n🔧 Validating .htaccess...\n";
if (file_exists('.htaccess')) {
    $htaccess = file_get_contents('.htaccess');
    if (strpos($htaccess, 'RewriteEngine On') !== false) {
        echo "✅ .htaccess appears valid\n";
    } else {
        $warnings[] = ".htaccess may not have URL rewriting enabled";
    }
} else {
    $warnings[] = ".htaccess file not found";
}

// Security checks
echo "\n🛡️  Running security checks...\n";
$securityIssues = 0;

// Check for .env file in version control
if (file_exists('.env')) {
    $warnings[] = ".env file exists - ensure it's in .gitignore for security";
}

// Check for debug settings in production files
$productionFiles = ['public/index.php', 'includes/functions.php'];
foreach ($productionFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'error_reporting(E_ALL)') !== false) {
            $warnings[] = "Debug settings found in $file - consider removing for production";
        }
    }
}

// Check upload directory security
if (is_dir('uploads')) {
    $indexFile = 'uploads/index.php';
    if (!file_exists($indexFile)) {
        file_put_contents($indexFile, "<?php\n// Prevent directory browsing\nheader('HTTP/1.0 403 Forbidden');\nexit;\n");
        echo "✅ Created uploads security file\n";
    }
}

echo "✅ Security checks completed\n";

// Performance optimization checks
echo "\n⚡ Performance optimization checks...\n";

// Check for CSS/JS minification opportunities
$staticFiles = [];
if (is_dir('assets')) {
    $staticFiles = glob('assets/css/*.css') + glob('assets/js/*.js');
}

if (!empty($staticFiles)) {
    $totalSize = 0;
    foreach ($staticFiles as $file) {
        $totalSize += filesize($file);
    }
    echo "📊 Static assets total size: " . number_format($totalSize / 1024, 2) . " KB\n";
    
    if ($totalSize > 500 * 1024) { // 500KB
        $warnings[] = "Static assets are quite large. Consider minification for production.";
    }
}

// Check image optimization
if (is_dir('uploads/covers')) {
    $images = glob('uploads/covers/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    if (!empty($images)) {
        $totalImageSize = 0;
        foreach ($images as $image) {
            $totalImageSize += filesize($image);
        }
        echo "🖼️  Total image size: " . number_format($totalImageSize / 1024 / 1024, 2) . " MB\n";
        
        if ($totalImageSize > 50 * 1024 * 1024) { // 50MB
            $warnings[] = "Cover images are quite large. Consider optimization.";
        }
    }
}

echo "✅ Performance checks completed\n";

// Database schema validation (if possible)
echo "\n🗄️  Database schema validation...\n";
try {
    // Try to load and validate database classes without executing them
    if (file_exists('includes/Database.php')) {
        $escapedFile = escapeshellarg('includes/Database.php');
        $output = shell_exec("$phpPath -l $escapedFile 2>&1");
        if ($output && strpos($output, 'No syntax errors detected') !== false) {
            echo "✅ Database class syntax valid\n";
        } else {
            $warnings[] = "Database class syntax issue: " . trim($output ?: 'Unknown error');
        }
    }
} catch (Exception $e) {
    $warnings[] = "Could not validate database schema: " . $e->getMessage();
}

// API endpoint validation
echo "\n🌐 API endpoint validation...\n";
$apiFiles = ['public/health-check.php'];
foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        // Test syntax only, don't execute
        $escapedFile = escapeshellarg($file);
        $output = shell_exec("$phpPath -l $escapedFile 2>&1");
        if ($output && strpos($output, 'No syntax errors detected') !== false) {
            echo "✅ API endpoint syntax valid: $file\n";
        } else {
            $warnings[] = "API endpoint syntax issue in $file: " . trim($output ?: 'Unknown error');
        }
    } else {
        $warnings[] = "API endpoint missing: $file";
    }
}

// Build artifacts creation
echo "\n📦 Creating build artifacts...\n";

// Create build info file
$buildInfo = [
    'build_time' => date('c'),
    'php_version' => PHP_VERSION,
    'php_path' => $phpPath,
    'files_checked' => count($phpFiles),
    'errors' => count($errors),
    'warnings' => count($warnings),
    'commit_hash' => trim(shell_exec('git rev-parse HEAD 2>/dev/null') ?: 'unknown'),
    'branch' => trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null') ?: 'unknown')
];

file_put_contents('build-info.json', json_encode($buildInfo, JSON_PRETTY_PRINT));
echo "✅ Build info created: build-info.json\n";

// Generate deployment checksum
echo "\n🔐 Generating deployment checksum...\n";
$checksumFiles = array_merge($phpFiles, ['composer.json', '.htaccess']);
$checksumData = '';

foreach ($checksumFiles as $file) {
    if (file_exists($file)) {
        $checksumData .= $file . ':' . md5_file($file) . "\n";
    }
}

$deploymentChecksum = md5($checksumData);
file_put_contents('deployment.checksum', $deploymentChecksum);
echo "✅ Deployment checksum: $deploymentChecksum\n";

// Summary
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 BUILD SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Build Duration: {$duration}s\n";
echo "Files Checked: " . count($phpFiles) . "\n";
echo "Errors: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n";

if (!empty($errors)) {
    echo "\n❌ ERRORS:\n";
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  • $warning\n";
    }
}

if (empty($errors)) {
    echo "\n✅ BUILD SUCCESS - Ready for deployment!\n";
    exit(0);
} else {
    echo "\n❌ BUILD FAILED - Please fix errors before deployment!\n";
    exit(1);
} 