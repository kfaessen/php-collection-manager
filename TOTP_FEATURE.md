# TOTP Two-Factor Authentication Functionaliteit

## Overzicht
De TOTP (Time-based One-Time Password) two-factor authenticatie voegt een extra beveiligingslaag toe aan het collectiebeheer systeem. Gebruikers kunnen optioneel twee-factor authenticatie inschakelen voor hun account.

## Functionaliteiten

### 1. TOTP Setup en Beheer
- **Inschakelen**: Gebruikers kunnen TOTP inschakelen via een dedicated setup pagina
- **Uitschakelen**: Gebruikers kunnen TOTP uitschakelen wanneer gewenst
- **QR Code**: Automatische QR code generatie voor eenvoudige setup
- **Handmatige invoer**: Secret key wordt getoond voor handmatige invoer

### 2. Backup Codes
- **Automatische generatie**: 10 backup codes worden automatisch gegenereerd
- **Eenmalig gebruik**: Elke backup code kan maar één keer gebruikt worden
- **Regeneratie**: Gebruikers kunnen nieuwe backup codes genereren
- **Account recovery**: Backup codes kunnen gebruikt worden als telefoon niet beschikbaar is

### 3. Login Proces
- **Twee-staps verificatie**: Eerst wachtwoord, dan TOTP code
- **Backup code ondersteuning**: Backup codes kunnen gebruikt worden in plaats van TOTP
- **Seamless UX**: Vloeiende gebruikerservaring met duidelijke feedback

## Technische Implementatie

### Database Schema
De `users` tabel is uitgebreid met:
- `totp_secret` (VARCHAR(32)): De TOTP secret key
- `totp_enabled` (BOOLEAN): Of TOTP is ingeschakeld
- `totp_backup_codes` (TEXT): JSON array van backup codes

### Backend Classes

#### TOTPHelper
- `generateSecret()`: Genereert een nieuwe TOTP secret
- `generateQRUrl()`: Genereert QR code URL voor authenticator apps
- `generateBackupCodes()`: Genereert backup codes
- `verifyCode()`: Verifieert TOTP codes
- `verifyBackupCode()`: Verifieert en verbruikt backup codes

#### Authentication (Uitgebreid)
- `enableTOTP()`: Schakelt TOTP in voor een gebruiker
- `verifyAndEnableTOTP()`: Verifieert en schakelt TOTP in
- `disableTOTP()`: Schakelt TOTP uit
- `generateNewBackupCodes()`: Genereert nieuwe backup codes
- `login()`: Uitgebreid met TOTP verificatie

### Frontend Pagina's

#### totp-setup.php
- Complete TOTP setup interface
- QR code generatie en weergave
- Backup codes beheer
- Inschakelen/uitschakelen functionaliteit

#### login.php (Uitgebreid)
- Twee-staps login proces
- TOTP code invoer veld
- Backup code ondersteuning
- Verbeterde gebruikerservaring

## Configuratie

### Environment Variables
```env
# TOTP Configuration
TOTP_ISSUER=Collectiebeheer
TOTP_WINDOW=1
TOTP_BACKUP_CODES_COUNT=10
```

### Instellingen
- **TOTP_ISSUER**: Naam die wordt getoond in authenticator apps
- **TOTP_WINDOW**: Tijdsvenster voor TOTP verificatie (standaard 1 = 30 seconden)
- **TOTP_BACKUP_CODES_COUNT**: Aantal backup codes om te genereren

## Gebruikerservaring

### Setup Proces
1. Gebruiker gaat naar "Twee-factor authenticatie" in het menu
2. Klikt op "TOTP inschakelen"
3. Scant QR code met authenticator app
4. Voert eerste TOTP code in om te verifiëren
5. Bewaart backup codes op veilige plek

### Login Proces
1. Gebruiker voert gebruikersnaam en wachtwoord in
2. Als TOTP is ingeschakeld, wordt TOTP veld getoond
3. Gebruiker voert 6-cijferige code in van authenticator app
4. Of gebruikt een backup code als alternatief

### Backup Code Gebruik
- Backup codes zijn 8-cijferig
- Elke code kan maar één keer gebruikt worden
- Na gebruik wordt de code automatisch verwijderd
- Gebruikers kunnen nieuwe codes genereren

## Veiligheid

### TOTP Implementatie
- Gebruikt RFC 6238 standaard
- SHA1 HMAC algoritme
- 30-seconden tijdvenster
- 6-cijferige codes
- Timing-safe vergelijking

### Backup Codes
- Willekeurig gegenereerd
- Eenmalig gebruik
- Automatische invalidatie na gebruik
- Veilige opslag in database

### Account Recovery
- Backup codes bieden recovery optie
- Geen permanente account lockout
- Gebruikers kunnen nieuwe codes genereren
- Admin kan TOTP uitschakelen indien nodig

## Ondersteunde Apps

### Authenticator Apps
- Google Authenticator
- Authy
- Microsoft Authenticator
- 1Password
- LastPass Authenticator
- En alle andere TOTP-compatibele apps

### QR Code Standaard
- Gebruikt standaard `otpauth://` URL formaat
- Compatibel met alle major authenticator apps
- Ondersteunt handmatige invoer als alternatief

## Bestanden

### Nieuwe Bestanden
- `includes/TOTPHelper.php`: TOTP functionaliteit
- `public/totp-setup.php`: TOTP setup interface
- `env.template`: Configuratie template

### Aangepaste Bestanden
- `includes/Authentication.php`: TOTP login ondersteuning
- `includes/Database.php`: Database schema uitbreiding
- `includes/Environment.php`: TOTP configuratie
- `includes/functions.php`: TOTPHelper class loader
- `public/login.php`: TOTP login interface
- `public/index.php`: Menu link naar TOTP setup

## Toekomstige Uitbreidingen
- **SMS backup**: SMS codes als alternatief voor backup codes
- **Hardware tokens**: Ondersteuning voor hardware security keys
- **Admin override**: Admin kan TOTP uitschakelen voor gebruikers
- **Audit logging**: Logboek van TOTP activiteiten
- **Bulk operations**: Bulk TOTP setup voor meerdere gebruikers 