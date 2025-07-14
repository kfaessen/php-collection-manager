# OAuth Social Login Functionaliteit

## Overzicht
De OAuth Social Login functionaliteit voegt ondersteuning toe voor inloggen via Google en Facebook. Gebruikers kunnen hun bestaande sociale accounts gebruiken om snel in te loggen zonder een nieuw wachtwoord te hoeven onthouden.

## Functionaliteiten

### 1. Ondersteunde Providers
- **Google OAuth 2.0**: Inloggen via Google accounts
- **Facebook Login**: Inloggen via Facebook accounts
- **Uitbreidbaar**: Architectuur ondersteunt eenvoudig toevoegen van andere providers

### 2. Account Linking
- **Automatische koppeling**: Als een gebruiker al bestaat met hetzelfde email adres, wordt het sociale account automatisch gekoppeld
- **Handmatige koppeling**: Ingelogde gebruikers kunnen sociale accounts koppelen via hun profiel
- **Account ontkoppeling**: Gebruikers kunnen sociale accounts weer loskoppelen

### 3. Gebruikersbeheer
- **Automatische registratie**: Nieuwe gebruikers worden automatisch aangemaakt bij eerste social login
- **Profiel synchronisatie**: Naam en avatar worden gesynchroniseerd vanaf het sociale account
- **Email verificatie**: Social logins worden als geverifieerd beschouwd

## Technische Implementatie

### Database Schema
Twee nieuwe tabellen zijn toegevoegd:

#### `social_logins` tabel
- `user_id`: Koppeling naar users tabel
- `provider`: OAuth provider (google, facebook)
- `provider_id`: Unieke ID van de gebruiker bij de provider
- `provider_email`: Email adres van het sociale account
- `provider_name`: Volledige naam van het sociale account
- `provider_avatar`: URL naar profielfoto
- `access_token`: OAuth access token (voor API calls)
- `expires_at`: Vervaldatum van de access token

#### `oauth_states` tabel
- `id`: Unieke state parameter voor OAuth security
- `provider`: OAuth provider
- `expires_at`: Vervaldatum van de state parameter

#### Uitbreidingen `users` tabel
- `avatar_url`: URL naar profielfoto
- `email_verified`: Of het email adres geverifieerd is
- `registration_method`: Hoe de gebruiker is geregistreerd (local, google, facebook)

### Backend Classes

#### OAuthHelper
- `isEnabled()`: Controleert of OAuth is ingeschakeld en geconfigureerd
- `getAuthorizationUrl()`: Genereert OAuth authorization URL
- `handleCallback()`: Verwerkt OAuth callback van provider
- `linkSocialAccount()`: Koppelt sociaal account aan gebruiker
- `findOrCreateUserFromSocial()`: Zoekt bestaande gebruiker of maakt nieuwe aan
- `getUserSocialAccounts()`: Haalt gekoppelde accounts van gebruiker op
- `unlinkSocialAccount()`: Ontkoppelt sociaal account

#### Provider-specifieke methoden
- Google OAuth 2.0 implementatie
- Facebook Graph API implementatie
- HTTP request handling met cURL
- Token uitwisseling en gebruikersinfo ophaling

### Frontend Integratie

#### Login Pagina (`login.php`)
- OAuth knoppen worden alleen getoond als providers zijn geconfigureerd
- Knoppen leiden naar `oauth.php?action=login&provider=X`
- Visueel onderscheid tussen Google (rood) en Facebook (blauw) knoppen

#### Profiel Pagina (`profile.php`)
- Sectie "Gekoppelde accounts" toont alle sociale accounts
- Knoppen om nieuwe accounts te koppelen
- Ontkoppel functionaliteit met bevestiging

#### OAuth Endpoint (`oauth.php`)
- `?action=login`: Start OAuth flow
- `?action=callback`: Verwerkt OAuth callback
- Automatische redirect URI generatie
- Error handling en gebruikersfeedback

## Configuratie

### Environment Variables
```env
# OAuth Configuration
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/oauth.php?action=callback&provider=google
FACEBOOK_APP_ID=your-facebook-app-id
FACEBOOK_APP_SECRET=your-facebook-app-secret
FACEBOOK_REDIRECT_URI=https://yourdomain.com/oauth.php?action=callback&provider=facebook
OAUTH_ENABLED=true
```

