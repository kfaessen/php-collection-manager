# 🚀 PHP Collectiebeheer - Deployment Documentatie

Deze documentatie beschrijft alle aspecten van deployment, configuratie en onderhoud van de PHP Collectiebeheer applicatie.

## 📋 Vereisten

### Server Vereisten
- **PHP**: 8.4 of hoger
- **MySQL**: 8.0 of hoger
- **Webserver**: Apache/Nginx met mod_rewrite
- **SSH toegang**: Voor automatische deployment
- **SSL certificaat**: HTTPS vereist voor PWA functionaliteit

### PHP Extensions
- `pdo` en `pdo_mysql` - Database connectivity
- `mbstring` - Multi-byte string support
- `openssl` - Encryption en SSL
- `curl` - API calls
- `json` - JSON processing
- `gd` of `imagick` - Image processing (optioneel)

### Development Tools
- **Git**: Version control
- **Composer**: Dependency management
- **GitHub account**: Voor CI/CD pipeline

## 🌳 Environment Strategy

### Branch & Environment Mapping
| Branch | Environment | URL Pattern | Database Prefix |
|--------|-------------|-------------|-----------------|
| `main` | Production | `www.domain.com` | `prd_` |
| `acc` | Acceptance | `acc.domain.com` | `acc_` |
| `tst` | Test | `tst.domain.com` | `tst_` |
| `dev` | Development | `dev.domain.com` | `dev_` |

### Environment Configuration
Elke omgeving heeft zijn eigen `.env` bestand:

```bash
# Production (.env)
APP_ENV=production
APP_DEBUG=false
DB_PREFIX=prd_
DB_HOST=mysql-prod.domain.com
DB_NAME=collection_prod

# Acceptance (.env)
APP_ENV=acceptance
APP_DEBUG=false
DB_PREFIX=acc_
DB_HOST=mysql-acc.domain.com
DB_NAME=collection_acc

# Test (.env)
APP_ENV=test
APP_DEBUG=true
DB_PREFIX=tst_
DB_HOST=mysql-tst.domain.com
DB_NAME=collection_tst

# Development (.env)
APP_ENV=development
APP_DEBUG=true
DB_PREFIX=dev_
DB_HOST=mysql-dev.domain.com
DB_NAME=collection_dev
```

## 🚀 Automatische Deployment via GitHub Actions

### Workflow Overzicht
1. **Push naar branch** → Workflow getriggerd
2. **Pre-deployment validatie** → Syntax check, security audit
3. **Environment deployment** → SSH naar server, code update
4. **Database migraties** → Automatische schema updates
5. **Post-deployment** → Cleanup, backup management

### GitHub Secrets Configuratie
Configureer de volgende secrets in je GitHub repository:

```bash
# SSH Configuration
SSH_PRIVATE_KEY     # Private SSH key voor server toegang
SSH_USER           # SSH username
SSH_HOST           # Server hostname/IP

# Environment URLs (optioneel voor monitoring)
PROD_URL           # https://www.domain.com
ACC_URL            # https://acc.domain.com
TST_URL            # https://tst.domain.com
DEV_URL            # https://dev.domain.com

# Deployment Paths
DEPLOY_PATH        # Server pad waar code wordt gedeployed
```

### SSH Key Setup
1. **Genereer SSH key pair**:
```bash
ssh-keygen -t rsa -b 4096 -C "deployment@domain.com"
```

2. **Voeg public key toe aan server**:
```bash
cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
```

3. **Voeg private key toe aan GitHub Secrets** als `SSH_PRIVATE_KEY`

## 🏗️ OVH Hosting Specifieke Configuratie

