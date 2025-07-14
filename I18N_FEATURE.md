# Meertalige Ondersteuning (i18n) Functionaliteit

## Overzicht
De Internationalisatie (i18n) functionaliteit voegt volledige meertalige ondersteuning toe aan de applicatie. Dit omvat automatische taaldetectie, een volledig vertaalsysteem, RTL-ondersteuning en gebruiksvriendelijke taalwisselaars.

## Functionaliteiten

### 1. Taaldetectie en -beheer
- **Automatische taaldetectie** via browser Accept-Language header
- **Gebruikers-specifieke taalvoorkeuren** opgeslagen in database
- **Sessie-gebaseerde taalwisseling** voor gasten
- **URL parameter ondersteuning** voor directe taalwisseling

### 2. Vertaalsysteem
- **Database-gedreven vertalingen** met fallback naar hardcoded defaults
- **Categoriegebaseerde organisatie** van vertalingen
- **Template functie ondersteuning** met placeholders
- **Pluralisatie ondersteuning** voor taal-specifieke meervouden

### 3. RTL Ondersteuning
- **Automatische tekst richting** (Left-to-Right / Right-to-Left)
- **CSS aanpassingen** voor RTL talen zoals Arabisch en Hebreeuws
- **Bootstrap integratie** met RTL-aware styling

### 4. Gebruikersinterface
- **Language Switcher component** in verschillende stijlen
- **Navbar integratie** met dropdown/button/select opties
- **Responsive design** voor alle apparaten

## Technische Implementatie

### Database Schema
Drie nieuwe tabellen zijn toegevoegd:

#### `languages` tabel
- `code`: Taalcode (nl, en, de, fr, etc.)
- `name`: Engelse naam van de taal
- `native_name`: Naam in eigen taal
- `is_rtl`: Of de taal rechts-naar-links wordt geschreven
- `is_active`: Of de taal beschikbaar is voor gebruikers
- `sort_order`: Volgorde in taalwisselaars

#### `translation_keys` tabel
- `key_name`: Unieke sleutel voor vertaling
- `description`: Beschrijving van de vertaling
- `category`: Groepering van vertalingen (core, navigation, etc.)

#### `translations` tabel
- `key_id`: Verwijzing naar translation_keys
- `language_code`: Taalcode voor deze vertaling
- `translation`: De eigenlijke vertaling
- `is_completed`: Of de vertaling voltooid is

#### Uitbreidingen `users` tabel
- `preferred_language`: Voorkeurstaal van de gebruiker

### Backend Classes

#### I18nHelper
- `init()`: Initialiseert het i18n systeem
- `getCurrentLanguage()`: Huidige taal van de gebruiker
- `setLanguage()`: Wijzigt de actieve taal
- `translate()` / `t()`: Vertaal functie met placeholder ondersteuning
- `pluralize()`: Meervoud ondersteuning
- `formatDate()`: Taal-specifieke datum formatting
- `formatNumber()`: Taal-specifieke nummer formatting
- `isRTL()`: Controleert of taal RTL is
- `getAvailableLanguages()`: Haalt beschikbare talen op

#### LanguageSwitcher Component
- `render()`: Verschillende stijlen van taalwisselaars
- `renderDropdown()`: Bootstrap dropdown stijl
- `renderButtons()`: Button group stijl  
- `renderSelect()`: Select dropdown stijl
- `renderInline()`: Eenvoudige link lijst
- `renderWithJS()`: JavaScript-enhanced versie

### Frontend Integratie

#### HTML Aanpassingen
```html
<html lang="<?= I18nHelper::getCurrentLanguage() ?>" dir="<?= I18nHelper::getDirection() ?>">
```

#### CSS RTL Ondersteuning
```css
[dir="rtl"] {
    text-align: right;
    direction: rtl;
}
```

#### Taalwisselaar Integratie
```php
<?= CollectionManager\LanguageSwitcher::render('dropdown', true, false) ?>
```

### Endpoint voor Taalwisseling

