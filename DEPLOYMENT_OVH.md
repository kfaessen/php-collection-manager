# OVH Deployment Guide - Collection Manager Laravel

## 🌐 OVH-Specifieke Deployment

Deze gids is specifiek geoptimaliseerd voor OVH Linux hosting (shared hosting en VPS).

## 🚀 Snelle Deployment

### Optie 1: Automatische Deployment (Aanbevolen)

#### GitHub Actions (Automatisch)
1. **Configureer GitHub Secrets** in je repository:
   ```
   OVH_FTP_SERVER=ftp.jouwdomein.com
   OVH_FTP_USERNAME=je_ftp_gebruiker
   OVH_FTP_PASSWORD=je_ftp_wachtwoord
   OVH_SERVER_DIR=/
   OVH_SSH_HOST=ip_of_hostname_van_ovh (voor VPS)
   OVH_SSH_USER=ssh_gebruiker (voor VPS)
   OVH_SSH_PRIVATE_KEY=private_key (voor VPS)
   OVH_DEPLOY_PATH=/var/www/html (voor VPS)
   ```

2. **Push naar de juiste branch** (zie deploy.yml voor branch/omgeving mapping) → Automatische deployment

#### OVH Deployment Script
```bash
# Maak het script uitvoerbaar
chmod +x deploy-ovh.sh

# Voer OVH deployment uit
./deploy-ovh.sh
```

> **Nieuw:** Het script detecteert automatisch de juiste PHP- en Composer-binary (ook voor shared hosting met afwijkende commando's).
> Essentiële .env-variabelen worden vóór de deployment gevalideerd. Fouten worden direct gemeld met OVH-specifieke tips.
> Na afloop wordt optioneel gecontroleerd of de site bereikbaar is (indien curl en APP_URL beschikbaar zijn).

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
Bewerk `.env` met je OVH database instellingen. Het script controleert nu of de belangrijkste variabelen zijn ingevuld:
```env
APP_NAME="Collection Manager"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jouwdomein.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ovh_db_naam
DB_USERNAME=ovh_db_gebruiker
DB_PASSWORD=ovh_db_wachtwoord
```

#### Stap 3: Dependencies Installeren
```bash
# Installeer Composer dependencies
./deploy-ovh.sh  # (aanbevolen, regelt alles automatisch)
# of handmatig:
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

> **Tip:** Het script geeft nu automatisch permissie-advies op basis van het platform (shared/VPS) en detecteert veelvoorkomende valkuilen.

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

## 🛠️ Troubleshooting & Veelvoorkomende Fouten

- **PHP/Composer niet gevonden:** Het script zoekt automatisch naar de juiste binary. Controleer of PHP en Composer (of composer.phar) aanwezig zijn.
- **.env variabelen missen:** Het script stopt en meldt welke variabelen ontbreken. Vul deze aan in je .env.
- **Databasefout:** Op OVH shared hosting kun je de database alleen via het OVH control panel aanmaken. Het script geeft een duidelijke melding.
- **Permissies:** Het script geeft advies over chmod/chown afhankelijk van het platform. Op shared hosting is chown meestal niet mogelijk.
- **Site niet bereikbaar:** Het script controleert na afloop of de site bereikbaar is (indien curl en APP_URL beschikbaar zijn). Controleer je DNS en webserverconfiguratie.

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

## 🔄 Automatische Deployment via GitHub Actions

Zie `.github/workflows/deploy.yml` voor de volledige workflow. Let op:
- De juiste branch moet overeenkomen met de gewenste omgeving (zie mapping in deploy.yml).
- Secrets moeten correct zijn ingesteld in GitHub.
- Deployment gebeurt via SSH (FTP kan optioneel toegevoegd worden). 