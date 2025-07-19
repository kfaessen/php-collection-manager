# Database Troubleshooting Guide

## MySQL Connectie Problemen

### Foutmelding: "Can't connect to local MySQL server through socket"

Dit probleem komt vaak voor bij OVH hosting wanneer de MySQL server niet bereikbaar is via de standaard socket.

#### Oplossingen:

1. **Controleer MySQL Service Status**
   ```bash
   systemctl status mysql
   systemctl status mariadb
   ```

2. **Start MySQL Service (VPS)**
   ```bash
   sudo systemctl start mysql
   # of
   sudo systemctl start mariadb
   ```

3. **Controleer Socket Locaties**
   ```bash
   ls -la /var/run/mysqld/
   ls -la /var/lib/mysql/
   ls -la /tmp/
   ```

4. **Test Verschillende Connectie Methoden**
   ```bash
   # TCP connectie
   mysql -h 127.0.0.1 -u root -p
   
   # Socket connectie
   mysql -S /var/run/mysqld/mysqld.sock -u root -p
   ```

### OVH Shared Hosting

Voor OVH shared hosting, gebruik de database credentials uit je OVH control panel:

```env
DB_CONNECTION=mysql
DB_HOST=your-ovh-db-host
DB_PORT=3306
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

### OVH VPS

Voor OVH VPS, installeer MySQL indien nodig:

```bash
sudo apt update
sudo apt install mysql-server
sudo mysql_secure_installation
```

## SQLite Fallback

Als MySQL niet beschikbaar is, kun je SQLite gebruiken:

### 1. Gebruik SQLite Deployment Script
```bash
chmod +x deploy-ovh-sqlite.sh
./deploy-ovh-sqlite.sh
```

### 2. Handmatige SQLite Configuratie
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

```bash
# Maak SQLite database
mkdir -p database
touch database/database.sqlite
chmod 664 database/database.sqlite

# Run migrations
php artisan migrate --force
```

## Database Permissies

### MySQL Permissies
```sql
-- Maak database aan
CREATE DATABASE IF NOT EXISTS collection_manager;

-- Geef rechten aan gebruiker
GRANT ALL PRIVILEGES ON collection_manager.* TO 'username'@'localhost';
GRANT ALL PRIVILEGES ON collection_manager.* TO 'username'@'%';
FLUSH PRIVILEGES;
```

### SQLite Permissies
```bash
# Zorg dat de database directory schrijfbaar is
chmod 755 database/
chmod 664 database/database.sqlite
```

## Debugging Tips

### 1. Test Database Connectie
```bash
# Test MySQL connectie
mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -e "SELECT 1;"

# Test SQLite connectie
sqlite3 database/database.sqlite "SELECT 1;"
```

### 2. Controleer PHP Extensions
```bash
php -m | grep -i mysql
php -m | grep -i sqlite
php -m | grep -i pdo
```

### 3. Controleer Laravel Database Configuratie
```bash
php artisan config:show database
```

### 4. Test Laravel Database Connectie
```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

## Veelvoorkomende Problemen

### 1. "Access denied for user"
- Controleer gebruikersnaam en wachtwoord
- Controleer host toegang (localhost vs %)
- Controleer database rechten

### 2. "Unknown database"
- Database bestaat niet
- Gebruiker heeft geen toegang tot database
- Database naam is incorrect

### 3. "Connection refused"
- MySQL service draait niet
- Verkeerde host/port
- Firewall blokkeert connectie

### 4. "Socket not found"
- MySQL draait niet
- Socket pad is incorrect
- Gebruik TCP connectie in plaats van socket

## Deployment Scripts

### Standaard Deployment (MySQL)
```bash
chmod +x deploy-ovh.sh
./deploy-ovh.sh
```

### SQLite Fallback Deployment
```bash
chmod +x deploy-ovh-sqlite.sh
./deploy-ovh-sqlite.sh
```

### Handmatige Deployment
```bash
# Installeer dependencies
composer install --no-dev --optimize-autoloader

# Configureer environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --force
php artisan db:seed --force

# Cache configuratie
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimaliseer
php artisan optimize
```

## Environment Variabelen

### MySQL Configuratie
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=collection_manager
DB_USERNAME=root
DB_PASSWORD=your_password
```

### SQLite Configuratie
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

### OVH Shared Hosting
```env
DB_CONNECTION=mysql
DB_HOST=your-ovh-db-host.ovh.net
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Support

Als je nog steeds problemen hebt:

1. Controleer de Laravel logs: `storage/logs/laravel.log`
2. Controleer de MySQL logs: `/var/log/mysql/error.log`
3. Test de database connectie handmatig
4. Gebruik SQLite als tijdelijke oplossing
5. Neem contact op met OVH support voor database toegang 