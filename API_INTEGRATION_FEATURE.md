# API Integratie voor Automatische Covers en Metadata

## Overzicht
De API integratie functionaliteit voegt automatische metadata enrichment en cover download toe via populaire externe APIs. Dit vermindert handmatige invoer en verbetert de gebruikerservaring door automatisch mooie covers en gedetailleerde metadata op te halen.

## Ondersteunde APIs

### 1. IGDB (Internet Game Database)
- **Type**: Games en gaming platforms
- **Authenticatie**: OAuth 2.0 (Twitch)
- **Features**: Game metadata, covers, screenshots, ratings, platforms
- **Rate Limit**: 30 requests per minuut
- **Kwaliteit**: Zeer hoog - officiële game database

### 2. OMDb (Open Movie Database)  
- **Type**: Films en TV series
- **Authenticatie**: API Key
- **Features**: Movie/TV metadata, ratings, cast, plot
- **Rate Limit**: 60 requests per minuut (free tier)
- **Kwaliteit**: Hoog - gebaseerd op IMDb data

### 3. TMDb (The Movie Database)
- **Type**: Films en TV series (optioneel)
- **Authenticatie**: API Key
- **Features**: High-res covers, detailed metadata, multi-language
- **Rate Limit**: 40 requests per minuut
- **Kwaliteit**: Zeer hoog - high resolution images

### 4. OpenLibrary
- **Type**: Boeken
- **Authenticatie**: Geen (open API)
- **Features**: Book metadata, covers via ISBN
- **Rate Limit**: 100 requests per minuut
- **Kwaliteit**: Goed - uitgebreide book database

### 5. Spotify Web API (Toekomstig)
- **Type**: Muziekalbums
- **Authenticatie**: OAuth 2.0
- **Features**: Album metadata, covers, artist info
- **Status**: Geprepareerd, niet actief

## Functionaliteiten

### 1. Automatische Metadata Enrichment
- **Real-time lookup** tijdens item toevoeging
- **Barcode scanning** met automatische API calls
- **Intelligent matching** op basis van titel, platform, jaar
- **Multi-provider search** voor beste resultaten
- **Confidence scoring** voor match kwaliteit

### 2. Cover Image Management
- **Automatische download** in verschillende kwaliteiten
- **Multiple image types**: covers, posters, banners, screenshots
- **Size optimization** voor web performance
- **Local storage** met organized directory structure
- **Fallback handling** bij download failures

### 3. Cache Systeem
- **Response caching** voor API efficiency
- **Configurable cache lifetime** (standaard 24 uur)
- **Rate limit management** per provider
- **Intelligent cache invalidation**

### 4. Rate Limiting
- **Per-provider limits** conform API restrictions
- **Request queuing** voor burst protection
- **Automatic backoff** bij limit overschrijding
- **Request statistics** tracking

## Technische Implementatie

### Database Schema

#### `api_providers` tabel
- **Providers configuratie**: naam, base URL, auth requirements
- **Rate limiting**: requests per minuut per provider
- **Status tracking**: actief/inactief per provider

#### `api_cache` tabel
- **Response storage**: gecachte API responses
- **Expiration handling**: automatische cleanup
- **Request tracking**: URL en response data

#### `item_metadata` tabel
- **Enriched data**: metadata per provider per item
- **Structured fields**: titel, beschrijving, genre, rating, etc.
- **Provider attribution**: bron van elke metadata set
- **Update tracking**: laatste synchronisatie

#### `cover_images` tabel
- **Image management**: URLs, local paths, dimensions
- **Quality variants**: thumb, small, medium, large, original
- **Download status**: pending, downloading, completed, failed
- **Primary image**: designation voor main cover

#### `api_rate_limits` tabel
- **Rate tracking**: requests per provider per IP
- **Window management**: sliding window rate limiting
- **Burst protection**: prevent API abuse

### Backend Classes

#### MetadataEnricher
- **`enrichItem()`**: Hoofdfunctie voor item verrijking
- **`searchProvider()`**: Provider-specifieke zoekacties
- **`downloadCovers()`**: Geautomatiseerde cover downloads
- **API-specifieke methoden**: IGDB, OMDb, TMDb integration

#### Enhanced APIManager
- **`getMetadataByBarcode()`**: Uitgebreide barcode lookup
- **`enrichItemMetadata()`**: Bridge naar MetadataEnricher
- **`getAvailableProviders()`**: Provider status checking
- **Confidence scoring**: match quality bepaling

### API Integration Details

#### IGDB Integration
```php
// OAuth 2.0 flow
$accessToken = self::getIGDBAccessToken();

// Search query
$query = "search \"$searchTerm\"; fields name,summary,cover.url; limit 10;";

// Request met authentication headers
$headers = [
    'Client-ID: ' . $clientId,
    'Authorization: Bearer ' . $accessToken
];
```

