# PHP Collectiebeheer

Dit project wordt gedeployed via het Git control panel van je hostingprovider. Voor elke omgeving (development, test, acceptatie, productie) gebruik je een aparte branch en een aparte directory op de server.

Zie [DEPLOYMENT.md](./DEPLOYMENT.md) voor de volledige handleiding en instructies.

## Korte samenvatting deployment

- **Branch dev** → deployment naar development directory
- **Branch tst** → deployment naar test directory
- **Branch acc** → deployment naar acceptatie directory
- **Branch main** → deployment naar productie directory

Na een push naar een branch wordt de code automatisch uitgerold via het hosting control panel. Per omgeving gebruik je een eigen `.env`-bestand (zie `.env.template`).

**Let op:** Er is geen CI/CD pipeline of GitHub Actions workflow meer nodig voor deployment.

## 🚀 Functies

- **📱 Barcode Scanning**: Scan barcodes met uw telefoon of webcam
- **🎮 Multi-type Collecties**: Ondersteuning voor games, films en series  
- **🔍 Automatische Metadata**: Haalt automatisch informatie op via APIs
- **📊 Zoeken & Filteren**: Zoek en filter uw collectie
- **📱 Responsive Design**: Werkt perfect op mobiel en desktop
- **🚀 Auto-deployment**: Automatische uitrol via GitHub Actions

## 📋 Vereisten

- PHP 7.4 of hoger
- MySQL 5.7 of hoger
- Composer (optioneel)
- Webserver (Apache/Nginx)

## 🛠️ Installatie

### 1. Clone het project

```bash
git clone https://github.com/jouw-username/php-collection-manager.git
cd php-collection-manager
```

### 2. Configureer de omgeving

Kopieer `.env` bestand en pas aan:

```bash
cp .env .env.local
```

Vul de volgende variabelen in:

```env
# Database configuratie
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=your_db_name
DB_PREFIX=dev_

# API Keys
OMDB_API_KEY=your_omdb_key
IGDB_CLIENT_ID=your_igdb_client_id
IGDB_SECRET=your_igdb_secret

# Applicatie configuratie
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost
```

### 3. Database setup

De database tabellen worden automatisch aangemaakt bij het eerste gebruik. Zorg ervoor dat de database bestaat en de gebruiker de juiste rechten heeft.

### 4. Permissies instellen

```bash
chmod 777 uploads/
```

### 5. Start de applicatie

Ga naar `public/index.php` in uw browser.

## 🔧 API Configuratie

### OMDb API (Films/Series)

