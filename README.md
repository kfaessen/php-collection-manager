# 🚀 Collection Manager - Laravel Versie

Een moderne Laravel applicatie voor het beheren van persoonlijke collecties (games, films, boeken, muziek) met automatische metadata enrichment, barcode scanning en multi-user ondersteuning.

## ✨ Hoofdfuncties

- 📱 **Barcode Scanning** - Scan items met telefoon of webcam
- 🎮 **Multi-type Collecties** - Games, films, series, boeken, muziek
- 🔍 **Automatische Metadata** - Via IGDB, OMDb, OpenLibrary APIs
- 🌐 **Meertalig** - Nederlands, Engels, Duits, Frans, Spaans
- 🔐 **Veilige Authenticatie** - TOTP 2FA, OAuth (Google/Facebook)
- 📱 **Progressive Web App** - Offline functionaliteit, push notifications
- 🚀 **Laravel Framework** - Moderne PHP framework met uitstekende features

## 🛠️ Technische Stack

- **Backend**: Laravel 11, PHP 8.1+
- **Database**: MySQL 8.0+
- **Frontend**: Bootstrap 5, Blade Templates
- **APIs**: IGDB, OMDb, OpenLibrary, TMDb, Spotify
- **Features**: PWA, Push Notifications, OAuth, i18n, TOTP
- **Packages**: Laravel Sanctum, Laravel Cashier, Spatie Permissions

## 🚀 Quick Start

### Vereisten
- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js & NPM (voor assets)

### Installatie

1. **Clone repository**
```bash
git clone <repository-url>
cd collection-manager-laravel
```

2. **Installeer dependencies**
```bash
composer install
npm install
```

3. **Configureer environment**
```bash
cp env.example .env
# Bewerk .env met je database gegevens
```

4. **Genereer application key**
```bash
php artisan key:generate
```

5. **Run database migrations**
```bash
php artisan migrate
```

6. **Seed database met test data**
```bash
php artisan db:seed
```

7. **Build assets**
```bash
npm run build
```

8. **Start development server**
```bash
php artisan serve
```

Ga naar `http://localhost:8000` en log in met:
- **Username**: `admin`
- **Password**: `admin123`

## 📚 Database Migrations

Het project gebruikt Laravel's migration systeem voor database schema management:

```bash
# Run alle migrations
php artisan migrate

# Rollback laatste migration
php artisan migrate:rollback

# Reset alle migrations en opnieuw runnen
php artisan migrate:fresh

# Status van migrations bekijken
php artisan migrate:status
```

### Beschikbare Migrations

1. `2014_10_12_000000_create_users_table.php` - Gebruikers tabel
2. `2014_10_12_100000_create_groups_table.php` - Groepen tabel
3. `2014_10_12_200000_create_permissions_table.php` - Permissions tabel
4. `2014_10_12_300000_create_user_groups_table.php` - User-Group pivot
5. `2014_10_12_400000_create_group_permissions_table.php` - Group-Permission pivot
6. `2014_10_12_500000_create_collection_items_table.php` - Collectie items

## 🔧 Configuratie

### Environment Variables

Basis configuratie via `.env`:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=collection_manager_laravel
DB_USERNAME=root
DB_PASSWORD=

# Application
APP_NAME="Collection Manager"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# API Keys (optioneel)
IGDB_CLIENT_ID=your_igdb_client_id
IGDB_CLIENT_SECRET=your_igdb_secret
OMDB_API_KEY=your_omdb_key
TMDB_API_KEY=your_tmdb_key
```

## 🏗️ Project Structuur

```
collection-manager-laravel/
├── app/
│   ├── Http/Controllers/     # Controllers
│   ├── Models/              # Eloquent Models
│   └── Providers/           # Service Providers
├── database/
│   ├── migrations/          # Database migrations
│   ├── seeders/            # Database seeders
│   └── factories/          # Model factories
├── resources/
│   ├── views/              # Blade templates
│   ├── js/                 # JavaScript files
│   └── css/                # CSS files
├── routes/
│   ├── web.php             # Web routes
│   └── api.php             # API routes
└── storage/                # File storage
```

## 🔐 Authenticatie & Permissions

Het project gebruikt Laravel's ingebouwde authenticatie systeem met Spatie Permissions:

### Gebruikersrollen
- **Admin** - Volledige toegang
- **Moderator** - Beperkte admin toegang
- **User** - Basis toegang
- **Guest** - Alleen lezen

### Permissions
- `manage_users` - Gebruikers beheren
- `manage_collections` - Collecties beheren
- `view_collections` - Collecties bekijken
- `edit_collections` - Collecties bewerken

## 📱 API Endpoints

### Collection Management
- `GET /collection` - Overzicht collectie
- `POST /collection` - Nieuw item toevoegen
- `GET /collection/{item}` - Item details
- `PUT /collection/{item}` - Item bewerken
- `DELETE /collection/{item}` - Item verwijderen

### API Routes
- `POST /api/collection/scan` - Barcode scannen
- `POST /api/collection/search` - Zoeken in collectie
- `POST /api/collection/share` - Deel link maken

## 🚀 Deployment

### Production Setup

1. **Configureer environment**
```bash
APP_ENV=production
APP_DEBUG=false
```

2. **Optimize voor productie**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. **Setup webserver**
- Apache/Nginx configuratie
- SSL certificaat
- File permissions

### Docker Deployment

```bash
# Build Docker image
docker build -t collection-manager .

# Run container
docker run -p 8000:8000 collection-manager
```

## 🧪 Testing

```bash
# Run alle tests
php artisan test

# Run specifieke test
php artisan test --filter=CollectionTest

# Code coverage
php artisan test --coverage
```

## 📊 Monitoring

### Health Checks
- `GET /up` - Application health check
- `GET /health` - Database connectivity check

### Logging
- Application logs: `storage/logs/laravel.log`
- Error tracking via Laravel Telescope (development)

## 🤝 Bijdragen

1. Fork het project
2. Maak een feature branch (`git checkout -b feature/amazing-feature`)
3. Commit je wijzigingen (`git commit -m 'Add amazing feature'`)
4. Push naar branch (`git push origin feature/amazing-feature`)
5. Open een Pull Request

## 📄 Licentie

Dit project is gelicenseerd onder de MIT License.

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/username/collection-manager-laravel/issues)
- **Documentatie**: [Laravel Docs](https://laravel.com/docs)
- **Email**: [support@collectiebeheer.nl](mailto:support@collectiebeheer.nl)

## 🎯 Roadmap

- [ ] Mobile app (React Native)
- [ ] Advanced analytics dashboard
- [ ] Import/export functionaliteit
- [ ] Social sharing features
- [ ] AI-powered recommendations
- [ ] Marketplace integratie
- [ ] Subscription system met Laravel Cashier

---

**Gemaakt met ❤️ en Laravel voor verzamelaars**
