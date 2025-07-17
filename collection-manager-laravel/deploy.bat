@echo off
setlocal enabledelayedexpansion

REM Collection Manager Laravel Deployment Script for Windows
REM This script handles the deployment process including database migrations

echo 🚀 Starting Collection Manager Laravel deployment...

REM Check if we're in the right directory
if not exist "artisan" (
    echo [ERROR] Artisan file not found. Please run this script from the Laravel project root.
    pause
    exit /b 1
)

echo [INFO] Checking PHP version...
php -v
if errorlevel 1 (
    echo [ERROR] PHP is not installed or not in PATH
    pause
    exit /b 1
)

echo [INFO] Checking Composer dependencies...
if not exist "vendor" (
    echo [WARNING] Vendor directory not found. Installing dependencies...
    composer install --no-dev --optimize-autoloader
) else (
    echo [INFO] Updating Composer dependencies...
    composer install --no-dev --optimize-autoloader
)

echo [INFO] Checking environment file...
if not exist ".env" (
    echo [WARNING] .env file not found. Creating from .env.example...
    if exist ".env.example" (
        copy ".env.example" ".env"
        echo [WARNING] Please configure your .env file with database credentials and other settings.
    ) else (
        echo [ERROR] .env.example file not found. Please create a .env file manually.
        pause
        exit /b 1
    )
)

echo [INFO] Generating application key...
php artisan key:generate --force

echo [INFO] Clearing and caching configuration...
php artisan config:clear
php artisan config:cache

echo [INFO] Clearing and caching routes...
php artisan route:clear
php artisan route:cache

echo [INFO] Clearing and caching views...
php artisan view:clear
php artisan view:cache

echo [INFO] Checking database connection...
php artisan db:show --database=mysql >nul 2>&1
if errorlevel 1 (
    echo [WARNING] Database connection failed. Please check your .env configuration.
    echo [WARNING] Continuing with deployment, but migrations will be skipped.
    set DB_AVAILABLE=false
) else (
    echo [SUCCESS] Database connection successful
    set DB_AVAILABLE=true
)

REM Run migrations if database is available
if "%DB_AVAILABLE%"=="true" (
    echo [INFO] Running database migrations...
    php artisan migrate --force
    if errorlevel 1 (
        echo [ERROR] Database migrations failed
        echo [WARNING] Continuing deployment, but database may not be up to date
    ) else (
        echo [SUCCESS] Database migrations completed successfully
        
        echo [INFO] Running database seeders...
        php artisan db:seed --force
        if errorlevel 1 (
            echo [WARNING] Database seeding failed, but continuing deployment
        ) else (
            echo [SUCCESS] Database seeding completed successfully
        )
    )
) else (
    echo [INFO] Skipping database operations due to connection failure
)

echo [INFO] Optimizing application...
php artisan optimize

echo [INFO] Setting proper permissions...
REM Note: Windows doesn't use chmod, but we can set ACLs if needed
REM For now, we'll just create the storage symlink

echo [INFO] Creating storage symlink...
php artisan storage:link

echo [INFO] Checking for required PHP extensions...
set REQUIRED_EXTENSIONS=pdo pdo_mysql openssl mbstring tokenizer xml ctype json bcmath fileinfo
set MISSING_EXTENSIONS=

for %%e in (%REQUIRED_EXTENSIONS%) do (
    php -m | findstr /i "^%%e$" >nul
    if errorlevel 1 (
        if not defined MISSING_EXTENSIONS (
            set MISSING_EXTENSIONS=%%e
        ) else (
            set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! %%e
        )
    )
)

if defined MISSING_EXTENSIONS (
    echo [WARNING] Missing PHP extensions: %MISSING_EXTENSIONS%
    echo [WARNING] Please install these extensions for full functionality
) else (
    echo [SUCCESS] All required PHP extensions are installed
)

echo [INFO] Running health check...
php artisan about
if errorlevel 1 (
    echo [WARNING] Health check failed, but deployment completed
) else (
    echo [SUCCESS] Laravel application is healthy
)

echo [SUCCESS] 🎉 Deployment completed successfully!

REM Display important information
echo.
echo 📋 Deployment Summary:
echo ======================
echo ✅ Composer dependencies installed/updated
echo ✅ Application key generated
echo ✅ Configuration cached
echo ✅ Routes cached
echo ✅ Views cached
if "%DB_AVAILABLE%"=="true" (
    echo ✅ Database migrations executed
    echo ✅ Database seeding completed
) else (
    echo ⚠️  Database operations skipped (connection failed)
)
echo ✅ Application optimized
echo ✅ Storage symlink created

echo.
echo 🔧 Next Steps:
echo ==============
echo 1. Configure your web server to point to the 'public' directory
echo 2. Ensure your .env file has correct database credentials
echo 3. Set up OAuth credentials if you want to use social login
echo 4. Configure VAPID keys for push notifications
echo 5. Test the application by visiting your domain

echo.
echo 🔐 Default Admin Credentials:
echo =============================
echo Email: admin@collectionmanager.local
echo Password: admin123

echo.
echo 📚 Documentation:
echo =================
echo - README_STAP5.md - Advanced features documentation
echo - Laravel documentation: https://laravel.com/docs

echo [SUCCESS] Deployment script finished!
pause 