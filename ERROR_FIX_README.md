# Error 500 Fix - Profile.php en Admin.php

## Probleem
De bestanden `profile.php` en `admin.php` geven error 500 fouten. Dit komt waarschijnlijk door:

1. **Database connectie problemen** - De database bestaat niet of is niet toegankelijk
2. **Missing dependencies** - Sommige PHP classes of functies ontbreken
3. **Permission issues** - De gebruiker heeft niet de juiste rechten

## Oplossing

### Stap 1: Database Setup
Voer eerst het database setup script uit om ervoor te zorgen dat de database en tabellen bestaan:

```bash
php setup_database.php
```

Dit script zal:
- De database `collection_manager` aanmaken als deze niet bestaat
- Alle benodigde tabellen aanmaken
- Default groepen, rechten en een admin gebruiker aanmaken

### Stap 2: Controleer Database Configuratie
Controleer of het `.env` bestand correct is geconfigureerd:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=collection_manager
DB_USER=root
DB_PASS=

# Application Configuration
APP_ENV=development
```

### Stap 3: Test de Database Connectie
Voer het test script uit om te controleren of alles werkt:

```bash
php test_error.php
```

### Stap 4: Controleer PHP Extensies
Zorg ervoor dat de volgende PHP extensies zijn geïnstalleerd:
- `pdo`
- `pdo_mysql`
- `mbstring`
- `openssl` (voor password hashing)

### Stap 5: Controleer Bestandspermissies
Zorg ervoor dat de volgende directories schrijfbaar zijn:
- `uploads/`
- `logs/` (als deze bestaat)

```bash
chmod 755 uploads/
chmod 755 logs/
```

## Debug Bestanden

Ik heb debug versies van beide bestanden gemaakt:
- `debug_profile.php` - Test versie van profile.php
- `debug_admin.php` - Test versie van admin.php

Deze bestanden tonen gedetailleerde foutmeldingen om het exacte probleem te identificeren.

## Mogelijke Oorzaken en Oplossingen

### 1. Database Connectie Fout
**Symptoom**: "Database connection failed"
**Oplossing**: 
- Controleer of MySQL/MariaDB draait
- Controleer database credentials in `.env`
- Voer `setup_database.php` uit

### 2. Missing Tables
**Symptoom**: "Table doesn't exist"
**Oplossing**: 
- Voer `setup_database.php` uit om alle tabellen aan te maken

### 3. Permission Denied
**Symptoom**: "Access denied" of "Permission denied"
**Oplossing**:
- Controleer of de database gebruiker de juiste rechten heeft
- Voor admin.php: zorg ervoor dat de gebruiker in de 'admin' groep zit

### 4. PHP Fatal Error
**Symptoom**: "Class not found" of "Function not found"
**Oplossing**:
- Controleer of alle bestanden in de `includes/` directory bestaan
- Controleer of de autoloader correct werkt

## Default Login Gegevens

Na het uitvoeren van `setup_database.php` wordt een default admin gebruiker aangemaakt:
- **Username**: admin
- **Password**: admin123

## Test de Fix

1. Voer `setup_database.php` uit
2. Log in met de admin gebruiker
3. Test beide pagina's:
   - `profile.php` - zou het profiel moeten tonen
   - `admin.php` - zou het admin panel moeten tonen

## Als het Probleem Blijft Bestaan

1. Controleer de PHP error logs
2. Voer de debug bestanden uit
3. Controleer of alle dependencies correct zijn geïnstalleerd
4. Controleer of de web server correct is geconfigureerd

## Belangrijke Bestanden

- `setup_database.php` - Database setup script
- `debug_profile.php` - Debug versie van profile.php
- `debug_admin.php` - Debug versie van admin.php
- `test_error.php` - Database connectie test
- `.env` - Database configuratie
- `includes/functions.php` - Class loader
- `includes/Database.php` - Database connectie
- `includes/Authentication.php` - Authenticatie systeem
- `includes/UserManager.php` - Gebruikersbeheer