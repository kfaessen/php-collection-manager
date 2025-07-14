# 🚀 OVH Optimized Deployment Guide

## Overzicht
Deze handleiding beschrijft hoe je de Collection Manager applicatie optimaliseert en deployeer op OVH shared hosting met GitHub Actions, inclusief alle performance optimalisaties en veiligheidsmaatregelen.

## 📋 Vereisten

### OVH Hosting Requirements
- **OVH Shared Hosting** (Performance of hoger aanbevolen)
- **PHP 8.4** of hoger
- **MySQL 8.0** of hoger  
- **SSH toegang** (voor deployment automation)
- **SSL certificaat** (Let's Encrypt gratis beschikbaar)

### Development Requirements
- **GitHub repository** met admin toegang
- **Local development environment** (XAMPP/WAMP/Docker)
- **Composer** geïnstalleerd
- **SSH client** (Git Bash/Terminal)

## 🏗️ OVH Environment Setup

### 1. Database Configuratie
Log in bij je OVH control panel en maak een nieuwe database aan:

```bash
# Database details (example)
DB_HOST=mysql-collection.database.ovh.net
DB_NAME=collection_manager_prod
DB_USER=collection_user
DB_PASS=your_secure_random_password
```

### 2. SSH Toegang Activeren
1. Ga naar OVH Manager → Web Cloud → Hosting
2. Selecteer je hosting pakket
3. Ga naar "FTP-SSH" tab
4. Klik op "SSH aanvragen" 
5. Wacht op activatie (kan tot 24 uur duren)

### 3. Domain/Subdomain Setup
Voor verschillende environments:
```
Production:  https://collectie.jouwdomein.nl
Acceptance:  https://acc.collectie.jouwdomein.nl  
Test:        https://tst.collectie.jouwdomein.nl
Development: https://dev.collectie.jouwdomein.nl
```

## 🔑 GitHub Deployment Setup

### 1. SSH Key Generatie
Genereer SSH keys voor GitHub Actions:

```bash
# Genereer SSH key pair
ssh-keygen -t ed25519 -C "github-actions@jouwdomein.nl" -f ~/.ssh/ovh_deploy
```

### 2. SSH Key Installatie op OVH
```bash
# Kopieer public key naar OVH server
cat ~/.ssh/ovh_deploy.pub | ssh username@ssh.cluster000.hosting.ovh.net 'cat >> ~/.ssh/authorized_keys'

# Test de connectie
ssh -i ~/.ssh/ovh_deploy username@ssh.cluster000.hosting.ovh.net 'whoami'
```

### 3. GitHub Secrets Configuratie
Ga naar je repository → Settings → Secrets and variables → Actions

#### Repository Secrets:
```bash
SSH_PRIVATE_KEY=<private key content>
SSH_USER=<ovh_username>
SSH_HOST=ssh.cluster000.hosting.ovh.net
ADMIN_IP=<your_static_ip_for_maintenance_bypass>
```

#### Environment-Specific Secrets:

**Production Environment:**
```bash
DEPLOY_PATH=/home/username/www
PROD_URL=https://collectie.jouwdomein.nl
```

**Acceptance Environment:**
```bash
DEPLOY_PATH=/home/username/acc
ACC_URL=https://acc.collectie.jouwdomein.nl
```

**Test Environment:**
```bash
DEPLOY_PATH=/home/username/tst
TST_URL=https://tst.collectie.jouwdomein.nl
```

**Development Environment:**
```bash
DEPLOY_PATH=/home/username/dev
DEV_URL=https://dev.collectie.jouwdomein.nl
```

## 🌊 Branch Strategy & Deployment Flow

### Branch Setup
```bash
# Create environment branches
git checkout -b dev
git checkout -b tst  
git checkout -b acc
git checkout main  # production
```

### Deployment Triggers
- **Push naar `dev`** → Automatic deployment naar Development
- **Push naar `tst`** → Automatic deployment naar Test
- **Push naar `acc`** → Automatic deployment naar Acceptance  
- **Push naar `main`** → Automatic deployment naar Production (met maintenance mode)

### Feature Development Flow
```bash
# Feature development
git checkout dev
git checkout -b feature/new-feature
# ... develop feature ...
git commit -m "feat: implement new feature"
git push origin feature/new-feature

# Create PR naar dev branch
# After merge naar dev → automatic deploy naar development

# Promote through environments
git checkout tst
git merge dev
git push origin tst  # → deploys to test

git checkout acc  
git merge tst
git push origin acc  # → deploys to acceptance

git checkout main
git merge acc
git push origin main  # → deploys to production
```

## 🛠️ OVH Server Preparation

### 1. Directory Structure Setup
```bash
# Log in via SSH
ssh username@ssh.cluster000.hosting.ovh.net

# Create environment directories
mkdir -p ~/www ~/acc ~/tst ~/dev
mkdir -p ~/backups ~/logs

# Set proper permissions
chmod 755 ~/www ~/acc ~/tst ~/dev
chmod 750 ~/backups ~/logs
```

### 2. Composer Installation (if not available)
```bash
# Download and install Composer
cd ~
curl -sS https://getcomposer.org/installer | php
mv composer.phar ~/bin/composer
chmod +x ~/bin/composer

# Add to PATH (add to ~/.bashrc)
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

### 3. PHP Version Configuration
Create `.htaccess` in home directory (OVH specific):
```apache
# Force PHP 8.4 (adjust version as needed)
AddHandler application/x-httpd-php84 .php
```

## 📁 Environment Configuration

### 1. Production Environment (.env)
```bash
# Production configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://collectie.jouwdomein.nl

# Database
DB_HOST=mysql-collection.database.ovh.net
DB_NAME=collection_manager_prod
DB_USER=collection_user_prod
DB_PASS=very_secure_production_password

# Security
SESSION_SECRET=32_character_random_production_secret
ENCRYPTION_KEY=32_character_random_production_key

# Email (OVH SMTP or external)
SMTP_HOST=pro1.mail.ovh.net
SMTP_PORT=587
SMTP_USERNAME=contact@jouwdomein.nl
SMTP_PASSWORD=email_account_password
SMTP_FROM_EMAIL=contact@jouwdomein.nl
SMTP_FROM_NAME="Collectiebeheer"

# Performance
CACHE_ENABLED=true
ENABLE_COMPRESSION=true
ENABLE_MINIFICATION=true
```

### 2. Environment Deployment Script
Create `deploy-env.sh` in each environment:

```bash
#!/bin/bash
# OVH Environment Deployment Script

ENV_NAME=$1
DEPLOY_PATH=$2

echo "🚀 Starting deployment for $ENV_NAME environment"

# Create environment-specific .env if it doesn't exist
if [ ! -f "$DEPLOY_PATH/.env" ]; then
    echo "📝 Creating environment configuration..."
    cp "$DEPLOY_PATH/env.template" "$DEPLOY_PATH/.env"
    
    # Update environment-specific values
    sed -i "s/APP_ENV=development/APP_ENV=$ENV_NAME/" "$DEPLOY_PATH/.env"
    sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" "$DEPLOY_PATH/.env"
    
    echo "⚠️  Please update $DEPLOY_PATH/.env with correct database credentials"
fi

# Create logs directory
mkdir -p "$DEPLOY_PATH/logs"
chmod 755 "$DEPLOY_PATH/logs"

# Create cache directory if needed
mkdir -p "$DEPLOY_PATH/cache"
chmod 755 "$DEPLOY_PATH/cache"

echo "✅ Environment setup completed for $ENV_NAME"
```

## 🔧 OVH-Specific Optimizations

### 1. Database Performance
```sql
-- Optimize for OVH shared hosting
-- Create these indexes for better performance

-- Users table optimization
CREATE INDEX idx_users_email_verified ON users(email_verified);
CREATE INDEX idx_users_last_login ON users(last_login);
CREATE INDEX idx_users_created ON users(created_at);

-- Collection items optimization  
CREATE INDEX idx_items_user_type ON collection_items(user_id, type);
CREATE INDEX idx_items_created ON collection_items(created_at);
CREATE INDEX idx_items_title ON collection_items(title);

-- API cache optimization
CREATE INDEX idx_api_cache_expires ON api_cache(expires_at);
```

### 2. File Caching Strategy
```php
// Create cache configuration for OVH
// File: includes/Cache.php

class Cache {
    private static $cacheDir = 'cache/';
    
    public static function get($key, $default = null) {
        $file = self::getCacheFile($key);
        if (file_exists($file) && (time() - filemtime($file)) < 3600) {
            return unserialize(file_get_contents($file));
        }
        return $default;
    }
    
    public static function set($key, $value, $ttl = 3600) {
        $file = self::getCacheFile($key);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, serialize($value));
    }
    
    private static function getCacheFile($key) {
        return self::$cacheDir . md5($key) . '.cache';
    }
}
```

### 3. Image Optimization
```bash
# Create image optimization script
# File: scripts/optimize-images.sh

