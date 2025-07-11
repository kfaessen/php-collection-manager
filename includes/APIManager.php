<?php
namespace CollectionManager;

use CollectionManager\Environment;

class APIManager 
{
    /**
     * Get metadata by barcode
     */
    public static function getMetadataByBarcode($barcode) 
    {
        // First try OpenLibrary API for books
        $bookData = self::getBookMetadata($barcode);
        if ($bookData) {
            return $bookData;
        }
        
        // Try other APIs for games/movies
        $gameData = self::getGameMetadata($barcode);
        if ($gameData) {
            return $gameData;
        }
        
        // Try movie/series APIs
        $movieData = self::getMovieMetadata($barcode);
        if ($movieData) {
            return $movieData;
        }
        
        return null;
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