# Setup Wizard - Collection Manager

## 🎯 Overzicht

Collection Manager heeft nu een WordPress-achtige setup wizard die automatisch verschijnt bij de eerste keer dat je de applicatie bezoekt. Dit maakt het installeren en configureren van de applicatie veel eenvoudiger dan command line migraties.

## ✨ Features

### 🚀 Eerste Installatie
- **Automatische detectie**: De wizard verschijnt automatisch als de applicatie nog niet is geconfigureerd
- **Database configuratie**: Eenvoudige database instellingen via web interface
- **Automatische migraties**: Database tabellen worden automatisch aangemaakt
- **Admin gebruiker**: Eerste admin gebruiker aanmaken via de wizard
- **Progress tracking**: Visuele voortgangsindicator door alle stappen

### 🔄 Database Upgrades
- **Upgrade detectie**: Automatische detectie van nieuwe migraties
- **Veilige upgrades**: Backup waarschuwingen en veilige upgrade procedure
- **Status overzicht**: Duidelijk overzicht van database status
- **Admin interface**: Upgrades uitvoeren via admin dashboard

## 📋 Setup Proces

### Stap 1: Welcome
- Welkomstpagina met applicatie features
- Overzicht van het setup proces
- Start knop naar database configuratie

### Stap 2: Database Configuratie
- Database host, port, naam instellen
- Gebruikersnaam en wachtwoord configureren
- Test database verbinding
- Automatische database aanmaak
- Migraties uitvoeren

### Stap 3: Admin Gebruiker
- Admin gebruiker gegevens invoeren
- Wachtwoord bevestiging
- Automatische rol toewijzing
- Setup voltooiing markeren

### Stap 4: Setup Voltooid
- Succes bericht
- Links naar login en admin dashboard
- Volgende stappen suggesties

## 🔧 Technische Details

### SetupController
```php
// Controleert of applicatie is geconfigureerd
public function checkSetup()

// Database configuratie en migraties
public function saveDatabase()

// Admin gebruiker aanmaken
public function createAdmin()

// Database upgrades voor bestaande installaties
public function runUpgrade()
```

### SetupMiddleware
```php
// Automatische redirect naar setup wizard
// Skip setup check voor setup routes
// Controleert database status
```

### Routes
```php
// Setup routes (moeten voor andere routes staan)
Route::get('/setup', [SetupController::class, 'checkSetup']);
Route::get('/setup/welcome', [SetupController::class, 'welcome']);
Route::get('/setup/database', [SetupController::class, 'database']);
Route::post('/setup/save-database', [SetupController::class, 'saveDatabase']);
Route::get('/setup/admin', [SetupController::class, 'admin']);
Route::post('/setup/create-admin', [SetupController::class, 'createAdmin']);
Route::get('/setup/complete', [SetupController::class, 'complete']);
Route::get('/setup/upgrade', [SetupController::class, 'upgrade']);
Route::post('/setup/run-upgrade', [SetupController::class, 'runUpgrade']);
```

## 🎨 UI/UX Features

### Moderne Design
- Gradient achtergronden
- Moderne typografie
- Responsive design
- Progress indicators
- Loading spinners

### Gebruiksvriendelijkheid
- Duidelijke stappen
- Foutmeldingen
- Success feedback
- Intuïtieve navigatie

## 🔒 Veiligheid

### Database Veiligheid
- Wachtwoord validatie
- Database verbinding testen
- Backup waarschuwingen
- Veilige .env updates

### Admin Veiligheid
- Sterke wachtwoord vereisten
- Email validatie
- Unieke gebruikersnamen
- Automatische rol toewijzing

## 📱 Responsive Design

De setup wizard is volledig responsive en werkt op:
- Desktop computers
- Tablets
- Mobiele telefoons
- Verschillende browsers

## 🚀 Gebruik

### Nieuwe Installatie
1. Upload bestanden naar server
2. Bezoek de website
3. Setup wizard verschijnt automatisch
4. Volg de stappen
5. Klaar!

### Database Upgrade
1. Log in als admin
2. Ga naar "Database Upgrade" in admin menu
3. Bekijk database status
4. Klik "Database Upgrade Uitvoeren"
5. Upgrade voltooid!

## 🔧 Configuratie

### .env Bestand
De wizard configureert automatisch:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=collection_manager
DB_USERNAME=root
DB_PASSWORD=password
APP_KEY=base64:...
```

### Database Tabellen
Automatisch aangemaakt:
- users
- cache
- jobs
- collection_items
- permissions (Spatie package)
- shared_links

## 🐛 Troubleshooting

### Setup Wizard verschijnt niet
- Controleer of database bestaat
- Controleer database verbinding
- Controleer .env configuratie

### Migraties falen
- Controleer database rechten
- Controleer PHP extensies
- Controleer Laravel logs

### Admin gebruiker aanmaken faalt
- Controleer wachtwoord vereisten
- Controleer email formaat
- Controleer unieke gebruikersnaam

## 📈 Voordelen

### Voor Gebruikers
- Geen command line kennis nodig
- Eenvoudige setup procedure
- Duidelijke feedback
- Moderne interface

### Voor Ontwikkelaars
- Minder support vragen
- Geautomatiseerde installatie
- Betere gebruikerservaring
- Professionele uitstraling

## 🔮 Toekomstige Verbeteringen

- [ ] Multi-language support
- [ ] Advanced database configuratie
- [ ] Email configuratie wizard
- [ ] Backup/restore functionaliteit
- [ ] Plugin systeem setup
- [ ] Performance optimalisatie wizard 