#!/bin/bash
echo "🖼️  Optimizing images for OVH hosting..."

# Install imagemagick if available
# convert -strip -interlace Plane -gaussian-blur 0.05 -quality 85% input.jpg output.jpg

# Optimize uploaded covers
find uploads/covers -name "*.jpg" -exec jpegoptim --max=85 --strip-all {} \;
find uploads/covers -name "*.png" -exec optipng -o5 {} \;

echo "✅ Image optimization completed"
```

## 📊 Monitoring & Maintenance

### 1. Health Check Setup
The deployment includes automatic health checks via `/health-check.php`. Monitor your environments:

```bash
# Check environment health
curl -s https://collectie.jouwdomein.nl/health-check | jq .

# Response example:
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00+01:00",
  "environment": "production",
  "database_version": 9,
  "checks": {
    "php": {"status": "ok", "version": "8.4.0"},
    "database": {"status": "ok", "message": "Database connection successful"},
    "permissions": {"status": "ok", "message": "File permissions correct"}
  }
}
```

### 2. Backup Strategy
```bash
# Automated backup script (runs via cron)
#!/bin/bash
# File: scripts/backup.sh

BACKUP_DIR="/home/username/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
mysqldump -h mysql-host -u username -p database_name > "$BACKUP_DIR/db_$DATE.sql"

