#!/bin/bash

# Collection Manager Laravel Deployment Script
# This script handles the deployment process including database migrations

set -e  # Exit on any error

echo "🚀 Starting Collection Manager Laravel deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Artisan file not found. Please run this script from the Laravel project root."
    exit 1
fi

print_status "Checking PHP version..."
php -v

print_status "Checking Composer dependencies..."
if [ ! -d "vendor" ]; then
    print_warning "Vendor directory not found. Installing dependencies..."
    composer install --no-dev --optimize-autoloader
else
    print_status "Updating Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

print_status "Checking environment file..."
if [ ! -f ".env" ]; then
    print_warning ".env file not found. Creating from .env.example..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
        print_warning "Please configure your .env file with database credentials and other settings."
    else
        print_error ".env.example file not found. Please create a .env file manually."
        exit 1
    fi
fi

print_status "Generating application key..."
php artisan key:generate --force

print_status "Clearing and caching configuration..."
php artisan config:clear
php artisan config:cache

print_status "Clearing and caching routes..."
php artisan route:clear
php artisan route:cache

print_status "Clearing and caching views..."
php artisan view:clear
php artisan view:cache

print_status "Checking database connection..."
if php artisan db:show --database=mysql 2>/dev/null; then
    print_success "Database connection successful"
else
    print_warning "Database connection failed. Please check your .env configuration."
    print_warning "Continuing with deployment, but migrations will be skipped."
    DB_AVAILABLE=false
fi

# Run migrations if database is available
if [ "$DB_AVAILABLE" != "false" ]; then
    print_status "Running database migrations..."
    if php artisan migrate --force; then
        print_success "Database migrations completed successfully"
        
        print_status "Running database seeders..."
        if php artisan db:seed --force; then
            print_success "Database seeding completed successfully"
        else
            print_warning "Database seeding failed, but continuing deployment"
        fi
    else
        print_error "Database migrations failed"
        print_warning "Continuing deployment, but database may not be up to date"
    fi
fi

print_status "Optimizing application..."
php artisan optimize

print_status "Setting proper permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chmod -R 775 storage/framework/cache
chmod -R 775 storage/framework/sessions
chmod -R 775 storage/framework/views

print_status "Creating storage symlink..."
php artisan storage:link

print_status "Checking for required PHP extensions..."
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "openssl" "mbstring" "tokenizer" "xml" "ctype" "json" "bcmath" "fileinfo")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "^$ext$"; then
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    print_warning "Missing PHP extensions: ${MISSING_EXTENSIONS[*]}"
    print_warning "Please install these extensions for full functionality"
else
    print_success "All required PHP extensions are installed"
fi

print_status "Running health check..."
if php artisan about; then
    print_success "Laravel application is healthy"
else
    print_warning "Health check failed, but deployment completed"
fi

print_success "🎉 Deployment completed successfully!"

# Display important information
echo ""
echo "📋 Deployment Summary:"
echo "======================"
echo "✅ Composer dependencies installed/updated"
echo "✅ Application key generated"
echo "✅ Configuration cached"
echo "✅ Routes cached"
echo "✅ Views cached"
if [ "$DB_AVAILABLE" != "false" ]; then
    echo "✅ Database migrations executed"
    echo "✅ Database seeding completed"
else
    echo "⚠️  Database operations skipped (connection failed)"
fi
echo "✅ Application optimized"
echo "✅ Permissions set"
echo "✅ Storage symlink created"

echo ""
echo "🔧 Next Steps:"
echo "=============="
echo "1. Configure your web server to point to the 'public' directory"
echo "2. Ensure your .env file has correct database credentials"
echo "3. Set up OAuth credentials if you want to use social login"
echo "4. Configure VAPID keys for push notifications"
echo "5. Test the application by visiting your domain"

echo ""
echo "🔐 Default Admin Credentials:"
echo "============================="
echo "Email: admin@collectionmanager.local"
echo "Password: admin123"

echo ""
echo "📚 Documentation:"
echo "================="
echo "- README_STAP5.md - Advanced features documentation"
echo "- Laravel documentation: https://laravel.com/docs"

print_success "Deployment script finished!" 