### Google OAuth Setup
1. Ga naar [Google Cloud Console](https://console.cloud.google.com/)
2. Maak een nieuw project aan of selecteer bestaand project
3. Activeer Google+ API
4. Ga naar "Credentials" en maak OAuth 2.0 Client ID aan
5. Voeg je domain toe aan "Authorized domains"
6. Voeg redirect URI toe: `https://yourdomain.com/oauth.php?action=callback&provider=google`
7. Kopieer Client ID en Client Secret naar `.env`

### Facebook OAuth Setup
1. Ga naar [Facebook Developers](https://developers.facebook.com/)
2. Maak een nieuwe app aan
3. Voeg "Facebook Login" product toe
4. Configureer "Valid OAuth Redirect URIs": `https://yourdomain.com/oauth.php?action=callback&provider=facebook`
5. Kopieer App ID en App Secret naar `.env`
6. Zet app live in App Review

## Veiligheid

### OAuth State Parameter
- Unieke state parameter voor elke OAuth request
- Voorkomt CSRF aanvallen
- Automatische cleanup van verlopen states

### Token Management
- Access tokens worden veilig opgeslagen
- Vervaldatums worden bijgehouden
- Refresh tokens voor lange termijn toegang

### Account Security
- Controle op bestaande accounts voorkomt duplicaten
- Email verificatie via sociale providers
- Wachtwoord vereist blijft voor kritieke acties

## Gebruikerservaring

### Eerste Social Login
1. Gebruiker klikt op Google/Facebook knop
2. Redirect naar OAuth provider
3. Gebruiker geeft toestemming
4. Automatische account aanmaak als nieuw
5. Direct ingelogd en doorgestuurd

### Account Koppelen
1. Ingelogde gebruiker gaat naar profiel
2. Klikt op "Google/Facebook koppelen"
3. OAuth flow voor koppeling
4. Account wordt toegevoegd aan profiel

### Account Ontkoppelen
1. Gebruiker gaat naar profiel
2. Klikt op "Ontkoppelen" bij gewenst account
3. Bevestiging popup
4. Account wordt ontkoppeld

## Error Handling

### Common Errors
- **Provider niet geconfigureerd**: Duidelijke foutmelding
- **OAuth denied**: Gebruiker geannuleerd, terug naar login
- **Invalid state**: Mogelijk CSRF poging, blocked
- **Account al gekoppeld**: Melding dat account al in gebruik is

### Error Logging
- Alle OAuth errors worden gelogd
- Gebruikers zien gebruiksvriendelijke berichten
- Technische details alleen in logs

## Deployment

### Required Dependencies
```json
{
    "suggest": {
        "league/oauth2-google": "^4.0 - Voor Google OAuth login (optioneel)",
        "league/oauth2-facebook": "^3.0 - Voor Facebook OAuth login (optioneel)"
    }
}
```

### Database Migration
- Migratie versie 5 wordt automatisch uitgevoerd
- Tabellen worden alleen aangemaakt als ze niet bestaan
- Bestaande data blijft behouden

### SSL Vereisten
- HTTPS is verplicht voor OAuth (productie)
- OAuth providers weigeren HTTP redirect URIs
- Let's Encrypt certificaten worden aanbevolen

## Toekomstige Uitbreidingen

### Andere Providers
- **Twitter/X OAuth**: Tweet integratie
- **GitHub OAuth**: Developer-gerichte features  
- **Microsoft OAuth**: Office 365 integratie
- **LinkedIn OAuth**: Professional networking

### Advanced Features
- **Token refresh**: Automatische token vernieuwing
- **Scope management**: Granulaire permissies
- **Social sharing**: Direct delen naar sociale media
- **Import contacts**: Vrienden uitnodigen via sociale netwerken

## Bestanden

### Nieuwe Bestanden
- `includes/OAuthHelper.php`: OAuth functionaliteit
- `public/oauth.php`: OAuth endpoint voor login en callback
- `OAUTH_FEATURE.md`: Deze documentatie

### Aangepaste Bestanden
- `includes/Database.php`: Migratie versie 5 voor OAuth tabellen
- `includes/Environment.php`: OAuth configuratie variabelen  
- `includes/functions.php`: OAuthHelper class loader
- `env.template`: OAuth configuratie template
- `composer.json`: OAuth library suggestions
- `public/login.php`: OAuth login knoppen
- `public/profile.php`: Social account management

## Troubleshooting

### OAuth Errors
- **"redirect_uri_mismatch"**: Controleer redirect URI configuratie
- **"invalid_client"**: Controleer Client ID/Secret
- **"access_denied"**: Gebruiker heeft toestemming geweigerd

### Configuration Issues
- **Knoppen niet zichtbaar**: Controleer OAUTH_ENABLED en provider configuratie
- **"Provider niet geconfigureerd"**: Vul Client ID en Secret in
- **HTTPS errors**: Gebruik SSL certificaat voor productie

### Testing
1. Test OAuth configuratie met development URLs
2. Gebruik verschillende browsers voor cross-testing
3. Test account koppeling en ontkoppeling
4. Controleer error handling scenarios 