# Email Verificatie Functionaliteit

## Overzicht
De Email Verificatie functionaliteit voegt een beveiligingslaag toe aan het registratieproces door te vereisen dat gebruikers hun email adres bevestigen voordat ze volledig toegang krijgen tot de applicatie. Dit voorkomt registraties met valse email adressen en zorgt ervoor dat gebruikers bereikbaar zijn voor belangrijke communicatie.

## 🎯 Doelstellingen

### Beveiliging
- **Valide email adressen**: Alleen geverifieerde email adressen in de database
- **Spam preventie**: Voorkomt registraties met ongeldig/tijdelijk email adressen
- **Account eigendom**: Bevestigt dat gebruiker toegang heeft tot het opgegeven email adres
- **Fraudepreventie**: Vermindert nepaccounts en misbruik

### Gebruikerservaring
- **Duidelijke communicatie**: Helder proces en feedback tijdens verificatie
- **Eenvoudige verificatie**: Een-klik verificatie via email link
- **Herinnering systeem**: Automatische herinneringen voor onverifieerde accounts
- **Admin ondersteuning**: Handmatige verificatie opties voor beheerders

## 🏗️ Architectuur

### Componenten Overzicht
```
Email Registration → Token Generation → Email Sending → User Verification → Account Activation
       ↓                    ↓               ↓              ↓                ↓
   User Input         Database Storage   SMTP Server   Click Link      Login Access
```

### Server-side Componenten
- **EmailVerificationHelper.php**: Core email verificatie management
- **verify-email.php**: Verificatie endpoint voor token processing
- **Database Schema**: Token opslag en user verificatie status
- **MailHelper Integration**: Email verzending via SMTP
- **Admin Interface**: Beheer en monitoring tools

### Database Schema
```sql
-- Email verification tokens table
CREATE TABLE email_verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Extended users table
ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN verification_reminder_sent BOOLEAN DEFAULT FALSE;
```

## 💻 Technische Implementatie

### EmailVerificationHelper Class
```php
class EmailVerificationHelper {
    // Core Methods
    public static function sendVerificationEmail($userId, $email, $resend = false)
    public static function generateVerificationToken($userId, $email)
    public static function verifyEmail($token)
    public static function isEmailVerified($userId)
    
    // Admin Functions
    public static function manuallyVerifyUser($userId)
    public static function sendVerificationReminders()
    public static function getVerificationStats()
    
    // Maintenance
    public static function cleanupExpiredTokens()
    public static function getUsersNeedingReminder($hours = 72)
}
```

### Token Management
- **Secure Generation**: 128-character random tokens (64 bytes hex)
- **Expiration**: 24-hour validity period
- **Single Use**: Tokens are marked as used after verification
- **Cleanup**: Automatic cleanup of expired tokens

### Email Template
- **HTML Email**: Responsive, professional design
- **Branding**: Consistent with Collectiebeheer styling
- **Multiple Languages**: Support for different languages
- **Clear CTAs**: Prominent verification button
- **Security Notes**: Expiration info and tips

## 🔄 Gebruikersflow

### Registratie Flow
1. **Nieuwe Registratie**:
   - Gebruiker vult registratie formulier in
   - Account wordt aangemaakt maar niet geactiveerd
   - Verificatie token wordt gegenereerd
   - Email wordt verzonden naar gebruiker

2. **Email Verificatie**:
   - Gebruiker ontvangt welkomst email
   - Klikt op verificatie link
   - Token wordt gevalideerd
   - Account wordt geactiveerd
   - Automatische inlog

3. **Login Check**:
   - Onverifieerde gebruikers kunnen niet inloggen
   - Duidelijke melding met resend optie
   - Verificatie status wordt gecontroleerd

### Admin Flow
1. **Monitoring**: Dashboard met verificatie statistieken
2. **Handmatige Verificatie**: Direct activeren van accounts
3. **Bulk Acties**: Herinneringen versturen, tokens opschonen
4. **User Management**: Overzicht van onverifieerde accounts

## ⚙️ Configuratie

### Environment Variables
```bash
# Email Verification Settings
EMAIL_VERIFICATION_ENABLED=true
EMAIL_VERIFICATION_TOKEN_LIFETIME=86400    # 24 hours
EMAIL_VERIFICATION_REMINDER_HOURS=72       # 3 days

# SMTP Configuration (Required for email sending)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=your_email@gmail.com
SMTP_FROM_NAME="Collectiebeheer"
```

### Feature Configuration
- **Skip Verification**: Admin setup en OAuth registraties
- **Automatic Reminders**: Configureerbare timing
- **Token Lifetime**: Aanpasbare vervaltijd
- **Email Templates**: Meertalige ondersteuning

## 🔒 Beveiliging

### Token Security
- **Cryptographic Randomness**: Gebruik van `random_bytes(64)`
- **Unique Constraints**: Database level uniciteit
- **Time Bounds**: Beperkte geldigheid (24 uur)
- **Single Use**: Tokens worden gedeactiveerd na gebruik

### Email Security
- **SMTP Authentication**: Veilige email verzending
- **Rate Limiting**: Voorkoming van spam
- **Input Validation**: Sanitization van alle input
- **SQL Injection Protection**: Prepared statements

### Privacy Protection
```php
// Sensitive data handling
- Tokens are hashed in logs
- Email addresses are validated
- User data is sanitized
- GDPR compliance considerations
```

## 📊 Monitoring & Analytics

### Verificatie Statistieken
```php
// Available statistics
- Total local users
- Verified users count
- Pending verification count
- Verification rate percentage
- Active tokens count
```

