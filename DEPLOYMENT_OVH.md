# OVH Deployment Guide - Collection Manager Laravel

## 🌐 OVH-Specifieke Deployment

Deze gids is specifiek geoptimaliseerd voor OVH Linux hosting (shared hosting en VPS).

## 🚀 Snelle Deployment

### Optie 1: Automatische Deployment (Aanbevolen)

#### GitHub Actions (Automatisch)
1. **Configureer GitHub Secrets** in je repository:
   ```
   OVH_FTP_SERVER=ftp.yourdomain.com
   OVH_FTP_USERNAME=your_ftp_username
   OVH_FTP_PASSWORD=your_ftp_password
   OVH_SERVER_DIR=/
   OVH_SSH_HOST=your_ovh_server_ip (alleen voor VPS)
   OVH_SSH_USER=your_ssh_username (alleen voor VPS)
   OVH_SSH_PRIVATE_KEY=your_ssh_private_key (alleen voor VPS)
   OVH_DEPLOY_PATH=/var/www/html (alleen voor VPS)
   ```

2. **Push naar main/master** → Automatische deployment

#### OVH Deployment Script
```bash
# Maak het script uitvoerbaar
chmod +x deploy-ovh.sh

# Voer OVH deployment uit
./deploy-ovh.sh
```

### Optie 2: Handmatige Deployment

#### Stap 1: Voorbereiding
```bash
# Clone repository
git clone https://github.com/your-repo/collection-manager-laravel.git
cd collection-manager-laravel

# Configureer .env voor OVH
cp .env.example .env
```

#### Stap 2: OVH Database Configuratie
Bewerk `.env` met je OVH database instellingen:
```env
APP_NAME="Collection Manager"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_ovh_database_name
DB_USERNAME=your_ovh_database_user
DB_PASSWORD=your_ovh_database_password

# OAuth (optioneel)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_client_secret

# Push Notifications (optioneel)
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
VAPID_SUBJECT=mailto:admin@yourdomain.com
```

#### Stap 3: Dependencies Installeren
```bash
# Installeer Composer dependencies
composer install --no-dev --optimize-autoloader

# Genereer application key
php artisan key:generate

# Optimaliseer applicatie
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

#### Stap 4: Database Setup
```bash
# Voer migraties uit
php artisan migrate --force

# Voer seeders uit
php artisan db:seed --force
```

#### Stap 5: Upload naar OVH
Upload alle bestanden naar je OVH hosting directory via FTP/SFTP.

#### Stap 6: OVH Permissions
```bash
# Stel juiste permissions in voor OVH
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chmod -R 775 storage/framework/cache
chmod -R 775 storage/framework/sessions
chmod -R 775 storage/framework/views

# Maak storage symlink
php artisan storage:link
```

## 🔧 OVH-Specifieke Configuratie

### Database Instellingen
- **Host**: `localhost` (meestal)
- **Database naam**: Van je OVH control panel
- **Gebruikersnaam**: Van je OVH control panel
- **Wachtwoord**: Van je OVH control panel

### PHP Vereisten
Zorg ervoor dat de volgende PHP extensies zijn ingeschakeld in je OVH control panel:
- pdo
- pdo_mysql
- openssl
- mbstring
- tokenizer
- xml
- ctype
- json
- bcmath
- fileinfo

### Bestandsrechten
```bash
# Directories
chmod 755 /path/to/your/app
chmod 755 /path/to/your/app/storage
chmod 755 /path/to/your/app/bootstrap/cache

# Bestanden
chmod 644 /path/to/your/app/.env
chmod 644 /path/to/your/app/public/index.php
```

## 📁 OVH Directory Structuur

### Shared Hosting
```
public_html/
├── index.php
├── .htaccess
├── favicon.ico
├── robots.txt
└── storage/ (symlink)
```

### VPS
```
/var/www/html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
└── artisan
```

## 🌐 Web Server Configuratie

### Apache (.htaccess)
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### Nginx (voor VPS)
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 🔐 OVH Security Checklist

### Production Instellingen
- [ ] `APP_DEBUG=false` in .env
- [ ] `APP_ENV=production` in .env
- [ ] SSL certificaat geconfigureerd
- [ ] Database credentials beveiligd
- [ ] File permissions correct ingesteld
- [ ] .env bestand niet toegankelijk via web

### OVH-Specifieke Beveiliging
- [ ] FTP/SFTP credentials beveiligd
- [ ] Database toegang beperkt tot localhost
- [ ] Backup strategie ingesteld
- [ ] Error logging geconfigureerd

## 📊 Monitoring en Troubleshooting

### OVH Error Logs
```bash
# Apache error logs (VPS)
tail -f /var/log/apache2/error.log

# Nginx error logs (VPS)
tail -f /var/log/nginx/error.log

# Laravel logs
tail -f storage/logs/laravel.log
```

### Veelvoorkomende OVH Problemen

#### Database Connectie Fout
```bash
# Controleer database instellingen
php artisan config:show database

# Test database connectie
php artisan db:show
```

#### Permission Problemen
```bash
# Fix OVH permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Composer Problemen
```bash
# Clear Composer cache
composer clear-cache

# Reinstall dependencies
composer install --no-dev --optimize-autoloader
```

## 🚀 OVH Deployment Scripts

### Automatische Deployment
```bash
# Voer OVH deployment script uit
./deploy-ovh.sh
```

### Composer Deployment
```bash
# Voer composer deploy script uit
composer run deploy
```

### GitHub Actions
De applicatie heeft een OVH-specifieke GitHub Actions workflow die automatisch wordt uitgevoerd.

## 📞 OVH Support

### Contact OVH Support voor:
- PHP extensie activatie
- Database configuratie problemen
- SSL certificaat installatie
- Server performance issues
- Backup configuratie

### OVH Documentatie
- [OVH Hosting Guide](https://docs.ovh.com/gb/en/hosting/)
- [OVH VPS Guide](https://docs.ovh.com/gb/en/vps/)
- [OVH Database Guide](https://docs.ovh.com/gb/en/hosting/web_hosting_database/)

## 🎉 Post-Deployment

### Test Checklist
- [ ] Website laadt correct
- [ ] Login functionaliteit werkt
- [ ] Admin panel toegankelijk
- [ ] Database operaties werken
- [ ] TOTP authenticatie werkt (indien geconfigureerd)
- [ ] OAuth login werkt (indien geconfigureerd)
- [ ] Push notifications werken (indien geconfigureerd)

### Default Credentials
```
Email: admin@collectionmanager.local
Password: admin123
```

### Monitoring
- Controleer regelmatig de Laravel logs
- Monitor database performance
- Check SSL certificaat status
- Backup database regelmatig

---

## 🎯 OVH Deployment Samenvatting

De Collection Manager Laravel applicatie is nu geoptimaliseerd voor OVH hosting met:

- ✅ **OVH-specifieke deployment scripts**
- ✅ **Automatische database migraties**
- ✅ **OVH-optimized permissions**
- ✅ **GitHub Actions voor automatische deployment**
- ✅ **Uitgebreide error handling**
- ✅ **OVH-specifieke documentatie**

Je applicatie is nu klaar voor productie op OVH! 🚀 