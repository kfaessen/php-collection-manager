<?php
namespace CollectionManager;

use CollectionManager\Environment;

class APIManager 
{
    /**
     * Get metadata by barcode with enhanced API integration
     */
    public static function getMetadataByBarcode($barcode) 
    {
        // Create temporary item data for enrichment
        $tempItemData = [
            'barcode' => $barcode,
            'title' => '', // Will be filled by API
            'type' => 'unknown'
        ];
        
        // Try to determine type and get metadata from multiple sources
        $results = [];
        
        // Try OpenLibrary for books (legacy support)
        $bookData = self::getBookMetadata($barcode);
        if ($bookData) {
            $bookData['source'] = 'OpenLibrary';
            $bookData['type'] = 'book';
            $results[] = $bookData;
        }
        
        // Try enhanced metadata enrichment if available
        if (class_exists('MetadataEnricher') && MetadataEnricher::isEnabled()) {
            $enrichmentResult = self::tryMetadataEnrichmentByBarcode($barcode);
            if ($enrichmentResult) {
                $results = array_merge($results, $enrichmentResult);
            }
        }
        
        // Return best result or legacy fallback
        if (!empty($results)) {
            return self::selectBestMetadataResult($results);
        }
        
        // Legacy fallback
        $gameData = self::getGameMetadata($barcode);
        if ($gameData) {
            return $gameData;
        }
        
        $movieData = self::getMovieMetadata($barcode);
        if ($movieData) {
            return $movieData;
        }
        
        return null;
    }
    
    /**
     * Enrich existing item with metadata from APIs
     */
    public static function enrichItemMetadata($itemId, $itemData = null) 
    {
        if (!class_exists('MetadataEnricher') || !MetadataEnricher::isEnabled()) {
            return ['success' => false, 'message' => 'Metadata enrichment not available'];
        }
        
        return MetadataEnricher::enrichItem($itemId, $itemData);
    }
    
    /**
     * Get available API providers
     */
    public static function getAvailableProviders($type = null) 
    {
        $providers = [];
        
        // Check IGDB
        if (Environment::get('IGDB_ENABLED', true) && Environment::get('IGDB_CLIENT_ID')) {
            $providers['IGDB'] = [
                'name' => 'IGDB',
                'description' => 'Internet Game Database',
                'types' => ['game'],
                'enabled' => true
            ];
        }
        
        // Check OMDb
        if (Environment::get('OMDB_ENABLED', true) && Environment::get('OMDB_API_KEY')) {
            $providers['OMDb'] = [
                'name' => 'OMDb',
                'description' => 'Open Movie Database',
                'types' => ['movie', 'tv_series'],
                'enabled' => true
            ];
        }
        
        // Check TMDb
        if (Environment::get('TMDB_ENABLED', false) && Environment::get('TMDB_API_KEY')) {
            $providers['TMDb'] = [
                'name' => 'TMDb',
                'description' => 'The Movie Database',
                'types' => ['movie', 'tv_series'],
                'enabled' => true
            ];
        }
        
        // OpenLibrary (no API key required)
        $providers['OpenLibrary'] = [
            'name' => 'OpenLibrary',
            'description' => 'Open Library',
            'types' => ['book'],
            'enabled' => true
        ];
        
        // Filter by type if specified
        if ($type) {
            $providers = array_filter($providers, function($provider) use ($type) {
                return in_array($type, $provider['types']);
            });
        }
        
        return $providers;
    }
    