1. Krijg een gratis API key op [OMDb API](http://www.omdbapi.com/apikey.aspx)
2. Vul `OMDB_API_KEY` in uw `.env` bestand in

### IGDB API (Games)

1. Registreer op [Twitch Developers](https://dev.twitch.tv/)
2. Maak een nieuwe applicatie aan
3. Vul `IGDB_CLIENT_ID` en `IGDB_SECRET` in uw `.env` bestand in

### UPCitemDB (Fallback)

Deze API werkt zonder registratie, maar heeft rate limits.

## 📱 Barcode Scanning

De app ondersteunt verschillende manieren om barcodes in te voeren:

- **Camera scanning**: Gebruik de ingebouwde camera scanner
- **Handmatige invoer**: Type de barcode handmatig in
- **Ondersteunde formaten**: EAN, UPC (8-14 cijfers)

## 🗂️ Project Structuur

```
php-collection-manager/
├── public/                 # Frontend toegankelijke bestanden
│   └── index.php          # Hoofdapplicatie
├── includes/               # PHP backend bestanden
│   ├── db.php             # Database connectie
│   ├── env.php            # Environment configuratie
│   └── functions.php      # Core functionaliteit
├── assets/                 # Frontend assets
│   ├── css/style.css      # Styling
│   └── js/app.js          # JavaScript functionaliteit
├── uploads/                # Upload directory voor afbeeldingen
├── .github/workflows/      # GitHub Actions
│   └── deploy.yml         # Deployment workflow
├── .env                   # Environment configuratie
├── composer.json          # PHP dependencies
└── README.md             # Documentatie
```

## 🚀 Deployment

### GitHub Actions Setup

1. **Secrets configureren** in GitHub repository:

```
# SSH configuratie
SSH_PRIVATE_KEY=your_private_key
SSH_USER=your_ssh_user

# Development omgeving
DEV_HOST=dev.example.com
DEV_PATH=/var/www/dev

# Test omgeving  
TST_HOST=tst.example.com
TST_PATH=/var/www/tst

# Acceptatie omgeving
ACC_HOST=acc.example.com
ACC_PATH=/var/www/acc

# Productie omgeving
PRD_HOST=www.example.com
PRD_PATH=/var/www/prd
```

2. **Branches aanmaken**:
   - `dev` - Development omgeving
   - `tst` - Test omgeving  
   - `acc` - Acceptatie omgeving
   - `prd` - Productie omgeving

3. **Push naar branch** triggert automatische deployment

### Handmatige Deployment

```bash
# Sync bestanden naar server
rsync -avz --exclude='.git' --exclude='.env' . user@server:/var/www/html/

# Zet permissies
ssh user@server "cd /var/www/html && find . -type f -exec chmod 644 {} \; && find . -type d -exec chmod 755 {} \; && chmod 777 uploads/"
```

## 💾 Database Schema

### Items Tabel (`{prefix}items`)

```sql
CREATE TABLE dev_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type ENUM('game', 'film', 'serie') NOT NULL,
    barcode VARCHAR(20),
    platform VARCHAR(100),
    director VARCHAR(100), 
    publisher VARCHAR(100),
    description TEXT,
    cover_image VARCHAR(255),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### API Cache Tabel (`{prefix}api_cache`)

```sql
CREATE TABLE dev_api_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(20) NOT NULL,
    api_source VARCHAR(20) NOT NULL,
    metadata JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL
);
```

## 🔒 Beveiliging

- Alle input wordt gevalideerd en ge-escaped
- SQL injection bescherming via prepared statements  
- XSS bescherming via htmlspecialchars
- Environment variabelen worden niet gecommit
- HTTPS wordt aanbevolen voor productie

## 🐛 Troubleshooting

### Camera werkt niet

- Controleer HTTPS (required voor camera access)
- Geef browsertoestemming voor camera
- Test op verschillende browsers

### Database connectie faalt

- Controleer `.env` configuratie
- Verify database server is running
- Check gebruikersrechten

### API's werken niet

- Verify API keys in `.env`
- Check API rate limits
- Test API endpoints handmatig

### Deployment faalt

- Check GitHub Secrets configuratie
- Verify SSH toegang tot server
- Check server permissies

## 🔧 Development

### Local development setup

```bash
# Start PHP development server
cd public && php -S localhost:8000

# Of gebruik XAMPP/WAMP/MAMP
```

### Testing

```bash
# Check PHP syntax
composer run-script check-syntax

# Run security checks
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
```

## 📝 Changelog

### v1.0.0 (2024-01-XX)
- ✅ Eerste release
- ✅ Barcode scanning
- ✅ Multi-API metadata ophalen
- ✅ Responsive interface
- ✅ GitHub Actions deployment

## 🤝 Contributing

1. Fork het project
2. Maak een feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit je changes (`git commit -m 'Add some AmazingFeature'`)
4. Push naar de branch (`git push origin feature/AmazingFeature`)
5. Open een Pull Request

## 📄 Licentie

Dit project is gelicenseerd onder de MIT License - zie het [LICENSE](LICENSE) bestand voor details.

## 👥 Credits

- **Bootstrap** - Frontend framework
- **html5-qrcode** - Barcode scanning
- **OMDb API** - Film/serie metadata
- **IGDB API** - Game metadata
- **UPCitemDB** - Fallback product database

## 📞 Support

Voor vragen of problemen:

1. Check de [Issues](https://github.com/jouw-username/php-collection-manager/issues) pagina
2. Maak een nieuwe issue aan
3. Geef gedetailleerde informatie over het probleem

---

⭐ **Star dit project als het je geholpen heeft!**