### Admin Dashboard
- **Real-time Status**: Live verificatie statistieken
- **User Lists**: Overzicht van onverifieerde accounts
- **Action Buttons**: Handmatige verificatie en resend opties
- **Email Status**: SMTP connectiviteit monitoring

## 🔧 Beheer Functies

### Automatische Processen
- **Token Cleanup**: Verwijdering van verlopen tokens
- **Reminder System**: Herinneringen na 72 uur
- **Statistics Update**: Real-time statistieken

### Handmatige Acties
- **Direct Verification**: Admin kan accounts direct activeren
- **Resend Emails**: Nieuwe verificatie emails versturen
- **Bulk Operations**: Acties op meerdere accounts tegelijk

## 📧 Email Integration

### SMTP Requirements
- **PHPMailer**: Voor email verzending (optioneel)
- **SMTP Server**: Configuratie vereist
- **Authentication**: Username/password of OAuth
- **Encryption**: TLS/SSL ondersteuning

### Email Content
- **Welcome Message**: Vriendelijke welkomstboodschap
- **Clear Instructions**: Stap-voor-stap verificatie instructies
- **Branding**: Collectiebeheer logo en styling
- **Contact Info**: Support informatie
- **Legal Notices**: Privacy en terms links

## 🚀 Deployment

### Database Migration
```sql
-- Migration version 9 wordt automatisch uitgevoerd
-- Voegt email_verification_tokens tabel toe
-- Breidt users tabel uit met verificatie velden
-- Update bestaande verified users
```

### Configuration Steps
1. **Environment Setup**: `.env` configuratie
2. **SMTP Configuration**: Email server instellingen
3. **Database Migration**: Automatische schema updates
4. **Feature Testing**: Verificatie van email sending

## 🔮 Toekomstige Uitbreidingen

### Geplande Features
- **Email Change Verification**: Verificatie bij email adres wijziging
- **Multiple Email Addresses**: Ondersteuning voor meerdere emails
- **Advanced Templates**: Meer email template opties
- **API Integration**: REST API voor verificatie status

### Integratie Mogelijkheden
- **SMS Verification**: Telefoon nummer verificatie
- **Social Verification**: Verificatie via sociale netwerken
- **Two-Factor Integration**: Koppeling met TOTP systeem
- **Audit Logging**: Uitgebreide logging van verificatie events

## 📋 Gebruiksvoorbeelden

### Basis Implementatie
```php
// Nieuwe gebruiker registreren
$result = Authentication::register([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'password' => 'secure_password',
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

// Verificatie email wordt automatisch verzonden
```

### Admin Functies
```php
// Handmatig verifiëren
EmailVerificationHelper::manuallyVerifyUser($userId);

// Herinneringen versturen
$result = EmailVerificationHelper::sendVerificationReminders();

// Statistieken ophalen
$stats = EmailVerificationHelper::getVerificationStats();
```

### Login Controle
```php
// Verificatie wordt automatisch gecontroleerd bij login
$result = Authentication::login($username, $password);

if (!$result['success'] && $result['requires_verification']) {
    // Toon verificatie melding met resend optie
}
```

## 🎯 Best Practices

### Gebruikerservaring
- **Duidelijke Communicatie**: Leg uit waarom verificatie nodig is
- **Snelle Verificatie**: Maak het proces zo eenvoudig mogelijk
- **Help & Support**: Zorg voor duidelijke instructies en hulp
- **Mobile Friendly**: Emails moeten goed werken op mobiel

### Beveiliging
- **Token Expiration**: Gebruik korte vervaltijden
- **Rate Limiting**: Voorkom misbruik van resend functionaliteit
- **Input Validation**: Valideer alle gebruikersinput
- **Secure Storage**: Gebruik encrypted database connecties

### Performance
- **Efficient Queries**: Gebruik indexes op token en user_id
- **Cleanup Jobs**: Regelmatige opschoning van verlopen tokens
- **Email Queue**: Overweeg queue voor grote volumes
- **Caching**: Cache verificatie status waar mogelijk

## 📞 Support & Troubleshooting

### Veelvoorkomende Problemen
- **Email niet ontvangen**: Check spam folder, SMTP configuratie
- **Token verlopen**: Nieuwe email aanvragen via login pagina
- **SMTP fouten**: Controleer server instellingen en authenticatie
- **Database errors**: Controleer migratie status

### Debug Tips
- **Error Logs**: Check PHP error logs voor SMTP fouten
- **Database Queries**: Controleer token status in database
- **Email Headers**: Controleer email routing en delivery
- **Network Issues**: Test SMTP connectiviteit

## 📈 Metrieken & KPIs

### Belangrijke Metrieken
- **Verificatie Ratio**: Percentage geverifieerde accounts
- **Time to Verification**: Gemiddelde tijd tot verificatie
- **Bounce Rate**: Percentage gefaalde email deliveries
- **Support Tickets**: Verificatie gerelateerde problemen

### Succes Indicatoren
- **>90% Verificatie Ratio**: Hoge adoptie van verificatie
- **<10% Bounce Rate**: Goede email deliverability
- **<24h Verification Time**: Snelle gebruiker activatie
- **Minimal Support Load**: Weinig verificatie problemen

## 🔄 Onderhoud

### Dagelijkse Taken
- Monitor email delivery status
- Check error logs voor problemen
- Bekijk verificatie statistieken

### Wekelijkse Taken
- Cleanup verlopen tokens
- Analyseer verificatie trends
- Test email functionality

### Maandelijkse Taken
- Review SMTP performance
- Update email templates
- Analyze user feedback 