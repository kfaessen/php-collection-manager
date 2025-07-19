# Collection Manager Laravel - OVH Deployment

<p align="center">
<img src="https://img.shields.io/badge/Laravel-12.x-red.svg" alt="Laravel Version">
<img src="https://img.shields.io/badge/PHP-8.2+-blue.svg" alt="PHP Version">
<img src="https://img.shields.io/badge/OVH-Optimized-green.svg" alt="OVH Optimized">
<img src="https://img.shields.io/badge/Deployment-Automated-orange.svg" alt="Automated Deployment">
</p>

## 🎯 Over Collection Manager Laravel

Een moderne collectie management applicatie gebouwd met Laravel 12, specifiek geoptimaliseerd voor OVH Linux hosting. Deze applicatie biedt uitgebreide functionaliteiten voor het beheren van collecties, gebruikers, en geavanceerde authenticatie.

## ✨ Hoofdfunctionaliteiten

### 🔐 Authenticatie & Beveiliging
- **Laravel Auth** - Standaard authenticatie systeem
- **TOTP (Two-Factor Authentication)** - Google Authenticator integratie
- **OAuth Social Login** - Google en Facebook integratie
- **Push Notifications** - Real-time notificaties
- **Role-Based Access Control** - Uitgebreide permissie systeem

### 📊 Admin Interface
- **Gebruikersbeheer** - Volledig CRUD voor gebruikers
- **Rollenbeheer** - Aanmaken en beheren van rollen
- **Permissiebeheer** - Granulaire toegangscontrole
- **Dashboard** - Overzicht van systeem statistieken

### 🗂️ Collectie Management
- **Collectie Items** - Beheren van collectie objecten
- **Metadata Enrichment** - Automatische data verrijking
- **Sharing System** - Delen van collecties via links
- **Search & Filter** - Geavanceerde zoekfunctionaliteit

## 🚀 OVH Deployment

### Snelle OVH Setup
```bash
# Clone repository
git clone https://github.com/your-repo/collection-manager-laravel.git
cd collection-manager-laravel

# Configureer OVH database in .env
cp .env.example .env
# Bewerk .env met je OVH database credentials

# Voer OVH deployment uit
./deploy-ovh.sh
```

### OVH GitHub Actions (Automatisch)
Configureer de volgende secrets in je GitHub repository:
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

**Push naar main/master** → Automatische OVH deployment met database migraties!

## 🔧 OVH Vereisten

### PHP Extensies (OVH Control Panel)
- pdo, pdo_mysql
- openssl, mbstring, tokenizer
- xml, ctype, json, bcmath, fileinfo

### OVH Database Configuratie
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_ovh_database_name
DB_USERNAME=your_ovh_database_user
DB_PASSWORD=your_ovh_database_password
```

## 📁 OVH Directory Structuur

### Shared Hosting
```
public_html/
├── index.php
├── .htaccess
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

## 🛠️ OVH Deployment Scripts

### OVH Deployment Commando's
```bash
# OVH-specifiek deployment
./deploy-ovh.sh                    # OVH deployment script
composer run deploy-ovh            # OVH composer script

# Handmatig voor OVH
php artisan migrate --force        # Force migraties voor OVH
php artisan db:seed --force        # Force seeding voor OVH
```

## 🔐 Default Credentials

Na de eerste deployment:
```
Email: admin@collectionmanager.local
Password: admin123
```

## 📚 OVH Documentatie

- **[DEPLOYMENT_OVH.md](DEPLOYMENT_OVH.md)** - Volledige OVH deployment gids
- **[OVH_DEPLOYMENT_SUMMARY.md](OVH_DEPLOYMENT_SUMMARY.md)** - OVH implementatie overzicht
- **[README_STAP5.md](README_STAP5.md)** - Geavanceerde features documentatie

## 🌐 OVH Support

### OVH-Specifieke Problemen
- **Database connectie** → Controleer OVH database credentials
- **Permission problemen** → Gebruik OVH deployment script
- **PHP extensies** → Activeer via OVH control panel
- **SSL certificaat** → Configureer via OVH control panel

### OVH Documentatie
- [OVH Hosting Guide](https://docs.ovh.com/gb/en/hosting/)
- [OVH VPS Guide](https://docs.ovh.com/gb/en/vps/)
- [OVH Database Guide](https://docs.ovh.com/gb/en/hosting/web_hosting_database/)

## 🎉 OVH Voordelen

### Voor OVH Gebruikers
- ✅ **OVH-specifieke configuratie** uit de box
- ✅ **Automatische database migraties**
- ✅ **OVH-optimized permissions**
- ✅ **OVH error handling** met support contact
- ✅ **OVH troubleshooting** documentatie

### Voor OVH Operations
- ✅ **OVH shared hosting** support
- ✅ **OVH VPS** support
- ✅ **OVH FTP deployment** automatisch
- ✅ **OVH SSH deployment** automatisch
- ✅ **OVH security best practices**

## 📄 Licentie

Deze applicatie is open-source software gelicenseerd onder de [MIT licentie](LICENSE).

---

## 🚀 OVH Production Ready

De Collection Manager Laravel applicatie is **volledig geoptimaliseerd voor OVH hosting** met automatische database migraties, OVH-specifieke error handling, en uitgebreide OVH documentatie.

**Klaar voor OVH productie deployment!** 🎯
