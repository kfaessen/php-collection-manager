<?php
namespace CollectionManager;

use CollectionManager\Environment;
use CollectionManager\Database;
use CollectionManager\Utils;

class MetadataEnricher 
{
    private static $apiProviders = [];
    private static $rateLimits = [];
    
    /**
     * Initialize metadata enricher
     */
    public static function init() 
    {
        self::loadApiProviders();
        self::createDirectories();
    }
    
    /**
     * Check if API enrichment is enabled
     */
    public static function isEnabled() 
    {
        return Environment::get('API_ENABLED', true);
    }
    
    /**
     * Enrich item with metadata from APIs
     */
    public static function enrichItem($itemId, $itemData = null) 
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'API enrichment is disabled'];
        }
        
        try {
            if (!$itemData) {
                $itemData = self::getItemData($itemId);
                if (!$itemData) {
                    return ['success' => false, 'message' => 'Item not found'];
                }
            }
            
            $type = self::detectItemType($itemData);
            $providers = self::getProvidersForType($type);
            
            if (empty($providers)) {
                return ['success' => false, 'message' => 'No providers available for type: ' . $type];
            }
            
            $enrichedData = [];
            $coverUrls = [];
            
            foreach ($providers as $provider) {
                $result = self::searchProvider($provider, $itemData, $type);
                
                if ($result['success'] && !empty($result['data'])) {
                    $enrichedData[$provider['name']] = $result['data'];
                    
                    // Extract cover URLs
                    if (isset($result['data']['covers'])) {
                        $coverUrls = array_merge($coverUrls, $result['data']['covers']);
                    }
                }
            }
            
            if (empty($enrichedData)) {
                return ['success' => false, 'message' => 'No metadata found from any provider'];
            }
            
            // Save enriched metadata
            $savedMetadata = self::saveItemMetadata($itemId, $enrichedData, $type);
            
            // Download covers if enabled
            $downloadedCovers = [];
            if (Environment::get('AUTO_COVER_DOWNLOAD', true) && !empty($coverUrls)) {
                $downloadedCovers = self::downloadCovers($itemId, $coverUrls);
            }
            
            // Update item status
            self::updateItemEnrichmentStatus($itemId, true);
            
            return [
                'success' => true,
                'metadata' => $savedMetadata,
                'covers' => $downloadedCovers,
                'providers_used' => array_keys($enrichedData)
            ];
            
        } catch (\Exception $e) {
            error_log("Metadata enrichment error for item $itemId: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Search specific provider for metadata
     */
    private static function searchProvider($provider, $itemData, $type) 
    {
        if (!self::checkRateLimit($provider['id'])) {
            return ['success' => false, 'message' => 'Rate limit exceeded for ' . $provider['name']];
        }
        
        switch ($provider['name']) {
            case 'IGDB':
                return self::searchIGDB($itemData, $type);
            case 'OMDb':
                return self::searchOMDb($itemData, $type);
            case 'TMDb':
                return self::searchTMDb($itemData, $type);
            case 'OpenLibrary':
                return self::searchOpenLibrary($itemData, $type);
            default:
                return ['success' => false, 'message' => 'Unknown provider: ' . $provider['name']];
        }
    }
    
    /**
     * Search IGDB for game metadata
     */
    private static function searchIGDB($itemData, $type) 
    {
        if (!Environment::get('IGDB_ENABLED', true) || $type !== 'game') {
            return ['success' => false, 'message' => 'IGDB not enabled or wrong type'];
        }
        
        $accessToken = self::getIGDBAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'message' => 'IGDB access token not available'];
        }
        
        // Build search query
        $searchTerm = self::cleanSearchTerm($itemData['title']);
        $platform = self::mapPlatform($itemData['platform'] ?? '');
        
        $query = "search \"$searchTerm\"; fields name,summary,first_release_date,genres.name,involved_companies.company.name,cover.url,cover.image_id,platforms.name,rating,rating_count,screenshots.url; limit 10;";
        
        if ($platform) {
            $query = "search \"$searchTerm\"; fields name,summary,first_release_date,genres.name,involved_companies.company.name,cover.url,cover.image_id,platforms.name,rating,rating_count,screenshots.url; where platforms = ($platform); limit 10;";
        }
        
        $response = self::makeIGDBRequest('games', $query, $accessToken);
        
        if (!$response['success']) {
            return $response;
        }
        
        $games = json_decode($response['data'], true);
        if (empty($games)) {
            return ['success' => false, 'message' => 'No games found'];
        }
        
        // Find best match
        $bestMatch = self::findBestGameMatch($games, $itemData);
        if (!$bestMatch) {
            return ['success' => false, 'message' => 'No suitable match found'];
        }
        
        // Format metadata
        $metadata = self::formatIGDBMetadata($bestMatch);
        
        return ['success' => true, 'data' => $metadata];
    }
    
    /**
     * Search OMDb for movie/TV metadata
     */
    private static function searchOMDb($itemData, $type) 
    {
        if (!Environment::get('OMDB_ENABLED', true) || !in_array($type, ['movie', 'tv_series'])) {
            return ['success' => false, 'message' => 'OMDb not enabled or wrong type'];
        }
        
        $apiKey = Environment::get('OMDB_API_KEY');
        if (!$apiKey) {
            return ['success' => false, 'message' => 'OMDb API key not configured'];
        }
        
        $searchTerm = self::cleanSearchTerm($itemData['title']);
        $movieType = $type === 'tv_series' ? 'series' : 'movie';
        
        // Try exact title search first
        $url = "http://www.omdbapi.com/?apikey=$apiKey&t=" . urlencode($searchTerm) . "&type=$movieType&plot=full";
        
        $response = self::makeHttpRequest($url);
        if (!$response['success']) {
            return $response;
        }
        
        $data = json_decode($response['data'], true);
        if (!$data || $data['Response'] === 'False') {
            // Try search by title
            $url = "http://www.omdbapi.com/?apikey=$apiKey&s=" . urlencode($searchTerm) . "&type=$movieType";
            $response = self::makeHttpRequest($url);
            
            if (!$response['success']) {
                return $response;
            }
            
            $searchData = json_decode($response['data'], true);
            if (!$searchData || $searchData['Response'] === 'False' || empty($searchData['Search'])) {
                return ['success' => false, 'message' => 'No results found'];
            }
            
            // Get detailed info for best match
            $bestMatch = self::findBestMovieMatch($searchData['Search'], $itemData);
            if (!$bestMatch) {
                return ['success' => false, 'message' => 'No suitable match found'];
            }
            
            $url = "http://www.omdbapi.com/?apikey=$apiKey&i=" . $bestMatch['imdbID'] . "&plot=full";
            $response = self::makeHttpRequest($url);
            
            if (!$response['success']) {
                return $response;
            }
            
            $data = json_decode($response['data'], true);
        }
        
        if (!$data || $data['Response'] === 'False') {
            return ['success' => false, 'message' => 'No detailed data found'];
        }
        
        // Format metadata
        $metadata = self::formatOMDbMetadata($data);
        
        return ['success' => true, 'data' => $metadata];
    }
    
    /**
     * Get or refresh IGDB access token
     */
    private static function getIGDBAccessToken() 
    {
        // First try to get from database cache
        $cachedToken = self::getCachedToken('IGDB_ACCESS_TOKEN');
        if ($cachedToken && !self::isTokenExpired($cachedToken)) {
            return $cachedToken['token'];
        }
        
        // Request new token
        $clientId = Environment::get('IGDB_CLIENT_ID');
        $clientSecret = Environment::get('IGDB_CLIENT_SECRET');
        
        if (!$clientId || !$clientSecret) {
            return null;
        }
        
        $url = "https://id.twitch.tv/oauth2/token?client_id=$clientId&client_secret=$clientSecret&grant_type=client_credentials";
        
        $response = self::makeHttpRequest($url, 'POST');
        if (!$response['success']) {
            return null;
        }
        
        $data = json_decode($response['data'], true);
        if (!$data || !isset($data['access_token'])) {
            return null;
        }
        
        // Calculate expiry time (IGDB tokens typically expire in 60 days)
        $expiresIn = $data['expires_in'] ?? (60 * 24 * 60 * 60); // Default to 60 days
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        
        // Save token to database cache
        self::saveCachedToken('IGDB_ACCESS_TOKEN', $data['access_token'], $expiresAt);
        
        return $data['access_token'];
    }
    
    /**
     * Make IGDB API request
     */
    private static function makeIGDBRequest($endpoint, $query, $accessToken) 
    {
        $url = "https://api.igdb.com/v4/$endpoint";
        $clientId = Environment::get('IGDB_CLIENT_ID');
        
        $headers = [
            'Client-ID: ' . $clientId,
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: text/plain'
        ];
        
        return self::makeHttpRequest($url, 'POST', $query, $headers);
    }
    
    /**
     * Download covers for item
     */
    private static function downloadCovers($itemId, $coverUrls) 
    {
        $downloadedCovers = [];
        $maxFileSize = Environment::get('MAX_COVER_FILE_SIZE', 5 * 1024 * 1024);
        $uploadDir = self::getCoverDirectory($itemId);
        
        foreach ($coverUrls as $coverData) {
            try {
                $url = self::buildCoverUrl($coverData);
                if (!$url) continue;
                
                $filename = self::generateCoverFilename($itemId, $coverData);
                $localPath = $uploadDir . '/' . $filename;
                
                // Check if already downloaded
                if (file_exists($localPath)) {
                    $downloadedCovers[] = [
                        'url' => $url,
                        'local_path' => $localPath,
                        'status' => 'already_exists'
                    ];
                    continue;
                }
                
                // Download file
                $response = self::downloadFile($url, $localPath, $maxFileSize);
                
                if ($response['success']) {
                    // Save cover record to database
                    $coverId = self::saveCoverRecord($itemId, $coverData, $localPath, $response);
                    
                    $downloadedCovers[] = [
                        'id' => $coverId,
                        'url' => $url,
                        'local_path' => $localPath,
                        'status' => 'downloaded',
                        'file_size' => $response['file_size']
                    ];
                } else {
                    $downloadedCovers[] = [
                        'url' => $url,
                        'status' => 'failed',
                        'error' => $response['error']
                    ];
                }
                
            } catch (\Exception $e) {
                error_log("Cover download error: " . $e->getMessage());
            }
        }
        
        return $downloadedCovers;
    }
    
    /**
     * Detect item type based on item data
     */
    private static function detectItemType($itemData) 
    {
        $type = strtolower($itemData['type'] ?? '');
        
        // Map common type variations
        $typeMap = [
            'game' => 'game',
            'games' => 'game',
            'videogame' => 'game',
            'movie' => 'movie',
            'film' => 'movie',
            'cinema' => 'movie',
            'tv' => 'tv_series',
            'series' => 'tv_series',
            'tv_series' => 'tv_series',
            'television' => 'tv_series',
            'book' => 'book',
            'ebook' => 'book',
            'novel' => 'book',
            'music' => 'music',
            'album' => 'music',
            'cd' => 'music'
        ];
        
        return $typeMap[$type] ?? 'game'; // Default to game
    }
    
    /**
     * Get appropriate providers for item type
     */
    private static function getProvidersForType($type) 
    {
        $providers = [];
        
        switch ($type) {
            case 'game':
                if (Environment::get('IGDB_ENABLED', true)) {
                    $providers[] = self::$apiProviders['IGDB'] ?? null;
                }
                break;
                
            case 'movie':
            case 'tv_series':
                if (Environment::get('OMDB_ENABLED', true)) {
                    $providers[] = self::$apiProviders['OMDb'] ?? null;
                }
                if (Environment::get('TMDB_ENABLED', false)) {
                    $providers[] = self::$apiProviders['TMDb'] ?? null;
                }
                break;
                
            case 'book':
                $providers[] = self::$apiProviders['OpenLibrary'] ?? null;
                break;
                
            case 'music':
                if (Environment::get('SPOTIFY_ENABLED', false)) {
                    $providers[] = self::$apiProviders['Spotify'] ?? null;
                }
                break;
        }
        
        return array_filter($providers);
    }
    
    /**
     * Save item metadata to database
     */
    private static function saveItemMetadata($itemId, $enrichedData, $type) 
    {
        $savedMetadata = [];
        $metadataTable = Environment::getTableName('item_metadata');
        
        foreach ($enrichedData as $providerName => $metadata) {
            $provider = self::$apiProviders[$providerName] ?? null;
            if (!$provider) continue;
            
            $sql = "INSERT INTO `$metadataTable` 
                    (item_id, provider_id, external_id, metadata_type, title, description, release_date, 
                     genre, developer, publisher, director, actors, rating, imdb_rating, metacritic_score, 
                     runtime_minutes, language, country, platforms, tags, price_info) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    title = VALUES(title), description = VALUES(description), 
                    release_date = VALUES(release_date), genre = VALUES(genre),
                    developer = VALUES(developer), publisher = VALUES(publisher),
                    director = VALUES(director), actors = VALUES(actors),
                    rating = VALUES(rating), imdb_rating = VALUES(imdb_rating),
                    metacritic_score = VALUES(metacritic_score), runtime_minutes = VALUES(runtime_minutes),
                    language = VALUES(language), country = VALUES(country),
                    platforms = VALUES(platforms), tags = VALUES(tags),
                    price_info = VALUES(price_info), last_updated = CURRENT_TIMESTAMP";
            
            Database::query($sql, [
                $itemId,
                $provider['id'],
                $metadata['external_id'] ?? null,
                $type,
                $metadata['title'] ?? null,
                $metadata['description'] ?? null,
                $metadata['release_date'] ?? null,
                $metadata['genre'] ?? null,
                $metadata['developer'] ?? null,
                $metadata['publisher'] ?? null,
                $metadata['director'] ?? null,
                $metadata['actors'] ?? null,
                $metadata['rating'] ?? null,
                $metadata['imdb_rating'] ?? null,
                $metadata['metacritic_score'] ?? null,
                $metadata['runtime_minutes'] ?? null,
                $metadata['language'] ?? null,
                $metadata['country'] ?? null,
                $metadata['platforms'] ?? null,
                $metadata['tags'] ?? null,
                $metadata['price_info'] ?? null
            ]);
            
            $savedMetadata[$providerName] = $metadata;
        }
        
        return $savedMetadata;
    }
    
    /**
     * Helper functions
     */
    private static function loadApiProviders() 
    {
        try {
            $providersTable = Environment::getTableName('api_providers');
            $sql = "SELECT * FROM `$providersTable` WHERE is_active = 1";
            $stmt = Database::query($sql);
            
            while ($provider = $stmt->fetch()) {
                self::$apiProviders[$provider['name']] = $provider;
            }
        } catch (\Exception $e) {
            // Database not ready yet
        }
    }
    
    private static function getItemData($itemId) 
    {
        $itemsTable = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$itemsTable` WHERE id = ?";
        $stmt = Database::query($sql, [$itemId]);
        return $stmt->fetch();
    }
    
    private static function updateItemEnrichmentStatus($itemId, $enriched) 
    {
        $itemsTable = Environment::getTableName('collection_items');
        $sql = "UPDATE `$itemsTable` SET api_enriched = ?, last_api_check = CURRENT_TIMESTAMP WHERE id = ?";
        Database::query($sql, [$enriched, $itemId]);
    }
    
    private static function createDirectories() 
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . Environment::get('COVER_STORAGE_PATH', '/uploads/covers/');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
    }
    
    private static function getCoverDirectory($itemId) 
    {
        $baseDir = $_SERVER['DOCUMENT_ROOT'] . Environment::get('COVER_STORAGE_PATH', '/uploads/covers/');
        $itemDir = $baseDir . floor($itemId / 1000); // Group by thousands
        
        if (!file_exists($itemDir)) {
            mkdir($itemDir, 0755, true);
        }
        
        return $itemDir;
    }
    
    private static function checkRateLimit($providerId) 
    {
        // Simplified rate limiting - in production you'd use a more sophisticated system
        $now = time();
        $windowStart = $now - 60; // 1 minute window
        
        if (!isset(self::$rateLimits[$providerId])) {
            self::$rateLimits[$providerId] = ['count' => 0, 'window_start' => $now];
        }
        
        $rateLimit = self::$rateLimits[$providerId];
        
        if ($rateLimit['window_start'] < $windowStart) {
            // Reset window
            self::$rateLimits[$providerId] = ['count' => 1, 'window_start' => $now];
            return true;
        }
        
        $provider = array_search($providerId, array_column(self::$apiProviders, 'id'));
        $maxRequests = self::$apiProviders[$provider]['rate_limit_per_minute'] ?? 60;
        
        if ($rateLimit['count'] >= $maxRequests) {
            return false;
        }
        
        self::$rateLimits[$providerId]['count']++;
        return true;
    }
    
    private static function makeHttpRequest($url, $method = 'GET', $data = null, $headers = []) 
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => Environment::get('API_REQUEST_TIMEOUT', 30),
            CURLOPT_USERAGENT => 'CollectionManager/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => 'HTTP request error: ' . $error];
        }
        
        if ($httpCode >= 400) {
            return ['success' => false, 'error' => "HTTP error $httpCode: $response"];
        }
        
        return ['success' => true, 'data' => $response, 'http_code' => $httpCode];
    }
    
    private static function cleanSearchTerm($title) 
    {
        // Remove common suffixes and clean up title
        $title = preg_replace('/\s*\([^)]*\)/', '', $title); // Remove parentheses content
        $title = preg_replace('/[^\w\s]/', '', $title); // Remove special characters
        $title = trim($title);
        
        return $title;
    }
    
    /**
     * Get cached token from database
     */
    private static function getCachedToken($tokenKey) 
    {
        try {
            $cacheTable = Environment::getTableName('api_cache');
            $sql = "SELECT response_data, expires_at FROM `$cacheTable` WHERE cache_key = ? AND expires_at > NOW() LIMIT 1";
            $stmt = Database::query($sql, [$tokenKey]);
            $result = $stmt->fetch();
            
            if ($result) {
                $tokenData = json_decode($result['response_data'], true);
                return [
                    'token' => $tokenData['token'] ?? null,
                    'expires_at' => $result['expires_at']
                ];
            }
        } catch (\Exception $e) {
            error_log("Error getting cached token: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Save token to database cache
     */
    private static function saveCachedToken($tokenKey, $token, $expiresAt) 
    {
        try {
            $cacheTable = Environment::getTableName('api_cache');
            $tokenData = json_encode(['token' => $token]);
            
            $sql = "INSERT INTO `$cacheTable` (provider_id, cache_key, response_data, expires_at) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    response_data = VALUES(response_data), 
                    expires_at = VALUES(expires_at)";
            
            // Use provider_id 0 for system tokens (System provider)
            Database::query($sql, [0, $tokenKey, $tokenData, $expiresAt]);
            
        } catch (\Exception $e) {
            error_log("Error saving cached token: " . $e->getMessage());
        }
    }
    
    /**
     * Check if token is expired
     */
    private static function isTokenExpired($tokenData) 
    {
        if (!$tokenData || !isset($tokenData['expires_at'])) {
            return true;
        }
        
        $expiresAt = strtotime($tokenData['expires_at']);
        $now = time();
        
        // Consider token expired if it expires within the next 5 minutes
        return $expiresAt <= ($now + 300);
    }
    
    // Additional helper methods would be implemented here for:
    // - findBestGameMatch()
    // - findBestMovieMatch() 
    // - formatIGDBMetadata()
    // - formatOMDbMetadata()
    // - mapPlatform()
    // - buildCoverUrl()
    // - generateCoverFilename()
    // - downloadFile()
    // - saveCoverRecord()
    // etc.
} 