    /**
     * Try metadata enrichment by barcode
     */
    private static function tryMetadataEnrichmentByBarcode($barcode) 
    {
        $results = [];
        
        // Search different types with barcode
        $searchTypes = ['game', 'movie', 'book'];
        
        foreach ($searchTypes as $type) {
            $tempItemData = [
                'barcode' => $barcode,
                'title' => $barcode, // Use barcode as title for search
                'type' => $type
            ];
            
            $enrichResult = MetadataEnricher::enrichItem(0, $tempItemData); // Use 0 as temp ID
            
            if ($enrichResult['success'] && !empty($enrichResult['metadata'])) {
                foreach ($enrichResult['metadata'] as $providerName => $metadata) {
                    $result = $metadata;
                    $result['source'] = $providerName;
                    $result['type'] = $type;
                    $result['confidence'] = self::calculateBarcodeMatchConfidence($barcode, $metadata);
                    $results[] = $result;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Select best metadata result from multiple sources
     */
    private static function selectBestMetadataResult($results) 
    {
        if (empty($results)) {
            return null;
        }
        
        if (count($results) === 1) {
            return $results[0];
        }
        
        // Sort by confidence score if available
        usort($results, function($a, $b) {
            $confA = $a['confidence'] ?? 0.5;
            $confB = $b['confidence'] ?? 0.5;
            
            return $confB <=> $confA; // Descending order
        });
        
        return $results[0];
    }
    
    /**
     * Calculate match confidence for barcode search
     */
    private static function calculateBarcodeMatchConfidence($barcode, $metadata) 
    {
        $confidence = 0.5; // Base confidence
        
        // Increase confidence if barcode matches exactly
        if (isset($metadata['barcode']) && $metadata['barcode'] === $barcode) {
            $confidence = 0.95;
        }
        
        // Check for ISBN/UPC/EAN matches
        if (isset($metadata['isbn']) && self::barcodeMatches($barcode, $metadata['isbn'])) {
            $confidence = 0.90;
        }
        
        if (isset($metadata['upc']) && self::barcodeMatches($barcode, $metadata['upc'])) {
            $confidence = 0.85;
        }
        
        return $confidence;
    }
    
    /**
     * Check if barcode matches various identifier formats
     */
    private static function barcodeMatches($barcode, $identifier) 
    {
        // Remove dashes and spaces
        $cleanBarcode = preg_replace('/[^0-9]/', '', $barcode);
        $cleanIdentifier = preg_replace('/[^0-9]/', '', $identifier);
        
        return $cleanBarcode === $cleanIdentifier;
    }
    
    /**
     * Get book metadata from OpenLibrary
     */
    private static function getBookMetadata($barcode) 
    {
        $url = "https://openlibrary.org/api/books?bibkeys=ISBN:$barcode&format=json&jscmd=data";
        
        $response = self::makeRequest($url);
        if (!$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        $key = "ISBN:$barcode";
        
        if (!isset($data[$key])) {
            return null;
        }
        
        $book = $data[$key];
        
        return [
            'title' => $book['title'] ?? '',
            'type' => 'book',
            'barcode' => $barcode,
            'director' => isset($book['authors'][0]) ? $book['authors'][0]['name'] : '',
            'publisher' => isset($book['publishers'][0]) ? $book['publishers'][0]['name'] : '',
            'description' => isset($book['excerpts'][0]) ? $book['excerpts'][0]['text'] : '',
            'cover_image' => $book['cover']['large'] ?? $book['cover']['medium'] ?? '',
            'platform' => '',
            'metadata' => $book
        ];
    }
    
    /**
     * Get game metadata (placeholder - would need actual game API)
     */
    private static function getGameMetadata($barcode) 
    {
        // Placeholder for game API integration
        // Could integrate with IGDB, Steam, etc.
        return null;
    }
    
    /**
     * Get movie/series metadata (placeholder - would need actual movie API)
     */
    private static function getMovieMetadata($barcode) 
    {
        // Placeholder for movie API integration
        // Could integrate with TMDB, OMDB, etc.
        return null;
    }
    
    /**
     * Make HTTP request with timeout
     */
    private static function makeRequest($url) 
    {
        $timeout = Environment::get('API_TIMEOUT', 30);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'user_agent' => 'CollectionManager/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        return $response;
    }
    
    /**
     * Search for metadata by title
     */
    public static function searchByTitle($title, $type = '') 
    {
        $title = urlencode($title);
        
        switch ($type) {
            case 'book':
                return self::searchBooks($title);
            case 'game':
                return self::searchGames($title);
            case 'film':
            case 'serie':
                return self::searchMovies($title);
            default:
                // Try all types
                $results = [];
                $results = array_merge($results, self::searchBooks($title) ?? []);
                $results = array_merge($results, self::searchGames($title) ?? []);
                $results = array_merge($results, self::searchMovies($title) ?? []);
                return $results;
        }
    }
    
    /**
     * Search books
     */
    private static function searchBooks($title) 
    {
        $url = "https://openlibrary.org/search.json?title=$title&limit=5";
        
        $response = self::makeRequest($url);
        if (!$response) {
            return [];
        }
        
        $data = json_decode($response, true);
        $results = [];
        
        if (isset($data['docs'])) {
            foreach ($data['docs'] as $book) {
                $results[] = [
                    'title' => $book['title'] ?? '',
                    'type' => 'book',
                    'director' => isset($book['author_name'][0]) ? $book['author_name'][0] : '',
                    'publisher' => isset($book['publisher'][0]) ? $book['publisher'][0] : '',
                    'description' => '',
                    'cover_image' => isset($book['cover_i']) ? "https://covers.openlibrary.org/b/id/{$book['cover_i']}-L.jpg" : '',
                    'barcode' => isset($book['isbn'][0]) ? $book['isbn'][0] : '',
                    'platform' => '',
                    'metadata' => $book
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Search games (placeholder)
     */
    private static function searchGames($title) 
    {
        // Placeholder for game search
        return [];
    }
    
    /**
     * Search movies (placeholder)
     */
    private static function searchMovies($title) 
    {
        // Placeholder for movie search
        return [];
    }
} 