<?php
/**
 * Collection Manager Functions
 * 
 * Alle hulpfuncties voor de collectiebeheer applicatie
 */

require_once __DIR__ . '/db.php';

/**
 * Collectie Item Management
 */
class CollectionManager 
{
    /**
     * Haal alle items op met optionele filtering
     */
    public static function getItems($type = null, $search = null, $limit = null, $offset = 0) 
    {
        $sql = "SELECT * FROM {prefix}items WHERE 1=1";
        $params = [];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        if ($search) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR platform LIKE ? OR director LIKE ? OR publisher LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Haal één item op via ID
     */
    public static function getItem($id) 
    {
        return Database::fetchOne("SELECT * FROM {prefix}items WHERE id = ?", [$id]);
    }
    
    /**
     * Haal item op via barcode
     */
    public static function getItemByBarcode($barcode) 
    {
        return Database::fetchOne("SELECT * FROM {prefix}items WHERE barcode = ?", [$barcode]);
    }
    
    /**
     * Voeg nieuw item toe
     */
    public static function addItem($data) 
    {
        $sql = "INSERT INTO {prefix}items (title, type, barcode, platform, director, publisher, description, cover_image, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['title'],
            $data['type'],
            $data['barcode'] ?? null,
            $data['platform'] ?? null,
            $data['director'] ?? null,
            $data['publisher'] ?? null,
            $data['description'] ?? null,
            $data['cover_image'] ?? null,
            json_encode($data['metadata'] ?? [])
        ];
        
        return Database::insert($sql, $params);
    }
    
    /**
     * Update bestaand item
     */
    public static function updateItem($id, $data) 
    {
        $sql = "UPDATE {prefix}items SET 
                title = ?, type = ?, barcode = ?, platform = ?, director = ?, 
                publisher = ?, description = ?, cover_image = ?, metadata = ?, updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $data['title'],
            $data['type'],
            $data['barcode'] ?? null,
            $data['platform'] ?? null,
            $data['director'] ?? null,
            $data['publisher'] ?? null,
            $data['description'] ?? null,
            $data['cover_image'] ?? null,
            json_encode($data['metadata'] ?? []),
            $id
        ];
        
        return Database::execute($sql, $params);
    }
    
    /**
     * Verwijder item
     */
    public static function deleteItem($id) 
    {
        return Database::execute("DELETE FROM {prefix}items WHERE id = ?", [$id]);
    }
    
    /**
     * Tel totaal aantal items
     */
    public static function countItems($type = null, $search = null) 
    {
        $sql = "SELECT COUNT(*) as count FROM {prefix}items WHERE 1=1";
        $params = [];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        if ($search) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR platform LIKE ? OR director LIKE ? OR publisher LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $result = Database::fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
}

/**
 * API Integration Handler
 */
class APIManager 
{
    /**
     * Haal metadata op voor barcode
     */
    public static function getMetadataByBarcode($barcode) 
    {
        // Check cache eerst
        $cached = self::getCachedMetadata($barcode);
        if ($cached) {
            return $cached;
        }
        
        $metadata = null;
        
        // Probeer verschillende APIs
        $metadata = self::tryOMDbAPI($barcode);
        if (!$metadata) {
            $metadata = self::tryIGDBAPI($barcode);
        }
        if (!$metadata) {
            $metadata = self::tryUPCItemDB($barcode);
        }
        
        // Cache het resultaat
        if ($metadata) {
            self::cacheMetadata($barcode, $metadata['source'], $metadata);
        }
        
        return $metadata;
    }
    
    /**
     * Check gecachte metadata
     */
    private static function getCachedMetadata($barcode) 
    {
        $sql = "SELECT * FROM {prefix}api_cache 
                WHERE barcode = ? AND (expires_at IS NULL OR expires_at > NOW()) 
                ORDER BY created_at DESC LIMIT 1";
        
        $result = Database::fetchOne($sql, [$barcode]);
        
        if ($result) {
            return json_decode($result['metadata'], true);
        }
        
        return null;
    }
    
    /**
     * Cache metadata
     */
    private static function cacheMetadata($barcode, $source, $metadata) 
    {
        $sql = "INSERT INTO {prefix}api_cache (barcode, api_source, metadata, expires_at) 
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
                ON DUPLICATE KEY UPDATE 
                metadata = VALUES(metadata), 
                expires_at = VALUES(expires_at),
                created_at = NOW()";
        
        Database::execute($sql, [$barcode, $source, json_encode($metadata)]);
    }
    
