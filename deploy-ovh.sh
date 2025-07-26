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

# === BEGIN TOEVOEGING: Detectie juiste PHP- en Composer-binary ===

# Detecteer juiste PHP-binary (php, php8.2, php8.1, ...)
PHP_BIN="php"
for bin in php php8.2 php8.1 php8.0; do
    if command -v $bin &> /dev/null; then
        PHP_BIN=$bin
        break
    fi
done
print_status "Gevonden PHP-binary: $PHP_BIN"

# Detecteer juiste Composer-binary
COMPOSER_BIN="composer"
if ! command -v composer &> /dev/null; then
    if [ -f "composer.phar" ]; then
        COMPOSER_BIN="$PHP_BIN composer.phar"
    else
        print_error "Composer niet gevonden. Installeer Composer of plaats composer.phar in de projectmap."
        exit 1
    fi
fi
print_status "Gevonden Composer-binary: $COMPOSER_BIN"

# === Vervang alle 'php' en 'composer' commando's door variabelen ===
# Bijvoorbeeld:
# php artisan ...  =>  $PHP_BIN artisan ...
# composer install =>  $COMPOSER_BIN install

print_status "Checking PHP version..."
$PHP_BIN -v

print_status "Checking Composer dependencies..."
if [ ! -d "vendor" ]; then
    print_warning "Vendor directory not found. Installing dependencies..."
    $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction
else
    print_status "Updating Composer dependencies..."
    $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction
fi

print_status "Clearing caches..."
$PHP_BIN artisan config:clear || true
$PHP_BIN artisan route:clear || true
$PHP_BIN artisan view:clear || true
$PHP_BIN artisan cache:clear || true

print_status "Setting up environment..."
if [ ! -f ".env" ]; then
    print_warning ".env file not found. Copying from .env.example..."
    cp .env.example .env
    print_warning "Please configure your .env file with the correct database settings."
fi

# === BEGIN TOEVOEGING: .env validatie ===

REQUIRED_ENV_VARS=(DB_HOST DB_USERNAME DB_PASSWORD DB_DATABASE APP_KEY)
MISSING_ENV_VARS=()
for var in "${REQUIRED_ENV_VARS[@]}"; do
    if ! grep -q "^$var=" .env; then
        MISSING_ENV_VARS+=("$var")
    fi
done
if [ ${#MISSING_ENV_VARS[@]} -gt 0 ]; then
    print_error ".env mist de volgende essentiële variabelen: ${MISSING_ENV_VARS[*]}"
    print_warning "Vul deze variabelen in voordat je verder gaat."
    exit 1
fi

# === AANPASSING: Databasecreatie foutafhandeling ===
# (vervang bestaande foutmelding na mislukte databasecreatie)

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    print_status "Generating application key..."
    $PHP_BIN artisan key:generate --force
fi

print_status "Caching configuration..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# Enhanced Database Setup with Multiple Connection Methods
print_status "Setting up database..."

# Function to test MySQL connection with better error handling
test_mysql_connection() {
    local host=$1
    local user=$2
    local password=$3
    local extra_args=$4
    
    # Try with password if provided
    if [ -n "$password" ]; then
        $PHP_BIN -h "$host" -u "$user" -p"$password" $extra_args -e "SELECT 1;" 2>/dev/null && return 0
    fi
    
    # Try without password
    $PHP_BIN -h "$host" -u "$user" $extra_args -e "SELECT 1;" 2>/dev/null && return 0
    
    return 1
}

# Function to create database with better error handling
create_database() {
    local host=$1
    local user=$2
    local password=$3
    local database=$4
    local extra_args=$5
    
    # Try with password if provided
    if [ -n "$password" ]; then
        $PHP_BIN -h "$host" -u "$user" -p"$password" $extra_args -e "CREATE DATABASE IF NOT EXISTS $database;" 2>/dev/null && return 0
    fi
    
    # Try without password
    $PHP_BIN -h "$host" -u "$user" $extra_args -e "CREATE DATABASE IF NOT EXISTS $database;" 2>/dev/null && return 0
    
    return 1
}

DB_CONNECTED=false
DB_CREATED=false

# Get database config from .env
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)
DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
DB_CONNECTION=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)