#### `language.php`
- `?action=switch&lang=X`: Wijzigt taal
- `?action=get_languages`: AJAX endpoint voor beschikbare talen
- `?action=update_preference`: Bijwerken gebruikersvoorkeur

## Configuratie

### Environment Variables
```env
# Internationalization Configuration
DEFAULT_LANGUAGE=nl
FALLBACK_LANGUAGE=en
I18N_ENABLED=true
AUTO_DETECT_LANGUAGE=true
TRANSLATION_CACHE_ENABLED=true
TRANSLATION_CACHE_LIFETIME=3600
SUPPORTED_LANGUAGES=["nl","en"]
```

### Ondersteunde Talen
Standaard geïnstalleerde talen:
- **Nederlands (nl)**: Actief
- **Engels (en)**: Actief  
- **Duits (de)**: Inactief
- **Frans (fr)**: Inactief
- **Spaans (es)**: Inactief
- **Arabisch (ar)**: Inactief (RTL)
- **Hebreeuws (he)**: Inactief (RTL)

### Nieuwe Taal Toevoegen
1. Voeg taal toe aan `languages` tabel
2. Voeg taalcode toe aan `SUPPORTED_LANGUAGES` configuratie
3. Voeg vertalingen toe via admin interface of direct in database
4. Test RTL ondersteuning indien van toepassing

## Gebruik

### Template Vertalingen
```php
// Eenvoudige vertaling
echo I18nHelper::t('login');

// Met placeholders
echo I18nHelper::t('welcome_user', ['name' => $userName]);

// Met categorie
echo I18nHelper::t('save_button', [], 'forms');

// Pluralisatie
echo I18nHelper::pluralize('item_count', $count, ['count' => $count]);
```

### Taalwisselaar Stijlen
```php
// Dropdown (standaard)
LanguageSwitcher::render('dropdown', true, true);

// Button group
LanguageSwitcher::render('buttons', true, false);

// Select dropdown
LanguageSwitcher::render('select', false, true);

// Inline links
LanguageSwitcher::renderInline(' | ');

// JavaScript enhanced
LanguageSwitcher::renderWithJS('my-switcher');
```

### Datum & Nummer Formatting
```php
// Datum formatting per taal
echo I18nHelper::formatDate($date); // nl: 31-12-2023, en: 12/31/2023

// Nummer formatting
echo I18nHelper::formatNumber(1234.56, 2); // nl: 1.234,56, en: 1,234.56
```

## RTL Ondersteuning

### Automatische Detectie
```php
if (I18nHelper::isRTL()) {
    // Speciale RTL logica
}

$direction = I18nHelper::getDirection(); // 'ltr' of 'rtl'
```

### CSS Classes
Alle Bootstrap en custom classes worden automatisch aangepast:
- `.me-2` wordt `.ms-2` in RTL
- `.float-start` wordt `.float-end` in RTL
- Dropdown menus openen aan de rechterkant
- Text alignment wordt omgekeerd

## Vertaling Management

### Vertalingen Toevoegen
```php
// Programmatisch
I18nHelper::addTranslation(
    'welcome_message',           // Key
    'Welkom bij onze applicatie', // Translation
    'nl',                        // Language
    'general',                   // Category
    'Welcome message for users'  // Description
);
```

### Database Queries
```sql
-- Nieuwe vertaling toevoegen
INSERT INTO translation_keys (key_name, category, description) 
VALUES ('new_feature', 'features', 'New feature description');

INSERT INTO translations (key_id, language_code, translation) 
VALUES (LAST_INSERT_ID(), 'nl', 'Nieuwe functie');

INSERT INTO translations (key_id, language_code, translation) 
VALUES (LAST_INSERT_ID(), 'en', 'New feature');
```

## Performance

### Caching
- Vertalingen worden gecached per categorie
- Talen lijst wordt gecached voor snelle toegang
- Cache levensduur configureerbaar via environment

### Lazy Loading
- Vertalingen worden alleen geladen wanneer nodig
- Categorieën worden individueel geladen
- Fallback taal wordt automatisch meegeladen