# Files backup  
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" ~/www \
    --exclude='*.log' --exclude='vendor/' --exclude='.git/'

# Keep only last 10 backups
cd "$BACKUP_DIR"
ls -t db_*.sql | tail -n +11 | xargs rm -f
ls -t files_*.tar.gz | tail -n +6 | xargs rm -f

echo "✅ Backup completed: $DATE"
```

### 3. Log Rotation (OVH)
```bash
# Add to crontab: crontab -e
# Rotate logs weekly
0 2 * * 0 /home/username/scripts/rotate-logs.sh

# File: scripts/rotate-logs.sh
#!/bin/bash
LOG_DIR="/home/username/www/logs"
DATE=$(date +%Y%m%d)

# Rotate PHP error logs
if [ -f "$LOG_DIR/php_errors.log" ]; then
    mv "$LOG_DIR/php_errors.log" "$LOG_DIR/php_errors_$DATE.log"
    gzip "$LOG_DIR/php_errors_$DATE.log"
    touch "$LOG_DIR/php_errors.log"
    chmod 644 "$LOG_DIR/php_errors.log"
fi

# Remove logs older than 30 days
find "$LOG_DIR" -name "*.log.gz" -mtime +30 -delete
```

## 🚨 Troubleshooting

### Common OVH Issues

#### 1. "No such file or directory" tijdens deployment
```bash
# Check if path exists
ssh username@ssh.cluster000.hosting.ovh.net 'ls -la ~/www'

# Create missing directories
ssh username@ssh.cluster000.hosting.ovh.net 'mkdir -p ~/www/uploads/covers'
```

#### 2. Database connection errors
```bash
# Test database connectivity
ssh username@ssh.cluster000.hosting.ovh.net 'cd ~/www && php test_db.php'

