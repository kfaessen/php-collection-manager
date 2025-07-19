# 🔧 OVH MySQL Deployment Troubleshooting

## 🚨 Probleem: MySQL Connection Failed

### Foutmelding
```bash
mysql -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS collection_manager_test;"
ERROR 2002 (HY000): Can't connect to local MySQL server through socket '/var/run/mysqld/mysqld.sock' (2)
```

## 🔍 Oorzaken & Oplossingen

### 1. MySQL Service Niet Gestart

#### ✅ Oplossing: Start MySQL Service
```bash
# Check MySQL status
sudo systemctl status mysql

# Start MySQL als het gestopt is
sudo systemctl start mysql

# Enable MySQL om automatisch te starten
sudo systemctl enable mysql

# Restart MySQL
sudo systemctl restart mysql
```

### 2. MySQL Socket Path Probleem

#### ✅ Oplossing: Check Socket Locatie
```bash
# Find MySQL socket
sudo find /var -name "mysqld.sock" 2>/dev/null

# Alternatieve socket locaties
ls -la /var/run/mysqld/
ls -la /tmp/
ls -la /var/lib/mysql/
```

#### ✅ Gebruik juiste socket path
```bash
# Probeer verschillende socket paths
mysql -u root -ppassword --socket=/var/lib/mysql/mysql.sock -e "CREATE DATABASE IF NOT EXISTS collection_manager_test;"

mysql -u root -ppassword --socket=/tmp/mysql.sock -e "CREATE DATABASE IF NOT EXISTS collection_manager_test;"
```

### 3. MySQL Niet Geïnstalleerd op OVH

#### ✅ Oplossing: Installeer MySQL
```bash
# Update package lijst
sudo apt update

# Installeer MySQL Server
sudo apt install mysql-server -y

# Secure MySQL installatie
sudo mysql_secure_installation

# Start MySQL service
sudo systemctl start mysql
sudo systemctl enable mysql
```

### 4. Verkeerde Credentials

#### ✅ Oplossing: Reset MySQL Root Password
```bash
# Stop MySQL
sudo systemctl stop mysql

# Start MySQL in safe mode
sudo mysqld_safe --skip-grant-tables &

# Login zonder password
mysql -u root

# Reset password
USE mysql;
UPDATE user SET authentication_string=PASSWORD('your_new_password') WHERE User='root';
FLUSH PRIVILEGES;
exit;

# Restart MySQL normaal
sudo systemctl restart mysql
```

### 5. OVH Hosting Specifieke Database

#### ✅ Voor OVH Shared Hosting
```bash
# OVH gebruikt vaak externe database servers
# Check je OVH control panel voor:
# - Database hostname (niet localhost)
# - Database poort (vaak niet 3306)
# - Database naam
# - Username/password
```

## 🎯 OVH-Specifieke Oplossingen

### Option 1: OVH Database Service
```bash
# Als je OVH Database service gebruikt
mysql -h sql_server_address -u username -ppassword -e "CREATE DATABASE IF NOT EXISTS collection_manager_test;"

# Bijvoorbeeld:
mysql -h mysql5-21.pro -u username -ppassword -e "CREATE DATABASE IF NOT EXISTS collection_manager_test;"
```

### Option 2: Docker MySQL (OVH VPS)
```bash
# Als je Docker gebruikt op OVH VPS
docker run --name mysql-server -e MYSQL_ROOT_PASSWORD=password -d mysql:8.0

# Connect to Docker MySQL
docker exec -it mysql-server mysql -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS collection_manager_test;"
```

### Option 3: MariaDB (OVH Alternative)
```bash
# OVH gebruikt vaak MariaDB
sudo apt install mariadb-server -y
sudo systemctl start mariadb
sudo mysql_secure_installation

# Connect met MariaDB
mysql -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS collection_manager_test;"
```

## 🔧 Laravel .env Configuration voor OVH

### Voor OVH Shared Hosting
```env
DB_CONNECTION=mysql
DB_HOST=your_ovh_db_host.pro
DB_PORT=3306
DB_DATABASE=collection_manager
DB_USERNAME=your_ovh_username
DB_PASSWORD=your_ovh_password
```

### Voor OVH VPS
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=collection_manager
DB_USERNAME=root
DB_PASSWORD=your_password
```

## 🚀 Deployment Script Fix

### Update deploy-ovh.sh
```bash
#!/bin/bash

# Check if MySQL is running
if ! systemctl is-active --quiet mysql; then
    echo "Starting MySQL service..."
    sudo systemctl start mysql
    sleep 5
fi

# Try multiple connection methods
DB_EXISTS=false

# Method 1: Standard connection
if mysql -u root -ppassword -e "SELECT 1;" 2>/dev/null; then
    mysql -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS collection_manager;"
    DB_EXISTS=true
fi

# Method 2: Socket connection
if [ "$DB_EXISTS" = false ]; then
    for socket in /var/lib/mysql/mysql.sock /tmp/mysql.sock /var/run/mysqld/mysqld.sock; do
        if [ -S "$socket" ]; then
            if mysql -u root -ppassword --socket="$socket" -e "SELECT 1;" 2>/dev/null; then
                mysql -u root -ppassword --socket="$socket" -e "CREATE DATABASE IF NOT EXISTS collection_manager;"
                DB_EXISTS=true
                break
            fi
        fi
    done
fi

# Method 3: Use Laravel to create database
if [ "$DB_EXISTS" = false ]; then
    echo "MySQL direct connection failed, using Laravel migration..."
    php artisan migrate --force
else
    echo "Database created successfully"
    php artisan migrate --force
fi
```

## 🔍 Debug Commands

### Check MySQL Status
```bash
# Service status
sudo systemctl status mysql

# Process check
ps aux | grep mysql

# Port check
netstat -tlnp | grep 3306

# Socket check
ls -la /var/run/mysqld/
ls -la /var/lib/mysql/

# MySQL log
sudo tail -f /var/log/mysql/error.log
```

### Test Database Connection
```bash
# Test met verschillende methoden
mysql -u root -ppassword -e "SELECT VERSION();"
mysqladmin -u root -ppassword ping
mysqladmin -u root -ppassword status

# Test Laravel database
php artisan tinker
# In tinker: DB::connection()->getPdo();
```

## 🎯 Aanbevolen OVH Deployment Flow

1. **Check MySQL Status**
```bash
sudo systemctl status mysql
```

2. **Start MySQL indien nodig**
```bash
sudo systemctl start mysql
```

3. **Test Database Connection**
```bash
mysql -u root -ppassword -e "SELECT 1;"
```

4. **Create Database**
```bash
mysql -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS collection_manager;"
```

5. **Run Laravel Migrations**
```bash
php artisan migrate --force
php artisan db:seed --force
```

## 🏆 Quick Fix voor OVH

### Eén-lijn oplossing
```bash
sudo systemctl start mysql && mysql -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS collection_manager;" && php artisan migrate --force
```

---

**Status**: MySQL troubleshooting gids voor OVH deployment problemen! 🚀 