    /**
     * Probeer OMDb API (voor films/series)
     */
    private static function tryOMDbAPI($barcode) 
    {
        $apiKey = Environment::get('OMDB_API_KEY');
        if (!$apiKey || $apiKey === 'xxxxxx') {
            return null;
        }
        
        $url = "http://www.omdbapi.com/?apikey={$apiKey}&type=movie&plot=full&r=json&i={$barcode}";
        
        $response = @file_get_contents($url);
        if (!$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        if ($data['Response'] !== 'True') {
            return null;
        }
        
        return [
            'source' => 'omdb',
            'title' => $data['Title'] ?? '',
            'type' => strtolower($data['Type']) === 'series' ? 'serie' : 'film',
            'description' => $data['Plot'] ?? '',
            'director' => $data['Director'] ?? '',
            'year' => $data['Year'] ?? '',
            'genre' => $data['Genre'] ?? '',
            'rating' => $data['imdbRating'] ?? '',
            'cover_image' => $data['Poster'] ?? '',
            'metadata' => $data
        ];
    }
    
    /**
     * Probeer IGDB API (voor games)
     */
    private static function tryIGDBAPI($barcode) 
    {
        $clientId = Environment::get('IGDB_CLIENT_ID');
        $secret = Environment::get('IGDB_SECRET');
        
        if (!$clientId || !$secret || $clientId === 'xxxx') {
            return null;
        }
        
        // IGDB implementatie zou hier komen
        // Voor nu returnen we null omdat IGDB complexer is
        return null;
    }
    
    /**
     * Probeer UPCitemDB API (fallback)
     */
    private static function tryUPCItemDB($barcode) 
    {
        $url = "https://api.upcitemdb.com/prod/trial/lookup?upc={$barcode}";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: PHP Collection Manager'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if (!$response) {
            return null;
        }
        
        $data = json_decode($response, true);
        if (!isset($data['items'][0])) {
            return null;
        }
        
        $item = $data['items'][0];
        
        return [
            'source' => 'upcitemdb',
            'title' => $item['title'] ?? '',
            'type' => 'game', // Default type
            'description' => $item['description'] ?? '',
            'publisher' => $item['brand'] ?? '',
            'cover_image' => $item['images'][0] ?? '',
            'metadata' => $item
        ];
    }
}

/**
 * Utility Functions
 */
class Utils 
{
    /**
     * Sanitize input
     */
    public static function sanitize($input) 
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valideer barcode
     */
    public static function validateBarcode($barcode) 
    {
        return preg_match('/^[0-9]{8,14}$/', $barcode);
    }
    
    /**
     * Upload afbeelding
     */
    public static function uploadImage($file) 
    {
        $uploadDir = dirname(__DIR__) . '/uploads/';
        $allowedTypes = explode(',', Environment::get('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,gif'));
        $maxSize = Environment::get('MAX_UPLOAD_SIZE', 5242880); // 5MB default
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload fout: ' . $file['error']);
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('Bestand is te groot');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Bestandstype niet toegestaan');
        }
        
        $filename = uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Kon bestand niet opslaan');
        }
        
        return 'uploads/' . $filename;
    }
    
    /**
     * Format datum
     */
    public static function formatDate($date) 
    {
        return date('d-m-Y H:i', strtotime($date));
    }
    
    /**
     * Generate pagination
     */
    public static function generatePagination($currentPage, $totalItems, $itemsPerPage, $baseUrl) 
    {
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        if ($totalPages <= 1) {
            return '';
        }
        
        $html = '<nav aria-label="Paginering"><ul class="pagination">';
        
        // Previous button
        if ($currentPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">Vorige</a></li>';
        }
        
        // Page numbers
        for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
            $active = $i === $currentPage ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
        }
        
        // Next button
        if ($currentPage < $totalPages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">Volgende</a></li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }
    
    /**
     * JSON response
     */
    public static function jsonResponse($data, $status = 200) 
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Error response
     */
    public static function errorResponse($message, $status = 400) 
    {
        self::jsonResponse(['error' => $message], $status);
    }
    
    /**
     * Success response
     */
    public static function successResponse($data = null, $message = 'Success') 
    {
        $response = ['success' => true, 'message' => $message];
        if ($data) {
            $response['data'] = $data;
        }
        self::jsonResponse($response);
    }
}

/**
 * Initialize database tables als ze nog niet bestaan
 */
try {
    Database::createTables();
} catch (Exception $e) {
    if (Environment::isDebug()) {
        error_log("Database initialization failed: " . $e->getMessage());
    }
}
?> 