# Check database credentials in .env
ssh username@ssh.cluster000.hosting.ovh.net 'head -20 ~/www/.env'
```

#### 3. Permission errors
```bash
# Fix file permissions
ssh username@ssh.cluster000.hosting.ovh.net 'cd ~/www && find . -type f -exec chmod 644 {} \; && find . -type d -exec chmod 755 {} \;'

# Fix upload permissions
ssh username@ssh.cluster000.hosting.ovh.net 'chmod -R 755 ~/www/uploads'
```

#### 4. Composer not found
```bash
# Install Composer locally
ssh username@ssh.cluster000.hosting.ovh.net 'cd ~ && curl -sS https://getcomposer.org/installer | php && mv composer.phar bin/composer'
```

### Performance Issues

#### 1. Slow database queries
```sql
-- Check slow queries
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Slow_queries';

-- Add missing indexes
ANALYZE TABLE collection_items;
```

#### 2. High memory usage
```php
// Monitor memory usage
echo "Memory usage: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
echo "Peak memory: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";
```

## 📈 Performance Metrics

### Expected Performance (OVH Hosting)
- **Page Load Time**: < 2 seconds (first visit)
- **Page Load Time**: < 500ms (cached)
- **Database Queries**: < 100ms average
- **Health Check**: < 1 second
- **Deployment Time**: 2-5 minutes
- **Backup Creation**: < 10 minutes

### Monitoring Tools
1. **OVH Manager**: Built-in resource monitoring
2. **Health Check**: `/health-check.php` endpoint
3. **Google PageSpeed**: Performance analysis
4. **GTmetrix**: Detailed performance metrics

## 🔮 Advanced Features

### 1. Auto-scaling (Pro hosting)
```bash
# Monitor resource usage
#!/bin/bash
# File: scripts/monitor-resources.sh

CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
MEMORY_USAGE=$(free | grep Mem | awk '{printf "%.2f", $3/$2 * 100.0}')

echo "CPU: $CPU_USAGE%, Memory: $MEMORY_USAGE%"

# Alert if usage too high
if (( $(echo "$CPU_USAGE > 80" | bc -l) )); then
    echo "⚠️  High CPU usage detected: $CPU_USAGE%"
fi
```

### 2. CDN Integration
```apache
# .htaccess - CDN headers
<IfModule mod_headers.c>
    # Enable CORS for CDN
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>
```

### 3. Database Connection Pooling
```php
// Enhanced database connection for high traffic
class DatabasePool {
    private static $connections = [];
    private static $maxConnections = 5;
    
    public static function getConnection() {
        if (count(self::$connections) < self::$maxConnections) {
            self::$connections[] = new PDO($dsn, $user, $pass);
        }
        return array_pop(self::$connections);
    }
    
    public static function releaseConnection($connection) {
        self::$connections[] = $connection;
    }
}
```

## 📞 Support

### OVH Support Resources
- **OVH Manager**: https://www.ovh.com/manager/
- **OVH Guides**: https://docs.ovh.com/
- **Support Ticket**: Via OVH Manager
- **Community**: https://community.ovh.com/

### Application Support
- **GitHub Issues**: Voor bugs en feature requests
- **Health Check**: Monitor deployment status
- **Error Logs**: Check `logs/php_errors.log`

## 🎯 Best Practices

### Security
1. **Gebruik HTTPS** voor alle environments
2. **Update regelmatig** dependencies via composer
3. **Monitor logs** voor suspicious activity
4. **Backup regelmatig** database en bestanden
5. **Gebruik sterke wachtwoorden** voor alle accounts

### Performance  
1. **Enable caching** in production
2. **Optimize images** voor web delivery
3. **Monitor database** performance  
4. **Use CDN** voor static assets
5. **Minify CSS/JS** in production

### Maintenance
1. **Test deployments** in acceptance eerst
2. **Monitor health checks** na deployment
3. **Keep backups** voor rollback mogelijkheden
4. **Update environment** configurations regelmatig
5. **Review logs** wekelijks voor issues

---

Deze handleiding zorgt ervoor dat je Collection Manager optimaal draait op OVH hosting met enterprise-level deployment practices! 🚀 