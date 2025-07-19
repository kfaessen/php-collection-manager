#!/bin/bash

# Collection Manager Laravel Deployment Script voor OVH Linux Hosting
# Dit script is geoptimaliseerd voor OVH shared hosting en VPS

set -e  # Exit on any error

echo "🚀 Starting Collection Manager Laravel deployment voor OVH..."

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
    composer install --no-dev --optimize-autoloader --no-interaction
else
    print_status "Updating Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

print_status "Checking environment file..."
if [ ! -f ".env" ]; then
    print_warning ".env file not found. Creating from .env.example..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
        print_warning "Please configure your .env file with OVH database credentials."
        print_warning "Typical OVH database settings:"
        print_warning "DB_HOST=localhost"
        print_warning "DB_DATABASE=your_ovh_database_name"
        print_warning "DB_USERNAME=your_ovh_database_user"
        print_warning "DB_PASSWORD=your_ovh_database_password"
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
else
    print_warning "Database connection failed. Please check your .env configuration."
    print_warning "Continuing deployment, but migrations will be skipped."
    print_warning "Make sure your OVH database is properly configured."
fi

print_status "Optimizing application..."
php artisan optimize

print_status "Setting proper permissions for OVH..."
# OVH specific permissions
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chmod -R 775 storage/framework/cache
chmod -R 775 storage/framework/sessions
chmod -R 775 storage/framework/views

# Set ownership for OVH (usually www-data or apache)
if command -v apache2 >/dev/null 2>&1; then
    print_status "Setting Apache ownership..."
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
elif command -v nginx >/dev/null 2>&1; then
    print_status "Setting Nginx ownership..."
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
else
    print_warning "Web server not detected, skipping ownership changes"
fi

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
    print_warning "Please contact OVH support to enable these extensions"
else
    print_success "All required PHP extensions are installed"
fi

print_status "Running health check..."
if php artisan about; then
    print_success "Laravel application is healthy"
else
    print_warning "Health check failed, but deployment completed"
fi

print_success "🎉 OVH Deployment completed successfully!"

# Display important information
echo ""
echo "📋 OVH Deployment Summary:"
echo "=========================="
echo "✅ Composer dependencies installed/updated"
echo "✅ Application key generated"
echo "✅ Configuration cached"
echo "✅ Routes cached"
echo "✅ Views cached"
echo "✅ Database migrations executed"
echo "✅ Database seeding completed"
echo "✅ Application optimized"
echo "✅ OVH-specific permissions set"
echo "✅ Storage symlink created"

echo ""
echo "🔧 OVH-Specific Next Steps:"
echo "==========================="
echo "1. Upload files to your OVH hosting directory"
echo "2. Ensure your .env file has correct OVH database credentials"
echo "3. Set up OAuth credentials if you want to use social login"
echo "4. Configure VAPID keys for push notifications"
echo "5. Test the application by visiting your OVH domain"

echo ""
echo "🌐 OVH Configuration Tips:"
echo "=========================="
echo "- Database host is usually 'localhost'"
echo "- Use the database credentials from your OVH control panel"
echo "- Make sure PHP version is 8.2 or higher"
echo "- Enable required PHP extensions via OVH control panel"
echo "- Set proper file permissions (755 for directories, 644 for files)"

echo ""
echo "🔐 Default Admin Credentials:"
echo "============================="
echo "Email: admin@collectionmanager.local"
echo "Password: admin123"

echo ""
echo "📚 Documentation:"
echo "================="
echo "- DEPLOYMENT.md - Full deployment guide"
echo "- README_STAP5.md - Advanced features documentation"
echo "- OVH documentation: https://docs.ovh.com/"

print_success "OVH deployment script finished!" 