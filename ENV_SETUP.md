# .env Configuratie Setup

## Overzicht
Dit bestand bevat instructies voor het instellen van de `.env` configuratie voor het collectiebeheer systeem.

## Stap 1: .env Bestand Aanmaken
Kopieer `env.template` naar `.env`:
```bash
cp env.template .env
```

## Stap 2: Database Configuratie
Pas de database instellingen aan in `.env`:
```env
DB_HOST=localhost
DB_NAME=collection_manager
DB_USER=root
DB_PASS=jouw_wachtwoord
```

## Stap 3: Application Configuratie
```env
APP_ENV=production
```

## Stap 4: SMTP Configuratie (Optioneel)
Alleen nodig als je e-mail functionaliteit wilt gebruiken:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=jouw_email@gmail.com
SMTP_PASS=jouw_app_wachtwoord
SMTP_FROM=jouw_email@gmail.com
```

## Stap 5: TOTP Configuratie
Voor twee-factor authenticatie:
```env
TOTP_ISSUER=Collectiebeheer
```

## Minimale .env Voorbeeld
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=collection_manager
DB_USER=root
DB_PASS=jouw_wachtwoord

# Application Configuration
APP_ENV=production

# TOTP Configuration
TOTP_ISSUER=Collectiebeheer
```

## Belangrijke Opmerkingen
- **DB_PASS**: Vul je database wachtwoord in
- **SMTP**: Alleen nodig voor e-mail functionaliteit (delen van collecties)
- **TOTP_ISSUER**: Naam die wordt getoond in authenticator apps
- **APP_ENV**: Gebruik 'development' voor lokale ontwikkeling

## Veiligheid
- Bewaar `.env` veilig en deel het niet
- Voeg `.env` toe aan `.gitignore`
- Gebruik sterke wachtwoorden voor database en SMTP 