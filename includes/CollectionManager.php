<?php
namespace CollectionManager;

use CollectionManager\Database;
use CollectionManager\Environment;

class CollectionManager 
{
    /**
     * Get items from collection
     */
    public static function getItems($typeFilter = '', $search = '', $limit = 12, $offset = 0) 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$tableName` WHERE 1=1";
        $params = [];
        
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
    public static function countItems($typeFilter = '', $search = '') 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT COUNT(*) as total FROM `$tableName` WHERE 1=1";
        $params = [];
        
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
     * Get item by barcode
     */
    public static function getItemByBarcode($barcode) 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$tableName` WHERE barcode = ?";
        $stmt = Database::query($sql, [$barcode]);
        return $stmt->fetch();
    }
    
    /**
     * Add new item to collection
     */
    public static function addItem($data) 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "INSERT INTO `$tableName` (title, type, barcode, platform, director, publisher, description, cover_image, metadata) 
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
    public static function searchItems($query, $limit = 20) 
    {
        $tableName = Environment::getTableName('collection_items');
        $sql = "SELECT * FROM `$tableName` 
                WHERE title LIKE ? OR director LIKE ? OR publisher LIKE ? OR description LIKE ?
                ORDER BY 
                    CASE WHEN title LIKE ? THEN 1 ELSE 2 END,
                    created_at DESC 
                LIMIT ?";
        
        $searchTerm = "%$query%";
        $exactTerm = "$query%";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $exactTerm, $limit];
        
        $stmt = Database::query($sql, $params);
        return $stmt->fetchAll();
    }
} 