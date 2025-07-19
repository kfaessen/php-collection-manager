# PHP Migration Instructions - Collection Manager

## ✅ PHP is nu beschikbaar!

Nu PHP beschikbaar is, kunnen we de database migraties uitvoeren. Hier zijn de stappen:

## 🔧 Stap 1: Database Voorbereiden

1. **Start MySQL server** (als deze nog niet draait)
2. **Maak database aan**:
   ```sql
   CREATE DATABASE collection_manager;
   ```

## 🔧 Stap 2: Dependencies Installeren

Voer uit in de project directory:
```bash
php composer.phar install
```

## 🔧 Stap 3: Migraties Uitvoeren

### Optie A: Via Artisan Command
```bash
php artisan migrate --force
php artisan db:seed --force
```

### Optie B: Via Admin Interface
1. **Start PHP development server**:
   ```bash
   php -S localhost:8000 -t public
   ```

2. **Open browser** en ga naar:
   ```
   http://localhost:8000/admin/database
   ```

3. **Gebruik de admin interface** om migraties uit te voeren

## 📋 Beschikbare Commando's

- `php artisan migrate --force` - Voer alle migraties uit
- `php artisan db:seed --force` - Voer seeders uit
- `php artisan migrate:status` - Bekijk migratie status
- `php artisan migrate:rollback` - Rollback laatste migratie

## 🗄️ Database Schema

De volgende tabellen worden aangemaakt:
- `users` - Gebruikers en authenticatie
- `cache` - Laravel cache tabel
- `jobs` - Queue jobs
- `collection_items` - Collectie items
- `permissions` - Spatie permission tabellen
- `shared_links` - Gedeelde collectie links

## 🔐 Admin Interface

Na succesvolle migraties:
1. Maak een admin gebruiker aan
2. Log in op de admin interface
3. Beheer gebruikers, rollen en permissies
4. Test de collectie beheer functionaliteit

## ⚠️ Troubleshooting

1. **MySQL niet bereikbaar**: Controleer of MySQL draait
2. **Database bestaat niet**: Maak database aan met `CREATE DATABASE collection_manager;`
3. **Composer errors**: Controleer PHP extensies (pdo_mysql, mbstring, etc.)
4. **Permission errors**: Controleer database gebruikers rechten

## 🚀 Na Migraties

1. Test de applicatie functionaliteit
2. Configureer OAuth providers (optioneel)
3. Maak admin gebruikers aan
4. Test collectie beheer features

## 📁 Beschikbare Scripts

- `run_migrations_direct.php` - Database setup script
- `admin_migrate.php` - Admin interface helper
- `DATABASE_SETUP.md` - Uitgebreide setup instructies 