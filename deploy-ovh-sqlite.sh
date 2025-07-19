#!/bin/bash

# Collection Manager Laravel Deployment Script voor OVH Linux Hosting
# SQLite Fallback Version - Use when MySQL is not available

set -e  # Exit on any error

echo "🚀 Starting Collection Manager Laravel deployment voor OVH (SQLite Fallback)..."

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

print_status "Clearing caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

print_status "Setting up environment..."
if [ ! -f ".env" ]; then
    print_warning ".env file not found. Copying from .env.example..."
    cp .env.example .env
fi

# Configure for SQLite
print_status "Configuring for SQLite database..."
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=database\/database.sqlite/' .env

# Create SQLite database directory and file
print_status "Setting up SQLite database..."
mkdir -p database
touch database/database.sqlite
chmod 664 database/database.sqlite

print_success "SQLite database file created: database/database.sqlite"

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    print_status "Generating application key..."
    php artisan key:generate --force
fi

print_status "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_status "Running database migrations..."
if php artisan migrate --force; then
    print_success "Database migrations completed successfully"
else
    print_error "Database migrations failed"
    print_warning "This may be due to:"
    print_warning "1. SQLite extension not enabled in PHP"
    print_warning "2. File permissions issues"
    print_warning "3. Database file not writable"
    print_warning ""
    print_warning "Please check: php -m | grep sqlite"
    print_warning "And ensure the database directory is writable"
fi

print_status "Running database seeders..."
if php artisan db:seed --force; then
    print_success "Database seeding completed successfully"
else
    print_warning "Database seeding failed - this is optional for deployment"
fi

print_status "Publishing Spatie Permission assets..."
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force || true

print_status "Creating storage link..."
php artisan storage:link || true

print_status "Optimizing application..."
php artisan optimize

print_status "Setting file permissions for OVH..."
# Set proper permissions for Laravel directories
find storage -type f -exec chmod 644 {} \; 2>/dev/null || true
find storage -type d -exec chmod 755 {} \; 2>/dev/null || true
find bootstrap/cache -type f -exec chmod 644 {} \; 2>/dev/null || true
find bootstrap/cache -type d -exec chmod 755 {} \; 2>/dev/null || true

# Make sure the application can write to necessary directories
chmod -R 755 storage/ 2>/dev/null || true
chmod -R 755 bootstrap/cache/ 2>/dev/null || true
chmod 664 database/database.sqlite 2>/dev/null || true

print_success "File permissions set for OVH hosting"

print_status "Deployment completed!"
print_success "🎉 Collection Manager Laravel is now deployed on OVH with SQLite!"
print_warning ""
print_warning "Next steps:"
print_warning "1. Configure your web server to point to the 'public' directory"
print_warning "2. Test the application in your browser"
print_warning "3. Configure OAuth credentials in .env if using social login"
print_warning "4. Set up VAPID keys for push notifications if needed"
print_warning ""
print_warning "Note: Using SQLite database at database/database.sqlite"
print_warning "For production, consider switching to MySQL when available"
print_warning ""
print_success "Deployment log completed successfully!" 