### Optimalisatie Tips
1. Groepeer gerelateerde vertalingen in categorieën
2. Gebruik korte, duidelijke keys
3. Vermijd teveel verschillende categorieën
4. Cacheer taalwisselaar output waar mogelijk

## Deployment

### Database Migration
- Migratie versie 6 wordt automatisch uitgevoerd
- Standaard talen worden geïnstalleerd
- Bestaande users krijgen Nederlandse taalvoorkeur

### Required Files
```
includes/I18nHelper.php
includes/components/LanguageSwitcher.php
public/language.php
assets/css/style.css (RTL additions)
```

### Configuration Steps
1. Configureer gewenste talen in environment
2. Activeer gewenste talen in database
3. Voeg vertalingen toe voor alle keys
4. Test RTL functionaliteit
5. Configureer fallback taal

## Gebruikerservaring

### Taaldetectie Flow
1. URL parameter (`?lang=en`)
2. Sessie voorkeur
3. Gebruiker database voorkeur (indien ingelogd)
4. Browser Accept-Language header
5. Standaard taal (Nederlands)

### Taalwisseling Process
1. Gebruiker selecteert taal via switcher
2. Taal wordt gevalideerd
3. Sessie wordt bijgewerkt
4. Gebruikersvoorkeur wordt opgeslagen (indien ingelogd)
5. Pagina herlaadt met nieuwe taal

### RTL Experience
- Automatische tekst richting
- Spiegelen van UI elementen
- Juiste dropdown positionering
- Aangepaste margin/padding

## Browser Ondersteuning

### Moderne Browsers
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### RTL Features
- CSS `direction` property
- Text alignment aanpassingen
- Flexbox en Grid RTL support
- Bootstrap RTL utilities

## Troubleshooting

### Common Issues
- **Vertalingen niet zichtbaar**: Controleer database migratie
- **RTL niet werkend**: Controleer HTML dir attribute
- **Taal niet wijzigend**: Controleer sessie en cookie instellingen
- **Performance problemen**: Activeer caching

### Debug Mode
```php
// Enable debug mode voor I18n
Environment::set('I18N_DEBUG', true);

// Check loaded translations
$translations = I18nHelper::getLoadedTranslations();

// Verify language detection
$detectedLang = I18nHelper::detectBrowserLanguage();
```

### Error Logging
Alle i18n gerelateerde fouten worden gelogd:
- Missing translations
- Invalid language codes
- Database connection issues
- Cache problems

## Toekomstige Uitbreidingen

### Geplande Features
- **Admin interface** voor vertaling management
- **Import/Export** van vertalingen
- **Translation memory** voor hergebruik
- **Automatische vertaling** via Google Translate API
- **Contextual translation** hints voor vertalers

### API Integratie
- **Translation services**: Google, Microsoft, DeepL
- **Crowdsourcing platforms**: Crowdin, Lokalise
- **Version control**: Git integration voor vertalingen

## Bestanden Overzicht

### Nieuwe Bestanden
- `includes/I18nHelper.php`: Hoofdfunctionaliteit
- `includes/components/LanguageSwitcher.php`: UI component
- `public/language.php`: Taalwisseling endpoint
- `I18N_FEATURE.md`: Deze documentatie

### Aangepaste Bestanden
- `includes/Database.php`: Migratie versie 6
- `includes/Environment.php`: i18n configuratie
- `includes/functions.php`: I18nHelper autoloading
- `public/index.php`: Language switcher integratie
- `assets/css/style.css`: RTL ondersteuning

## Testing Checklist

### Functionaliteit
- [ ] Taaldetectie werkt correct
- [ ] Taalwisseling via dropdown
- [ ] Gebruikersvoorkeur opslaan
- [ ] Vertalingen laden correct
- [ ] Fallback naar Engels werkt
- [ ] RTL talen renderen correct

### Performance
- [ ] Caching werkt
- [ ] Lazy loading functioneert
- [ ] Database queries geoptimaliseerd
- [ ] Geen memory leaks

### UI/UX
- [ ] Language switcher responsive
- [ ] RTL styling correct
- [ ] Dropdown positionering
- [ ] Flag display werkt

### Browser Compatibility
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile browsers 