#### OMDb Integration
```php
// API key authentication
$url = "http://www.omdbapi.com/?apikey=$apiKey&t=" . urlencode($title);

// Response parsing
$data = json_decode($response, true);
if ($data['Response'] === 'True') {
    // Process metadata
}
```

#### Cover Download System
```php
// Intelligent file naming
$filename = "item_{$itemId}_{$provider}_{$quality}_{$hash}.{$ext}";

// Directory organization
$itemDir = $baseDir . floor($itemId / 1000); // Group by thousands

// Download with size limits
$maxSize = Environment::get('MAX_COVER_FILE_SIZE', 5 * 1024 * 1024);
```

## Configuratie

### Environment Variables
```env
# API Integration
API_ENABLED=true
API_CACHE_ENABLED=true
API_CACHE_LIFETIME=86400
AUTO_COVER_DOWNLOAD=true
COVER_DOWNLOAD_QUALITY=medium

# IGDB Configuration
IGDB_CLIENT_ID=your_twitch_client_id
IGDB_CLIENT_SECRET=your_twitch_client_secret
IGDB_ENABLED=true

# OMDb Configuration  
OMDB_API_KEY=your_omdb_api_key
OMDB_ENABLED=true

# TMDb Configuration (optioneel)
TMDB_API_KEY=your_tmdb_api_key
TMDB_ENABLED=false

# Performance Settings
API_REQUEST_TIMEOUT=30
API_RETRY_ATTEMPTS=3
MAX_COVER_FILE_SIZE=5242880
```

### API Keys Setup

