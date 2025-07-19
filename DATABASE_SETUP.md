# Database Setup - Collection Manager

## Probleem
De MySQL verbinding werkt niet via command line, maar de applicatie heeft een admin interface voor database beheer.

## Oplossing: Database Setup via Admin Interface

### Stap 1: Database Aanmaken
1. Start MySQL server
2. Maak database aan:
   ```sql
   CREATE DATABASE collection_manager;
   ```

### Stap 2: .env Configuratie
Het .env bestand is al aangemaakt met de juiste instellingen:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=collection_manager
DB_USERNAME=root
DB_PASSWORD=password
```

### Stap 3: Start PHP Development Server
```bash
php -S localhost:8000 -t public
```

### Stap 4: Toegang tot Admin Interface
1. Ga naar: http://localhost:8000/admin/database
2. Log in met admin credentials (maak eerst een admin gebruiker aan)
3. Klik op "Run Migrations" knop

### Stap 5: Alternatief - Directe Migratie
Als PHP beschikbaar is in PATH:
```bash
php artisan migrate --force
php artisan db:seed --force
```

## Beschikbare Migraties
- `0001_01_01_000000_create_users_table.php` - Gebruikers tabel
- `0001_01_01_000001_create_cache_table.php` - Cache tabel
- `0001_01_01_000002_create_jobs_table.php` - Jobs tabel
- `2025_07_17_144812_add_totp_fields_to_users_table.php` - TOTP velden
- `2025_07_19_080820_create_collection_items_table.php` - Collectie items
- `2025_07_19_081610_create_permission_tables.php` - Permissies
- `2025_07_19_090000_create_shared_links_table.php` - Gedeelde links

## Admin Interface Functies
- Database verbinding testen
- Migraties uitvoeren
- Seeders uitvoeren
- Database resetten
- Tabel overzicht bekijken

## Troubleshooting
1. **MySQL niet bereikbaar**: Controleer of MySQL draait
2. **Database bestaat niet**: Maak database aan met `CREATE DATABASE collection_manager;`
3. **PHP niet gevonden**: Installeer PHP of gebruik de admin interface
4. **Permissie errors**: Controleer database gebruikers rechten

## Volgende Stappen
Na succesvolle migraties:
1. Maak een admin gebruiker aan
2. Configureer OAuth providers (optioneel)
3. Test de applicatie functionaliteit 