# Default values if not set
DB_HOST=${DB_HOST:-127.0.0.1}
DB_USERNAME=${DB_USERNAME:-root}
DB_DATABASE=${DB_DATABASE:-collection_manager}
DB_CONNECTION=${DB_CONNECTION:-mysql}

print_status "Database config: Connection=$DB_CONNECTION, Host=$DB_HOST, User=$DB_USERNAME, Database=$DB_DATABASE"

# Database setup for MySQL/MariaDB only
    # Check if MySQL/MariaDB service is running (for VPS)
    if command -v systemctl &> /dev/null; then
        if systemctl is-active --quiet mysql 2>/dev/null; then
            print_success "MySQL service is running"
        elif systemctl is-active --quiet mariadb 2>/dev/null; then
            print_success "MariaDB service is running"
        else
            print_warning "MySQL/MariaDB service not running. Trying to start..."
            systemctl start mysql 2>/dev/null || systemctl start mariadb 2>/dev/null || true
            sleep 3
        fi
    fi

    # Method 1: Standard TCP connection
    print_status "Testing standard MySQL connection..."
    if test_mysql_connection "$DB_HOST" "$DB_USERNAME" "$DB_PASSWORD"; then
        print_success "Standard MySQL connection successful"
        DB_CONNECTED=true
        if create_database "$DB_HOST" "$DB_USERNAME" "$DB_PASSWORD" "$DB_DATABASE"; then
            print_success "Database '$DB_DATABASE' created/verified"
            DB_CREATED=true
        fi
    fi

    # Method 2: Socket connections (for localhost)
    if [ "$DB_CONNECTED" = false ] && ([ "$DB_HOST" = "127.0.0.1" ] || [ "$DB_HOST" = "localhost" ]); then
        print_status "Trying socket connections..."
        for socket in /var/lib/mysql/mysql.sock /tmp/mysql.sock /var/run/mysqld/mysqld.sock; do
            if [ -S "$socket" ]; then
                print_status "Testing socket: $socket"
                if test_mysql_connection "$DB_HOST" "$DB_USERNAME" "$DB_PASSWORD" "--socket=$socket"; then
                    print_success "Socket connection successful: $socket"
                    DB_CONNECTED=true
                    if create_database "$DB_HOST" "$DB_USERNAME" "$DB_PASSWORD" "$DB_DATABASE" "--socket=$socket"; then
                        print_success "Database '$DB_DATABASE' created/verified via socket"
                        DB_CREATED=true
                    fi
                    break
                fi
            fi
        done
    fi

    # Method 3: Try without password (for fresh MySQL installations)
    if [ "$DB_CONNECTED" = false ]; then
        print_status "Trying connection without password..."
        if test_mysql_connection "$DB_HOST" "$DB_USERNAME" ""; then
            print_success "MySQL connection without password successful"
            DB_CONNECTED=true
            if create_database "$DB_HOST" "$DB_USERNAME" "" "$DB_DATABASE"; then
                print_success "Database '$DB_DATABASE' created/verified"
                DB_CREATED=true
            fi
        fi
    fi

    # Method 4: Try with different hosts
    if [ "$DB_CONNECTED" = false ]; then
        print_status "Trying alternative hosts..."
        for alt_host in localhost 127.0.0.1; do
            if [ "$alt_host" != "$DB_HOST" ]; then
                print_status "Testing host: $alt_host"
                if test_mysql_connection "$alt_host" "$DB_USERNAME" "$DB_PASSWORD"; then
                    print_success "MySQL connection successful with host: $alt_host"
                    DB_CONNECTED=true
                    if create_database "$alt_host" "$DB_USERNAME" "$DB_PASSWORD" "$DB_DATABASE"; then
                        print_success "Database '$DB_DATABASE' created/verified"
                        DB_CREATED=true
                    fi
                    break
                fi
            fi
        done
    fi

    # If still no connection, provide guidance
    if [ "$DB_CONNECTED" = false ]; then
        print_error "Kon geen verbinding maken met de MySQL/MariaDB database!"
        print_warning "Controleer je databasegegevens in het OVH control panel."
        print_warning "Op OVH shared hosting kun je de database meestal alleen via het control panel aanmaken."
        print_warning "Ga naar https://www.ovh.nl/manager/ en maak de database handmatig aan indien nodig."
        print_status "Ga verder met de deployment, maar migraties kunnen mislukken."
    fi