#### IGDB Setup
1. Ga naar [Twitch Developers](https://dev.twitch.tv/console/apps)
2. Maak een nieuwe applicatie aan
3. Noteer Client ID en Client Secret
4. IGDB gebruikt Twitch OAuth voor authenticatie

#### OMDb Setup
1. Ga naar [OMDb API](http://www.omdbapi.com/apikey.aspx)
2. Registreer voor een API key (gratis tier beschikbaar)
3. Bevestig email en ontvang API key
4. Free tier: 1000 requests per dag

#### TMDb Setup (Optioneel)
1. Ga naar [TMDb](https://www.themoviedb.org/settings/api)
2. Registreer account en vraag API key aan
3. Hoge kwaliteit images beschikbaar
4. Rate limit: 40 requests per 10 seconden

## Gebruik

### Automatische Enrichment
```php
// Bij item toevoegen
$result = APIManager::enrichItemMetadata($itemId);

if ($result['success']) {
    echo "Metadata gevonden van: " . implode(', ', $result['providers_used']);
    echo "Covers gedownload: " . count($result['covers']);
}
```

### Barcode Lookup
```php
// Enhanced barcode scanning
$metadata = APIManager::getMetadataByBarcode($barcode);

if ($metadata) {
    echo "Bron: " . $metadata['source'];
    echo "Confidence: " . $metadata['confidence'];
    echo "Type: " . $metadata['type'];
}
```

### Provider Status
```php
// Check beschikbare providers
$providers = APIManager::getAvailableProviders('game');

foreach ($providers as $provider) {
    echo $provider['name'] . ": " . ($provider['enabled'] ? 'Actief' : 'Inactief');
}
```

## Admin Interface

### API Manager Dashboard
- **Provider status** overzicht met connection testing
- **Barcode test tool** voor troubleshooting
- **Item enrichment** interface voor handmatige verrijking
- **Recent activities** log voor monitoring
- **Configuration overview** met key status

### System Tab in Admin
- **API integraties** quick access naar API manager
- **Provider status** indicators
- **Language settings** integration
- **OAuth status** overview

## Performance Optimalisatie

### Caching Strategy
- **Response caching**: API responses 24u gecached
- **Image caching**: Covers lokaal opgeslagen
- **Provider rotation**: Multiple providers voor resilience
- **Lazy loading**: Enrichment on-demand

### Rate Limiting
- **Per-provider limits**: respecteer API restrictions
- **Request queuing**: voorkom bursts
- **Exponential backoff**: bij rate limit hits
- **Circuit breaker**: tijdelijke provider disable

### Storage Optimization
- **Directory structure**: Georganiseerde opslag per 1000 items
- **Multiple qualities**: Verschillende resoluties beschikbaar
- **Compression**: Geoptimaliseerde bestanden
- **Cleanup jobs**: Oude/ongebruikte covers verwijderen

## Error Handling

### API Failures
- **Graceful degradation**: Fallback naar andere providers
- **Retry logic**: Automatische herhalingen
- **Error logging**: Uitgebreide foutregistratie
- **User feedback**: Duidelijke foutmeldingen

### Download Failures
- **Multiple attempts**: 3 pogingen per download
- **Size validation**: File size checking
- **Format validation**: Image format verificatie
- **Cleanup**: Failed downloads worden opgeruimd

## Security Considerations

### API Key Management
- **Environment variables**: Veilige opslag van credentials
- **No hardcoding**: Keys niet in source code
- **Access control**: Admin-only configuration
- **Rotation support**: Key updates mogelijk

### File Security
- **Upload validation**: Strenge bestandscontroles
- **Path traversal**: Preventie van directory traversal
- **File type checking**: Alleen afbeeldingen toegestaan
- **Size limits**: Maximum bestandsgroottes

### Rate Limiting Security
- **IP-based limits**: Per-IP rate limiting
- **Request validation**: Input sanitization
- **DoS protection**: Burst protection
- **Monitoring**: Unusual activity detection

## Monitoring & Logging

### API Statistics
- **Request counting**: Per provider request tracking
- **Success rates**: API call success monitoring
- **Response times**: Performance metrics
- **Error rates**: Failure rate tracking

### Performance Metrics
- **Cache hit rates**: Cache efficiency monitoring
- **Download success**: Cover download statistics
- **Storage usage**: Disk space tracking
- **Queue lengths**: Request queue monitoring

## Deployment

### Database Migration
- Migratie versie 7 wordt automatisch uitgevoerd
- Nieuwe tabellen voor API data
- Default providers worden geconfigureerd
- Bestaande items blijven ongewijzigd

### File System Setup
- Cover storage directory creation
- Permission verification
- Cleanup job scheduling
- Backup strategy implementation

### Configuration Validation
- API key testing during setup
- Provider connectivity verification
- Permission checking
- Resource availability validation

## Troubleshooting

### Common Issues
- **API keys incorrect**: Verify credentials in env file
- **Rate limits exceeded**: Check request frequency
- **Download failures**: Check file permissions and storage space
- **No metadata found**: Verify search terms and provider availability

### Debug Tools
- **Connection testing**: Built-in provider connectivity tests
- **Barcode testing**: Manual lookup testing interface
- **Cache inspection**: Cache content verification
- **Log analysis**: Detailed error logging

### Performance Issues
- **Slow responses**: Check API timeouts and cache settings
- **Storage full**: Monitor disk usage and cleanup
- **High CPU usage**: Optimize concurrent requests
- **Memory leaks**: Monitor cache size limits

## Toekomstige Uitbreidingen

### Additional APIs
- **Steam API**: Game pricing en user reviews
- **Google Books**: Uitgebreide book metadata
- **Last.fm**: Music metadata en scrobbling
- **BoardGameGeek**: Board game database
- **MobyGames**: Vintage game database

### Enhanced Features
- **Bulk enrichment**: Mass processing van bestaande items
- **Scheduled updates**: Automatische metadata updates
- **Conflict resolution**: Smart handling van conflicterende data
- **Custom providers**: Plugin system voor eigen APIs
- **Machine learning**: Intelligent matching improvements

### Analytics
- **Usage statistics**: Provider performance analytics
- **Cost tracking**: API usage cost monitoring
- **Quality metrics**: Metadata accuracy tracking
- **User behavior**: Feature usage analytics

## Bestanden Overzicht

### Nieuwe Bestanden
- `includes/MetadataEnricher.php`: Hoofdfunctionaliteit API enrichment
- `public/api-manager.php`: Admin interface voor API beheer
- `API_INTEGRATION_FEATURE.md`: Deze documentatie

### Aangepaste Bestanden
- `includes/Database.php`: Migratie versie 7 voor API tabellen
- `includes/Environment.php`: API configuratie variabelen
- `includes/APIManager.php`: Uitgebreide API functionaliteit
- `includes/functions.php`: MetadataEnricher autoloading
- `env.template`: API configuratie template
- `public/admin.php`: Systeem tab met API beheer toegang

### Directory Structure
```
uploads/covers/
├── 0/          # Items 0-999
├── 1/          # Items 1000-1999
├── 2/          # Items 2000-2999
└── ...
```

## Testing Checklist

### API Connectivity
- [ ] IGDB OAuth token generation
- [ ] OMDb API key validation
- [ ] TMDb API key validation
- [ ] OpenLibrary accessibility
- [ ] Rate limiting functionality

### Metadata Enrichment
- [ ] Game metadata via IGDB
- [ ] Movie metadata via OMDb
- [ ] Book metadata via OpenLibrary
- [ ] Multi-provider search
- [ ] Confidence scoring

### Cover Downloads
- [ ] Automatic cover download
- [ ] Multiple image qualities
- [ ] Local storage organization
- [ ] Failed download handling
- [ ] File size validation

### Performance
- [ ] Response caching
- [ ] Rate limit compliance
- [ ] Memory usage optimization
- [ ] Database query efficiency
- [ ] Concurrent request handling

### Admin Interface
- [ ] Provider status display
- [ ] Connection testing
- [ ] Barcode lookup testing
- [ ] Item enrichment interface
- [ ] Activity logging 