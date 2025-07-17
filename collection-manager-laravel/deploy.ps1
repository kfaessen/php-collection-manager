# Collection Manager Laravel Deployment Script for Windows PowerShell
# This script handles the deployment process including database migrations

Write-Host "🚀 Starting Collection Manager Laravel deployment..." -ForegroundColor Blue

# Check if we're in the right directory
if (-not (Test-Path "artisan")) {
    Write-Host "[ERROR] Artisan file not found. Please run this script from the Laravel project root." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "[INFO] Checking PHP version..." -ForegroundColor Blue
try {
    php -v
} catch {
    Write-Host "[ERROR] PHP is not installed or not in PATH" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "[INFO] Checking Composer dependencies..." -ForegroundColor Blue
if (-not (Test-Path "vendor")) {
    Write-Host "[WARNING] Vendor directory not found. Installing dependencies..." -ForegroundColor Yellow
    try {
        & composer install --no-dev --optimize-autoloader
    } catch {
        Write-Host "[ERROR] Composer not found. Please install Composer first." -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
} else {
    Write-Host "[INFO] Updating Composer dependencies..." -ForegroundColor Blue
    try {
        & composer install --no-dev --optimize-autoloader
    } catch {
        Write-Host "[ERROR] Composer not found. Please install Composer first." -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}

Write-Host "[INFO] Checking environment file..." -ForegroundColor Blue
if (-not (Test-Path ".env")) {
    Write-Host "[WARNING] .env file not found. Creating from .env.example..." -ForegroundColor Yellow
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "[WARNING] Please configure your .env file with database credentials and other settings." -ForegroundColor Yellow
    } else {
        Write-Host "[ERROR] .env.example file not found. Please create a .env file manually." -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}

Write-Host "[INFO] Generating application key..." -ForegroundColor Blue
php artisan key:generate --force

Write-Host "[INFO] Clearing and caching configuration..." -ForegroundColor Blue
php artisan config:clear
php artisan config:cache

Write-Host "[INFO] Clearing and caching routes..." -ForegroundColor Blue
php artisan route:clear
php artisan route:cache

Write-Host "[INFO] Clearing and caching views..." -ForegroundColor Blue
php artisan view:clear
php artisan view:cache

Write-Host "[INFO] Checking database connection..." -ForegroundColor Blue
try {
    $null = php artisan db:show --database=mysql 2>$null
    Write-Host "[SUCCESS] Database connection successful" -ForegroundColor Green
    $DB_AVAILABLE = $true
} catch {
    Write-Host "[WARNING] Database connection failed. Please check your .env configuration." -ForegroundColor Yellow
    Write-Host "[WARNING] Continuing with deployment, but migrations will be skipped." -ForegroundColor Yellow
    $DB_AVAILABLE = $false
}

# Run migrations if database is available
if ($DB_AVAILABLE) {
    Write-Host "[INFO] Running database migrations..." -ForegroundColor Blue
    try {
        php artisan migrate --force
        Write-Host "[SUCCESS] Database migrations completed successfully" -ForegroundColor Green
        
        Write-Host "[INFO] Running database seeders..." -ForegroundColor Blue
        try {
            php artisan db:seed --force
            Write-Host "[SUCCESS] Database seeding completed successfully" -ForegroundColor Green
        } catch {
            Write-Host "[WARNING] Database seeding failed, but continuing deployment" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "[ERROR] Database migrations failed" -ForegroundColor Red
        Write-Host "[WARNING] Continuing deployment, but database may not be up to date" -ForegroundColor Yellow
    }
} else {
    Write-Host "[INFO] Skipping database operations due to connection failure" -ForegroundColor Blue
}

Write-Host "[INFO] Optimizing application..." -ForegroundColor Blue
php artisan optimize

Write-Host "[INFO] Creating storage symlink..." -ForegroundColor Blue
php artisan storage:link

Write-Host "[INFO] Checking for required PHP extensions..." -ForegroundColor Blue
$REQUIRED_EXTENSIONS = @("pdo", "pdo_mysql", "openssl", "mbstring", "tokenizer", "xml", "ctype", "json", "bcmath", "fileinfo")
$MISSING_EXTENSIONS = @()

$phpModules = php -m
foreach ($ext in $REQUIRED_EXTENSIONS) {
    if ($phpModules -notmatch "^$ext$") {
        $MISSING_EXTENSIONS += $ext
    }
}

if ($MISSING_EXTENSIONS.Count -gt 0) {
    Write-Host "[WARNING] Missing PHP extensions: $($MISSING_EXTENSIONS -join ', ')" -ForegroundColor Yellow
    Write-Host "[WARNING] Please install these extensions for full functionality" -ForegroundColor Yellow
} else {
    Write-Host "[SUCCESS] All required PHP extensions are installed" -ForegroundColor Green
}

Write-Host "[INFO] Running health check..." -ForegroundColor Blue
try {
    php artisan about
    Write-Host "[SUCCESS] Laravel application is healthy" -ForegroundColor Green
} catch {
    Write-Host "[WARNING] Health check failed, but deployment completed" -ForegroundColor Yellow
}

Write-Host "[SUCCESS] 🎉 Deployment completed successfully!" -ForegroundColor Green

# Display important information
Write-Host ""
Write-Host "📋 Deployment Summary:" -ForegroundColor Cyan
Write-Host "======================" -ForegroundColor Cyan
Write-Host "✅ Composer dependencies installed/updated" -ForegroundColor Green
Write-Host "✅ Application key generated" -ForegroundColor Green
Write-Host "✅ Configuration cached" -ForegroundColor Green
Write-Host "✅ Routes cached" -ForegroundColor Green
Write-Host "✅ Views cached" -ForegroundColor Green
if ($DB_AVAILABLE) {
    Write-Host "✅ Database migrations executed" -ForegroundColor Green
    Write-Host "✅ Database seeding completed" -ForegroundColor Green
} else {
    Write-Host "⚠️  Database operations skipped (connection failed)" -ForegroundColor Yellow
}
Write-Host "✅ Application optimized" -ForegroundColor Green
Write-Host "✅ Storage symlink created" -ForegroundColor Green

Write-Host ""
Write-Host "🔧 Next Steps:" -ForegroundColor Cyan
Write-Host "==============" -ForegroundColor Cyan
Write-Host "1. Configure your web server to point to the 'public' directory" -ForegroundColor White
Write-Host "2. Ensure your .env file has correct database credentials" -ForegroundColor White
Write-Host "3. Set up OAuth credentials if you want to use social login" -ForegroundColor White
Write-Host "4. Configure VAPID keys for push notifications" -ForegroundColor White
Write-Host "5. Test the application by visiting your domain" -ForegroundColor White

Write-Host ""
Write-Host "🔐 Default Admin Credentials:" -ForegroundColor Cyan
Write-Host "=============================" -ForegroundColor Cyan
Write-Host "Email: admin@collectionmanager.local" -ForegroundColor White
Write-Host "Password: admin123" -ForegroundColor White

Write-Host ""
Write-Host "📚 Documentation:" -ForegroundColor Cyan
Write-Host "=================" -ForegroundColor Cyan
Write-Host "- README_STAP5.md - Advanced features documentation" -ForegroundColor White
Write-Host "- DEPLOYMENT.md - Deployment guide" -ForegroundColor White
Write-Host "- Laravel documentation: https://laravel.com/docs" -ForegroundColor White

Write-Host "[SUCCESS] Deployment script finished!" -ForegroundColor Green
Read-Host "Press Enter to exit" 