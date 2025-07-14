# 🚀 PHP Collectiebeheer

Een moderne web applicatie voor het beheren van persoonlijke collecties (games, films, boeken, muziek) met automatische metadata enrichment, barcode scanning en multi-user ondersteuning.

## ✨ Hoofdfuncties

- 📱 **Barcode Scanning** - Scan items met telefoon of webcam
- 🎮 **Multi-type Collecties** - Games, films, series, boeken, muziek
- 🔍 **Automatische Metadata** - Via IGDB, OMDb, OpenLibrary APIs
- 🌐 **Meertalig** - Nederlands, Engels, Duits, Frans, Spaans
- 🔐 **Veilige Authenticatie** - TOTP 2FA, OAuth (Google/Facebook)
- 📱 **Progressive Web App** - Offline functionaliteit, push notifications
- 🚀 **Auto-deployment** - GitHub Actions CI/CD pipeline

## 🚀 Quick Start

### Vereisten
- PHP 8.4+
- MySQL 8.0+
- Composer (optioneel)

### Installatie
```bash
# 1. Clone repository
git clone https://github.com/kfaessen/php-collection-manager.git
cd php-collection-manager

# 2. Configureer environment
cp env.template .env
# Bewerk .env met je database gegevens

# 3. Setup database
php setup_database.php

# 4. Run migraties
php run_migrations.php

# 5. Start webserver
php -S localhost:8000 -t public/
```

Ga naar `http://localhost:8000` en log in met:
- **Username**: `admin`
- **Password**: `admin123`

## 📚 Documentatie

Voor gedetailleerde informatie zie de documentatie in `/docs/`:

- **[Features](docs/FEATURES.md)** - Uitgebreide functionaliteit overzicht
- **[Deployment](docs/DEPLOYMENT.md)** - Installatie, configuratie en deployment
- **[API Reference](docs/API.md)** - API endpoints en integratie (TODO)
- **[Development](docs/DEVELOPMENT.md)** - Development setup en bijdragen (TODO)

## 🛠️ Technische Stack

- **Backend**: PHP 8.4, MySQL 8.0
- **Frontend**: Bootstrap 5, Vanilla JavaScript
- **APIs**: IGDB, OMDb, OpenLibrary, TMDb, Spotify
- **Features**: PWA, Push Notifications, OAuth, i18n, TOTP
- **Deployment**: GitHub Actions, SSH, Multi-environment

## 🔧 Configuratie

Basis configuratie via `.env`:

```env
# Database
DB_HOST=localhost
DB_NAME=collection_manager
DB_USER=root
DB_PASS=

# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# API Keys (optioneel)
OMDB_API_KEY=your_key_here
IGDB_CLIENT_ID=your_client_id
IGDB_SECRET=your_secret
```

Zie [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) voor volledige configuratie opties.

## 🚀 Deployment

### Development
```bash
git push origin main  # Auto-deploy naar development
```

### Production
Volledig geautomatiseerde deployment via GitHub Actions:
- Multi-environment support (dev/test/acc/prod)
- Automatische database migraties
- Zero-downtime deployment
- Rollback functionaliteit

Zie [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) voor setup instructies.

## 🤝 Bijdragen

1. Fork het project
2. Maak een feature branch (`git checkout -b feature/amazing-feature`)
3. Commit je wijzigingen (`git commit -m 'Add amazing feature'`)
4. Push naar branch (`git push origin feature/amazing-feature`)
5. Open een Pull Request

## 📄 Licentie

Dit project is gelicenseerd onder de MIT License - zie het [LICENSE](LICENSE) bestand voor details.

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/kfaessen/php-collection-manager/issues)
- **Documentatie**: [docs/](docs/)
- **Email**: [support@collectiebeheer.nl](mailto:support@collectiebeheer.nl)

## 🎯 Roadmap

- [ ] Mobile app (React Native)
- [ ] Advanced analytics dashboard
- [ ] Import/export functionaliteit
- [ ] Social sharing features
- [ ] AI-powered recommendations
- [ ] Marketplace integratie

---

**Gemaakt met ❤️ voor verzamelaars**
