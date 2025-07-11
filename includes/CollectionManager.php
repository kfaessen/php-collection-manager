<?php
namespace CollectionManager;

use CollectionManager\Database;
use CollectionManager\Environment;

class CollectionManager 
{
    /**
     * Get items from collection
     */
    public static function getItems($typeFilter = '', $search = '', $limit = 12, $offset = 0, $userId = null) 
    {
        // If no userId specified, use current user or show all if admin
        if ($userId === null) {
            if (Authentication::hasPermission('view_all_collections')) {
                // Admin can see all collections
                $userId = null;
            } else {
                // Regular user can only see their own
                $userId = Authentication::getCurrentUserId();
            }
        }
        
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$tableName` WHERE 1=1";
        $params = [];
        
        // Filter by user if specified
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        if ($typeFilter) {
            $sql .= " AND type = ?";
            $params[] = $typeFilter;
        }
        
        if ($search) {
            $sql .= " AND (title LIKE ? OR director LIKE ? OR publisher LIKE ? OR description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = Database::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Count items in collection
     */
    public static function countItems($typeFilter = '', $search = '', $userId = null) 
    {
        // If no userId specified, use current user or show all if admin
        if ($userId === null) {
            if (Authentication::hasPermission('view_all_collections')) {
                // Admin can see all collections
                $userId = null;
            } else {
                // Regular user can only see their own
                $userId = Authentication::getCurrentUserId();
            }
        }
        
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT COUNT(*) as total FROM `$tableName` WHERE 1=1";
        $params = [];
        
        // Filter by user if specified
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        if ($typeFilter) {
            $sql .= " AND type = ?";
            $params[] = $typeFilter;
        }
        
        if ($search) {
            $sql .= " AND (title LIKE ? OR director LIKE ? OR publisher LIKE ? OR description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $stmt = Database::query($sql, $params);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Get item by ID
     */
    public static function getItemById($id) 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$tableName` WHERE id = ?";
        $stmt = Database::query($sql, [$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get item by barcode (for current user)
     */
    public static function getItemByBarcode($barcode, $userId = null) 
    {
        // Use current user if no userId specified
        if ($userId === null) {
            $userId = Authentication::getCurrentUserId();
        }
        
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$tableName` WHERE barcode = ? AND user_id = ?";
        $stmt = Database::query($sql, [$barcode, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Add new item to collection
     */
    public static function addItem($data, $userId = null) 
    {
        // Require login to add items
        Authentication::requireLogin();
        
        // Use current user if no userId specified
        if ($userId === null) {
            $userId = Authentication::getCurrentUserId();
        }
        
        // Check if user has permission to add items
        if (!Authentication::hasPermission('manage_own_collection')) {
            Authentication::requirePermission('manage_all_collections');
        }
        
        $tableName = Environment::getTableName('collection_items');
        $sql = "INSERT INTO `$tableName` (user_id, title, type, barcode, platform, director, publisher, description, cover_image, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $userId,
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
        
        Database::query($sql, $params);
        return Database::lastInsertId();
    }
    
    /**
     * Update item in collection
     */
    public static function updateItem($id, $data) 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "UPDATE `$tableName` 
                SET title = ?, type = ?, barcode = ?, platform = ?, director = ?, publisher = ?, description = ?, cover_image = ?, metadata = ?
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
        
        $stmt = Database::query($sql, $params);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Delete item from collection
     */
    public static function deleteItem($id) 
    {
        // Require login
        Authentication::requireLogin();
        
        // Check if user can delete this item
        if (!self::canUserModifyItem($id)) {
            Authentication::requirePermission('manage_all_collections');
        }
        
        $tableName = Environment::getTableName('collection_items');
        $sql = "DELETE FROM `$tableName` WHERE id = ?";
        $stmt = Database::query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get collection statistics
     */
    public static function getStatistics() 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN type = 'game' THEN 1 ELSE 0 END) as games,
                    SUM(CASE WHEN type = 'film' THEN 1 ELSE 0 END) as films,
                    SUM(CASE WHEN type = 'serie' THEN 1 ELSE 0 END) as series
                FROM `$tableName`";
        
        $stmt = Database::query($sql);
        return $stmt->fetch();
    }
    
    /**
     * Get recently added items
     */
    public static function getRecentItems($limit = 5) 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$tableName` ORDER BY created_at DESC LIMIT ?";
        $stmt = Database::query($sql, [$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search items
     */
    public static function searchItems($query, $limit = 20, $userId = null) 
    {
        // If no userId specified, use current user or show all if admin
        if ($userId === null) {
            if (Authentication::hasPermission('view_all_collections')) {
                // Admin can see all collections
                $userId = null;
            } else {
                // Regular user can only see their own
                $userId = Authentication::getCurrentUserId();
            }
        }
        
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$tableName` WHERE 1=1";
        $params = [];
        
        // Filter by user if specified
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " AND (title LIKE ? OR director LIKE ? OR publisher LIKE ? OR description LIKE ?)
                ORDER BY 
                    CASE WHEN title LIKE ? THEN 1 ELSE 2 END,
                    created_at DESC 
                LIMIT ?";
        
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $exactTerm, $limit]);
        
        $stmt = Database::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if current user can modify item
     */
    public static function canUserModifyItem($itemId) 
    {
        $currentUserId = Authentication::getCurrentUserId();
        if (!$currentUserId) {
            return false;
        }
        
        // Admin can modify everything
        if (Authentication::hasPermission('manage_all_collections')) {
            return true;
        }
        
        // Check if item belongs to current user
        $item = self::getItemById($itemId);
        return $item && $item['user_id'] == $currentUserId;
    }

    /**
     * Maak een publieke deel-link aan voor de collectie van een gebruiker
     */
    public static function createShareLink($userId, $expiresAt) 
    {
        $tableName = Environment::getTableName('shared_links');
        $token = bin2hex(random_bytes(32));
        $sql = "INSERT INTO `$tableName` (user_id, token, expires_at) VALUES (?, ?, ?)";
        Database::query($sql, [$userId, $token, $expiresAt]);
        return $token;
    }

    /**
     * Haal alle actieve deel-links op voor een gebruiker
     */
    public static function getShareLinks($userId) 
    {
        $tableName = Environment::getTableName('shared_links');
        $sql = "SELECT * FROM `$tableName` WHERE user_id = ? AND (revoked_at IS NULL AND expires_at > NOW()) ORDER BY created_at DESC";
        $stmt = Database::query($sql, [$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Trek een deel-link in
     */
    public static function revokeShareLink($token, $userId) 
    {
        $tableName = Environment::getTableName('shared_links');
        $sql = "UPDATE `$tableName` SET revoked_at = NOW() WHERE token = ? AND user_id = ?";
        Database::query($sql, [$token, $userId]);
    }

    /**
     * Haal user_id op bij een geldige token
     */
    public static function getUserIdByToken($token) 
    {
        $tableName = Environment::getTableName('shared_links');
        $sql = "SELECT user_id FROM `$tableName` WHERE token = ? AND revoked_at IS NULL AND expires_at > NOW()";
        $stmt = Database::query($sql, [$token]);
        $row = $stmt->fetch();
        return $row ? $row['user_id'] : null;
    }

    /**
     * Controleer of een deel-link geldig is
     */
    public static function isShareLinkValid($token) 
    {
        $tableName = Environment::getTableName('shared_links');
        $sql = "SELECT COUNT(*) as cnt FROM `$tableName` WHERE token = ? AND revoked_at IS NULL AND expires_at > NOW()";
        $stmt = Database::query($sql, [$token]);
        $row = $stmt->fetch();
        return $row && $row['cnt'] > 0;
    }
} 