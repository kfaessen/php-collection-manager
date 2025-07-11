<?php
namespace CollectionManager;

use CollectionManager\Database;
use CollectionManager\Environment;
use CollectionManager\Authentication;

class UserManager 
{
    /**
     * Get all users with their groups
     */
    public static function getAllUsers($limit = 50, $offset = 0) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "SELECT u.*, 
                       GROUP_CONCAT(g.name) as groups,
                       COUNT(ci.id) as collection_count
                FROM `$usersTable` u
                LEFT JOIN " . Environment::getTableName('user_groups') . " ug ON u.id = ug.user_id
                LEFT JOIN " . Environment::getTableName('groups') . " g ON ug.group_id = g.id
                LEFT JOIN " . Environment::getTableName('collection_items') . " ci ON u.id = ci.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = Database::query($sql, [$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user by ID with groups
     */
    public static function getUserById($userId) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "SELECT u.*, GROUP_CONCAT(g.name) as groups
                FROM `$usersTable` u
                LEFT JOIN " . Environment::getTableName('user_groups') . " ug ON u.id = ug.user_id
                LEFT JOIN " . Environment::getTableName('groups') . " g ON ug.group_id = g.id
                WHERE u.id = ?
                GROUP BY u.id";
        
        $stmt = Database::query($sql, [$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Update user information
     */
    public static function updateUser($userId, $userData) 
    {
        $usersTable = Environment::getTableName('users');
        
        $updateFields = [];
        $params = [];
        
        // Allowed fields to update
        $allowedFields = ['username', 'email', 'first_name', 'last_name', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($userData[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $userData[$field];
            }
        }
        
        if (empty($updateFields)) {
            return ['success' => false, 'message' => 'Geen velden om bij te werken'];
        }
        
        $params[] = $userId;
        $sql = "UPDATE `$usersTable` SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        try {
            Database::query($sql, $params);
            return ['success' => true, 'message' => 'Gebruiker bijgewerkt'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij bijwerken: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete user
     */
    public static function deleteUser($userId) 
    {
        // Don't allow deleting the current user
        if ($userId == Authentication::getCurrentUserId()) {
            return ['success' => false, 'message' => 'U kunt uw eigen account niet verwijderen'];
        }
        
        $usersTable = Environment::getTableName('users');
        $sql = "DELETE FROM `$usersTable` WHERE id = ?";
        
        try {
            Database::query($sql, [$userId]);
            return ['success' => true, 'message' => 'Gebruiker verwijderd'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij verwijderen: ' . $e->getMessage()];
        }
    }
    
    /**
     * Change user password
     */
    public static function changePassword($userId, $newPassword) 
    {
        // Validate password strength
        $passwordValidation = Authentication::validatePassword($newPassword);
        if (!$passwordValidation['valid']) {
            return ['success' => false, 'message' => $passwordValidation['message']];
        }
        
        $usersTable = Environment::getTableName('users');
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE `$usersTable` SET password_hash = ? WHERE id = ?";
        
        try {
            Database::query($sql, [$passwordHash, $userId]);
            return ['success' => true, 'message' => 'Wachtwoord gewijzigd'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij wijzigen wachtwoord: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all groups
     */
    public static function getAllGroups() 
    {
        $groupsTable = Environment::getTableName('groups');
        $sql = "SELECT g.*, COUNT(ug.user_id) as user_count
                FROM `$groupsTable` g
                LEFT JOIN " . Environment::getTableName('user_groups') . " ug ON g.id = ug.group_id
                GROUP BY g.id
                ORDER BY g.name";
        
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get group by ID
     */
    public static function getGroupById($groupId) 
    {
        $groupsTable = Environment::getTableName('groups');
        $sql = "SELECT g.*, COUNT(ug.user_id) as user_count
                FROM `$groupsTable` g
                LEFT JOIN " . Environment::getTableName('user_groups') . " ug ON g.id = ug.group_id
                WHERE g.id = ?
                GROUP BY g.id";
        
        $stmt = Database::query($sql, [$groupId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all permissions
     */
    public static function getAllPermissions() 
    {
        $permissionsTable = Environment::getTableName('permissions');
        $sql = "SELECT * FROM `$permissionsTable` ORDER BY name";
        
        $stmt = Database::query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user's groups
     */
    public static function getUserGroups($userId) 
    {
        $sql = "SELECT g.* FROM " . Environment::getTableName('groups') . " g
                JOIN " . Environment::getTableName('user_groups') . " ug ON g.id = ug.group_id
                WHERE ug.user_id = ?
                ORDER BY g.name";
        
        $stmt = Database::query($sql, [$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get group's users
     */
    public static function getGroupUsers($groupId) 
    {
        $sql = "SELECT u.* FROM " . Environment::getTableName('users') . " u
                JOIN " . Environment::getTableName('user_groups') . " ug ON u.id = ug.user_id
                WHERE ug.group_id = ?
                ORDER BY u.first_name, u.last_name";
        
        $stmt = Database::query($sql, [$groupId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get group's permissions
     */
    public static function getGroupPermissions($groupId) 
    {
        $sql = "SELECT p.* FROM " . Environment::getTableName('permissions') . " p
                JOIN " . Environment::getTableName('group_permissions') . " gp ON p.id = gp.permission_id
                WHERE gp.group_id = ?
                ORDER BY p.name";
        
        $stmt = Database::query($sql, [$groupId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add user to group
     */
    public static function addUserToGroup($userId, $groupId) 
    {
        $userGroupsTable = Environment::getTableName('user_groups');
        $sql = "INSERT IGNORE INTO `$userGroupsTable` (user_id, group_id) VALUES (?, ?)";
        
        try {
            Database::query($sql, [$userId, $groupId]);
            return ['success' => true, 'message' => 'Gebruiker toegevoegd aan groep'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij toevoegen aan groep: ' . $e->getMessage()];
        }
    }
    
    /**
     * Remove user from group
     */
    public static function removeUserFromGroup($userId, $groupId) 
    {
        $userGroupsTable = Environment::getTableName('user_groups');
        $sql = "DELETE FROM `$userGroupsTable` WHERE user_id = ? AND group_id = ?";
        
        try {
            Database::query($sql, [$userId, $groupId]);
            return ['success' => true, 'message' => 'Gebruiker verwijderd uit groep'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij verwijderen uit groep: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create new group
     */
    public static function createGroup($name, $description = '') 
    {
        $groupsTable = Environment::getTableName('groups');
        $sql = "INSERT INTO `$groupsTable` (name, description) VALUES (?, ?)";
        
        try {
            Database::query($sql, [$name, $description]);
            $groupId = Database::lastInsertId();
            return ['success' => true, 'message' => 'Groep aangemaakt', 'group_id' => $groupId];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij aanmaken groep: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update group
     */
    public static function updateGroup($groupId, $name, $description = '') 
    {
        $groupsTable = Environment::getTableName('groups');
        $sql = "UPDATE `$groupsTable` SET name = ?, description = ? WHERE id = ?";
        
        try {
            Database::query($sql, [$name, $description, $groupId]);
            return ['success' => true, 'message' => 'Groep bijgewerkt'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij bijwerken groep: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete group
     */
    public static function deleteGroup($groupId) 
    {
        // Don't allow deleting admin, user or moderator groups
        $groupsTable = Environment::getTableName('groups');
        $sql = "SELECT name FROM `$groupsTable` WHERE id = ?";
        $stmt = Database::query($sql, [$groupId]);
        $group = $stmt->fetch();
        
        if ($group && in_array($group['name'], ['admin', 'user', 'moderator'])) {
            return ['success' => false, 'message' => 'Standaard groepen kunnen niet verwijderd worden'];
        }
        
        $sql = "DELETE FROM `$groupsTable` WHERE id = ?";
        
        try {
            Database::query($sql, [$groupId]);
            return ['success' => true, 'message' => 'Groep verwijderd'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij verwijderen groep: ' . $e->getMessage()];
        }
    }
    
    /**
     * Add permission to group
     */
    public static function addPermissionToGroup($groupId, $permissionId) 
    {
        $groupPermissionsTable = Environment::getTableName('group_permissions');
        $sql = "INSERT IGNORE INTO `$groupPermissionsTable` (group_id, permission_id) VALUES (?, ?)";
        
        try {
            Database::query($sql, [$groupId, $permissionId]);
            return ['success' => true, 'message' => 'Recht toegevoegd aan groep'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij toevoegen recht: ' . $e->getMessage()];
        }
    }
    
    /**
     * Remove permission from group
     */
    public static function removePermissionFromGroup($groupId, $permissionId) 
    {
        $groupPermissionsTable = Environment::getTableName('group_permissions');
        $sql = "DELETE FROM `$groupPermissionsTable` WHERE group_id = ? AND permission_id = ?";
        
        try {
            Database::query($sql, [$groupId, $permissionId]);
            return ['success' => true, 'message' => 'Recht verwijderd uit groep'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij verwijderen recht: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user statistics
     */
    public static function getUserStats() 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
                    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_last_month,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_last_month
                FROM `$usersTable`";
        
        $stmt = Database::query($sql);
        return $stmt->fetch();
    }
} 