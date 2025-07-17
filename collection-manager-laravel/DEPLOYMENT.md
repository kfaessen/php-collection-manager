# Deployment Guide - Collection Manager Laravel

## Overzicht

Deze gids beschrijft hoe je de Collection Manager Laravel applicatie kunt deployen met automatische database migraties.

## Deployment Opties

### 1. Automatische Deployment Scripts

#### Linux/macOS (deploy.sh)
```bash
# Maak het script uitvoerbaar
chmod +x deploy.sh

# Voer deployment uit
./deploy.sh
```

#### Windows (deploy.bat)
```cmd
# Voer deployment uit
deploy.bat
```

### 2. Composer Deployment Script
```bash
# Voer het composer deploy script uit
composer run deploy
```

### 3. GitHub Actions (Automatisch)
De applicatie heeft een GitHub Actions workflow die automatisch wordt uitgevoerd bij pushes naar main/master branch.

## Wat gebeurt er tijdens deployment?

### Database Operaties
✅ **Automatische migraties** - Alle database wijzigingen worden toegepast  
✅ **Database seeding** - Standaard data wordt ingevoegd  
✅ **Connection verificatie** - Database connectie wordt getest  
✅ **Error handling** - Deployment gaat door zelfs als database niet beschikbaar is  

### Applicatie Optimalisatie
✅ **Composer dependencies** - Alle packages worden geïnstalleerd  
✅ **Configuration caching** - Configuratie wordt gecached voor betere performance  
✅ **Route caching** - Routes worden gecached  
✅ **View caching** - Views worden gecached  
✅ **Application key** - Laravel app key wordt gegenereerd  
✅ **Storage symlink** - Storage link wordt aangemaakt  
✅ **Permissions** - Juiste bestandsrechten worden ingesteld  

### Health Checks
✅ **PHP extensies** - Vereiste extensies worden gecontroleerd  
✅ **Laravel health** - Applicatie gezondheid wordt getest  
✅ **Database status** - Database status wordt gecontroleerd  

## Voorbereiding voor Deployment

### 1. Environment Configuratie

Maak een `.env` bestand aan met de juiste instellingen:

```env
APP_NAME="Collection Manager"
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=collection_manager
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# OAuth Configuration (optioneel)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_client_secret

# Push Notifications (optioneel)
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
VAPID_SUBJECT=mailto:admin@yourdomain.com
```

### 2. Database Setup

Zorg ervoor dat je MySQL database bestaat en toegankelijk is:

```sql
CREATE DATABASE collection_manager;
CREATE USER 'collection_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON collection_manager.* TO 'collection_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. PHP Vereisten

Zorg ervoor dat de volgende PHP extensies zijn geïnstalleerd:
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

## Deployment Stappen

### Stap 1: Code Uploaden
```bash
# Clone de repository
git clone https://github.com/your-repo/collection-manager-laravel.git
cd collection-manager-laravel

# Of pull de laatste wijzigingen
git pull origin main
```

### Stap 2: Dependencies Installeren
```bash
# Installeer Composer dependencies
composer install --no-dev --optimize-autoloader
```

### Stap 3: Environment Configureren
```bash
# Kopieer environment file
cp .env.example .env

# Genereer application key
php artisan key:generate

# Configureer je database instellingen in .env
```

### Stap 4: Database Migraties
```bash
# Voer migraties uit
php artisan migrate --force

# Voer seeders uit
php artisan db:seed --force
```

### Stap 5: Optimalisatie
```bash
# Cache configuratie
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimaliseer applicatie
php artisan optimize
```

### Stap 6: Permissions
```bash
# Stel juiste permissions in
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chmod -R 775 storage/framework/cache
chmod -R 775 storage/framework/sessions
chmod -R 775 storage/framework/views

# Maak storage symlink
php artisan storage:link
```

## GitHub Actions Deployment

### Automatische Deployment
De applicatie heeft een GitHub Actions workflow die automatisch wordt uitgevoerd:

1. **Test Job** - Voert tests uit met MySQL database
2. **Deploy Job** - Deployt naar Linux server
3. **Deploy Windows Job** - Deployt naar Windows server

### GitHub Secrets Configuratie
Configureer de volgende secrets in je GitHub repository:

- `SSH_HOST` - Je server hostname/IP
- `SSH_USER` - SSH gebruikersnaam
- `SSH_PRIVATE_KEY` - SSH private key
- `DEPLOY_PATH` - Pad naar je applicatie op de server

### Workflow Triggers
- **Push naar main/master** - Automatische deployment
- **Pull Request naar main/master** - Alleen tests

## Monitoring en Troubleshooting

### Deployment Status Controleren
```bash
# Controleer applicatie status
php artisan about

# Controleer database connectie
php artisan db:show

# Controleer migratie status
php artisan migrate:status

# Controleer cache status
php artisan config:clear
php artisan cache:clear
```

### Logs Bekijken
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Error logs
tail -f storage/logs/error.log

# Deployment logs
tail -f storage/logs/deployment.log
```

### Veelvoorkomende Problemen

#### Database Connectie Fout
```bash
# Controleer database instellingen
php artisan config:show database

# Test database connectie
php artisan db:show
```

#### Permission Problemen
```bash
# Fix permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Cache Problemen
```bash
# Clear alle caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Post-Deployment

### 1. Test de Applicatie
- Bezoek je website
- Test login functionaliteit
- Controleer admin panel
- Test TOTP authenticatie (indien geconfigureerd)

### 2. Configureer Web Server
Zorg ervoor dat je web server (Apache/Nginx) naar de `public` directory wijst.

#### Apache (.htaccess)
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/collection-manager-laravel/public
    
    <Directory /path/to/collection-manager-laravel/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/collection-manager-laravel/public;
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
}
```

### 3. SSL Certificaat
Configureer SSL voor productie gebruik:
```bash
# Met Let's Encrypt
sudo certbot --nginx -d yourdomain.com

# Of met eigen certificaat
# Configureer in je web server
```

### 4. Monitoring Setup
```bash
# Installeer monitoring tools
composer require spatie/laravel-health

# Configureer health checks
php artisan vendor:publish --provider="Spatie\Health\HealthServiceProvider"
```

## Rollback Procedure

### Database Rollback
```bash
# Rollback laatste migratie
php artisan migrate:rollback

# Rollback specifiek aantal stappen
php artisan migrate:rollback --step=5

# Reset hele database
php artisan migrate:fresh --seed
```

### Code Rollback
```bash
# Ga terug naar vorige commit
git reset --hard HEAD~1

# Of naar specifieke commit
git reset --hard <commit-hash>

# Voer deployment opnieuw uit
./deploy.sh
```

## Beveiliging

### Production Checklist
- [ ] `APP_DEBUG=false` in .env
- [ ] `APP_ENV=production` in .env
- [ ] SSL certificaat geconfigureerd
- [ ] Database credentials beveiligd
- [ ] File permissions correct ingesteld
- [ ] Firewall geconfigureerd
- [ ] Regular backups ingesteld

### Backup Strategie
```bash
# Database backup
mysqldump -u username -p collection_manager > backup.sql

# Code backup
tar -czf code-backup.tar.gz /path/to/application

# Automatische backups
# Configureer cron job voor dagelijkse backups
```

## Support

Voor vragen of problemen:
1. Controleer de logs in `storage/logs/`
2. Bekijk de Laravel documentatie
3. Controleer de README_STAP5.md voor advanced features
4. Open een issue op GitHub

---

**Succesvolle deployment! 🎉**

Je Collection Manager Laravel applicatie is nu live met automatische database migraties! 