### OVH Shared Hosting Setup
1. **Activeer SSH** in OVH control panel
2. **Stel PHP versie in** op 8.4 via `.htaccess`
3. **Configureer database** via OVH database manager
4. **SSL certificaat** activeren (Let's Encrypt gratis)

### OVH Specifieke .htaccess
```apache
# PHP Version
AddHandler application/x-httpd-php84 .php

# Security Headers
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>
```

### OVH File Permissions
```bash
# Set correct permissions voor OVH
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 755 uploads/
```

## 💾 Database Management

### Automatische Migraties
Het systeem gebruikt automatische database migraties:

```php
// Database versie wordt automatisch gedetecteerd
$currentVersion = Database::getCurrentVersion();
$targetVersion = Database::getTargetVersion();

// Migraties worden automatisch uitgevoerd
if ($currentVersion < $targetVersion) {
    Database::runMigrations($currentVersion);
}
```

### Handmatige Database Setup
Voor nieuwe installaties:

```bash
# 1. Maak database en gebruiker aan
mysql -u root -p
CREATE DATABASE collection_manager;
CREATE USER 'collection_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON collection_manager.* TO 'collection_user'@'localhost';
FLUSH PRIVILEGES;

# 2. Run setup script
php setup_database.php

# 3. Run migraties
php run_migrations.php
```

### Database Backup Strategy
```bash
# Automatische backups voor deployment
mysqldump -u user -p database > backup_$(date +%Y%m%d_%H%M%S).sql

# Cleanup oude backups (houd laatste 5)
ls -t backup_*.sql | tail -n +6 | xargs rm -f
```

## 🔧 Configuratie Management

### Environment Variabelen
Alle configuratie via `.env` bestanden:

```bash
# Database
DB_HOST=localhost
DB_NAME=collection_manager
DB_USER=collection_user
DB_PASS=secure_password
DB_PREFIX=prd_

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.domain.com

# API Keys
OMDB_API_KEY=your_omdb_key
IGDB_CLIENT_ID=your_igdb_client_id
IGDB_SECRET=your_igdb_secret

# Email (optioneel)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
SMTP_FROM=noreply@domain.com

# OAuth (optioneel)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_secret
FACEBOOK_APP_ID=your_facebook_app_id
FACEBOOK_APP_SECRET=your_facebook_secret

# Push Notifications (optioneel)
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
VAPID_SUBJECT=mailto:admin@domain.com
```

### Feature Flags
```php
// Schakel features in/uit per omgeving
'OAUTH_ENABLED' => true,
'API_ENABLED' => true,
'PUSH_NOTIFICATIONS_ENABLED' => true,
'I18N_ENABLED' => true,
'TOTP_ENABLED' => true,
'EMAIL_VERIFICATION_ENABLED' => true
```

## 🛡️ Veiligheid & Onderhoud

### Security Checklist
- [ ] **HTTPS** ingeschakeld op alle omgevingen
- [ ] **Strong passwords** voor database gebruikers
- [ ] **SSH keys** gebruikt in plaats van wachtwoorden
- [ ] **File permissions** correct ingesteld (644/755)
- [ ] **Database backups** regelmatig en getest
- [ ] **Error logging** ingeschakeld maar niet publiek toegankelijk
- [ ] **Debug mode** uitgeschakeld in productie
- [ ] **API keys** veilig opgeslagen in environment variabelen

### Monitoring & Logging
```php
// Error logging configuratie
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');

// Application logging
if (Environment::isDevelopment()) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}
```

### Onderhoud Taken
```bash
# Wekelijkse taken
php scripts/cleanup_logs.php          # Log files opruimen
php scripts/cleanup_sessions.php      # Oude sessies verwijderen
php scripts/optimize_database.php     # Database optimalisatie

# Maandelijkse taken
php scripts/backup_database.php       # Database backup
php scripts/update_api_cache.php      # API cache vernieuwen
php scripts/generate_reports.php      # Gebruiksstatistieken
```

## 🚨 Troubleshooting

### Veelvoorkomende Problemen

#### Database Connection Failed
```bash
# Check database configuratie
php -r "
$pdo = new PDO('mysql:host=localhost;dbname=collection_manager', 'user', 'pass');
echo 'Database connection successful';
"

# Check PHP extensions
php -m | grep -E "(pdo|mysql)"
```

#### File Permission Errors
```bash
# Fix file permissions
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 755 uploads/
```

#### Composer Autoload Issues
```bash
# Regenerate autoloader
composer dump-autoload --optimize

# Check PSR-4 compliance
composer validate --strict
```

### Log Locaties
```bash
# Application logs
/var/log/php/error.log
/path/to/app/logs/application.log

# Webserver logs
/var/log/apache2/error.log
/var/log/nginx/error.log

# Database logs
/var/log/mysql/error.log
```

## 📊 Performance Optimalisatie

### Caching Strategy
```php
// API response caching
$cacheKey = 'api_' . md5($request);
$cached = Cache::get($cacheKey);
if (!$cached) {
    $response = ApiClient::request($request);
    Cache::set($cacheKey, $response, 3600); // 1 hour
}
```

### Database Optimalisatie
```sql
-- Indexing voor performance
CREATE INDEX idx_user_items ON collection_items(user_id);
CREATE INDEX idx_type_category ON collection_items(type, category);
CREATE INDEX idx_created_at ON collection_items(created_at);

-- Query optimalisatie
EXPLAIN SELECT * FROM collection_items WHERE user_id = 1 AND type = 'game';
```

### Asset Optimalisatie
```bash
# CSS/JS minification
npm install -g uglifycss uglify-js
uglifycss assets/css/style.css > assets/css/style.min.css
uglifyjs assets/js/app.js > assets/js/app.min.js

# Image optimization
find uploads/ -name "*.jpg" -exec jpegoptim --max=85 {} \;
find uploads/ -name "*.png" -exec optipng -o7 {} \;
```

---

## 🔄 Deployment Checklist

### Pre-deployment
- [ ] Code review voltooid
- [ ] Tests passing
- [ ] Database migraties getest
- [ ] Environment configuratie gecontroleerd
- [ ] Backup gemaakt

### Deployment
- [ ] GitHub Actions workflow succesvol
- [ ] Database migraties uitgevoerd
- [ ] File permissions correct
- [ ] Environment variabelen geladen

### Post-deployment
- [ ] Applicatie toegankelijk
- [ ] Database connectiviteit getest
- [ ] API functionaliteit getest
- [ ] Error logs gecontroleerd
- [ ] Performance monitoring actief

### Rollback Procedure
```bash
# Automatische rollback bij deployment failure
git reset --hard $PREVIOUS_COMMIT
tar -xzf ../backups/backup_$(date +%Y%m%d).tar.gz
systemctl restart apache2
```

Voor meer gedetailleerde informatie over specifieke onderdelen, zie de technische documentatie in `/docs/`. 