print_status "Running database migrations..."
if $PHP_BIN artisan migrate --force; then
    print_success "Database migrations completed successfully"
else
    print_error "Database migrations failed"
    print_warning "This may be due to:"
    print_warning "1. Database connection issues"
    print_warning "2. Missing database permissions"
    print_warning "3. Database server not running"
    print_warning ""
    print_warning "Please fix the database issues and run: $PHP_BIN artisan migrate --force"
    print_warning "Or use the database admin interface to manage the database"
fi

print_status "Running database seeders..."
if $PHP_BIN artisan db:seed --force; then
    print_success "Database seeding completed successfully"
else
    print_warning "Database seeding failed - this is optional for deployment"
fi

print_status "Publishing Spatie Permission assets..."
$PHP_BIN artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force || true

print_status "Creating storage link..."
$PHP_BIN artisan storage:link || true

print_status "Optimizing application..."
$PHP_BIN artisan optimize

print_status "Setting file permissions for OVH..."
# Set proper permissions for Laravel directories
find storage -type f -exec chmod 644 {} \; 2>/dev/null || true
find storage -type d -exec chmod 755 {} \; 2>/dev/null || true
find bootstrap/cache -type f -exec chmod 644 {} \; 2>/dev/null || true
find bootstrap/cache -type d -exec chmod 755 {} \; 2>/dev/null || true

# Make sure the application can write to necessary directories
chmod -R 755 storage/ 2>/dev/null || true
chmod -R 755 bootstrap/cache/ 2>/dev/null || true

print_success "File permissions set for OVH hosting"

print_status "Deployment completed!"
print_success "🎉 Collection Manager Laravel is now deployed on OVH!"
print_warning ""
print_warning "Next steps:"
print_warning "1. Configure your web server to point to the 'public' directory"
print_warning "2. Ensure .env file has correct database and application settings"
print_warning "3. Test the application in your browser"
print_warning "4. Configure OAuth credentials in .env if using social login"
print_warning "5. Set up VAPID keys for push notifications if needed"
print_warning ""
print_success "Deployment log completed successfully!" 

# === BEGIN TOEVOEGING: Platformdetectie en permissietips ===

if grep -qi ovh /etc/motd 2>/dev/null || grep -qi ovh /etc/issue 2>/dev/null; then
    print_status "OVH-platform gedetecteerd."
else
    print_warning "Let op: OVH-platform niet automatisch gedetecteerd. Controleer handmatig of je op OVH draait."
fi

if [ -d "/home" ] && [ -d "/var/www" ]; then
    print_status "Waarschijnlijk VPS. Gebruik 'chown -R www-data:www-data storage bootstrap/cache' indien mogelijk."
else
    print_status "Waarschijnlijk shared hosting. Gebruik chmod zoals hieronder, chown is meestal niet mogelijk."
fi

# === BEGIN TOEVOEGING: Eindcontrole of site bereikbaar is ===

if grep -q "^APP_URL=" .env; then
    APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2)
    print_status "Controleer of de site bereikbaar is op $APP_URL ..."
    if command -v curl &> /dev/null; then
        if curl -s --head "$APP_URL" | grep "200 OK" > /dev/null; then
            print_success "De site is bereikbaar!"
        else
            print_warning "De site is niet direct bereikbaar. Controleer je webserverconfiguratie en DNS."
        fi
    else
        print_warning "curl is niet geïnstalleerd, kan geen automatische check uitvoeren."
    fi
fi 