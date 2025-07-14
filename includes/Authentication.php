<?php
namespace CollectionManager;

use CollectionManager\Database;
use CollectionManager\Environment;
use CollectionManager\Utils;

class Authentication 
{
    private static $currentUser = null;
    private static $sessionStarted = false;
    
    /**
     * Initialize authentication system
     */
    public static function init() 
    {
        if (!self::$sessionStarted) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            self::$sessionStarted = true;
        }
        
        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            self::$currentUser = self::getUserById($_SESSION['user_id']);
        }
    }
    
    /**
     * Login user with username/email and password
     */
    public static function login($username, $password, $totpCode = null) 
    {
        // Find user by username or email
        $user = self::findUserByUsernameOrEmail($username);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Gebruiker niet gevonden'];
        }
        
        // Check if account is locked
        if ($user['locked_until'] && new \DateTime() < new \DateTime($user['locked_until'])) {
            return ['success' => false, 'message' => 'Account is tijdelijk vergrendeld vanwege te veel mislukte inlogpogingen'];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is gedeactiveerd'];
        }
        
        // Check if email is verified (only for local accounts, not OAuth)
        if ($user['registration_method'] === 'local' && !$user['email_verified']) {
            return [
                'success' => false, 
                'message' => 'Je email adres is nog niet geverifieerd. Check je inbox voor de verificatie email.',
                'requires_verification' => true,
                'user_id' => $user['id'],
                'email' => $user['email']
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Increment failed login attempts
            self::incrementFailedLoginAttempts($user['id']);
            return ['success' => false, 'message' => 'Onjuist wachtwoord'];
        }
        
        // Check if TOTP is enabled
        if ($user['totp_enabled']) {
            if (empty($totpCode)) {
                return ['success' => false, 'message' => 'Twee-factor authenticatie code vereist', 'requires_totp' => true];
            }
            
            // Verify TOTP code
            if (!TOTPHelper::verifyCode($user['totp_secret'], $totpCode)) {
                // Check if it's a backup code
                $newBackupCodes = TOTPHelper::verifyBackupCode($user['totp_backup_codes'], $totpCode);
                if ($newBackupCodes === false) {
                    // Increment failed login attempts
                    self::incrementFailedLoginAttempts($user['id']);
                    return ['success' => false, 'message' => 'Ongeldige twee-factor authenticatie code'];
                } else {
                    // Update backup codes
                    self::updateBackupCodes($user['id'], $newBackupCodes);
                }
            }
        }
        
        // Reset failed login attempts
        self::resetFailedLoginAttempts($user['id']);
        
        // Update last login
        self::updateLastLogin($user['id']);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = self::userHasPermission($user['id'], 'system_admin');
        
        self::$currentUser = $user;
        
        return ['success' => true, 'message' => 'Succesvol ingelogd', 'user' => $user];
    }
    
    /**
     * Logout current user
     */
    public static function logout() 
    {
        session_destroy();
        self::$currentUser = null;
        self::$sessionStarted = false;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() 
    {
        return self::$currentUser !== null;
    }
    
    /**
     * Get current user
     */
    public static function getCurrentUser() 
    {
        return self::$currentUser;
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() 
    {
        return self::$currentUser ? self::$currentUser['id'] : null;
    }
    
    /**
     * Register new user
     */
    public static function register($userData) 
    {
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                return ['success' => false, 'message' => "Veld '$field' is verplicht"];
            }
        }
        
        // Validate password strength
        $passwordValidation = self::validatePassword($userData['password']);
        if (!$passwordValidation['valid']) {
            return ['success' => false, 'message' => $passwordValidation['message']];
        }
        
        // Check if username already exists
        if (self::usernameExists($userData['username'])) {
            return ['success' => false, 'message' => 'Gebruikersnaam is al in gebruik'];
        }
        
        // Check if email already exists
        if (self::emailExists($userData['email'])) {
            return ['success' => false, 'message' => 'Email is al in gebruik'];
        }
        
        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $usersTable = Environment::getTableName('users');
        $sql = "INSERT INTO `$usersTable` (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)";
        $params = [
            $userData['username'],
            $userData['email'],
            $passwordHash,
            $userData['first_name'],
            $userData['last_name']
        ];
        
        try {
            Database::query($sql, $params);
            $userId = Database::lastInsertId();
            
            // Add user to default 'user' group
            self::addUserToGroup($userId, 'user');
            
            // Send email verification (only for regular registration, not setup or OAuth)
            $requiresVerification = !isset($userData['skip_verification']) || !$userData['skip_verification'];
            if ($requiresVerification && class_exists('\CollectionManager\EmailVerificationHelper')) {
                $verificationResult = \CollectionManager\EmailVerificationHelper::sendVerificationEmail($userId, $userData['email']);
                
                if ($verificationResult['success']) {
                    return [
                        'success' => true, 
                        'message' => 'Gebruiker succesvol aangemaakt. Er is een verificatie email verstuurd naar ' . $userData['email'],
                        'user_id' => $userId,
                        'requires_verification' => true
                    ];
                } else {
                    // Log verification email failure but don't fail registration
                    error_log('Failed to send verification email for user ' . $userId . ': ' . $verificationResult['message']);
                    return [
                        'success' => true, 
                        'message' => 'Gebruiker succesvol aangemaakt, maar verificatie email kon niet worden verzonden. Neem contact op met beheerder.',
                        'user_id' => $userId,
                        'verification_email_failed' => true
                    ];
                }
            }
            
            return ['success' => true, 'message' => 'Gebruiker succesvol aangemaakt', 'user_id' => $userId];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij aanmaken gebruiker: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) 
    {
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Wachtwoord moet minimaal 8 tekens bevatten'];
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => 'Wachtwoord moet minimaal 1 hoofdletter bevatten'];
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'message' => 'Wachtwoord moet minimaal 1 kleine letter bevatten'];
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Wachtwoord moet minimaal 1 cijfer bevatten'];
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Wachtwoord moet minimaal 1 speciaal teken bevatten'];
        }
        
        return ['valid' => true, 'message' => 'Wachtwoord voldoet aan de eisen'];
    }
    
    /**
     * Check if user has permission
     */
    public static function userHasPermission($userId, $permissionName) 
    {
        $sql = "SELECT COUNT(*) as count FROM " . Environment::getTableName('user_groups') . " ug
                JOIN " . Environment::getTableName('group_permissions') . " gp ON ug.group_id = gp.group_id
                JOIN " . Environment::getTableName('permissions') . " p ON gp.permission_id = p.id
                WHERE ug.user_id = ? AND p.name = ?";
        
        $stmt = Database::query($sql, [$userId, $permissionName]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Check if current user has permission
     */
    public static function hasPermission($permissionName) 
    {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        return self::userHasPermission(self::getCurrentUserId(), $permissionName);
    }
    
    /**
     * Require permission (redirect if not authorized)
     */
    public static function requirePermission($permissionName) 
    {
        if (!self::hasPermission($permissionName)) {
            Utils::errorResponse('Onvoldoende rechten voor deze actie');
        }
    }
    
    /**
     * Require login (redirect if not logged in)
     */
    public static function requireLogin() 
    {
        if (!self::isLoggedIn()) {
            Utils::errorResponse('U moet ingelogd zijn om deze actie uit te voeren');
        }
    }
    
    /**
     * Private helper methods
     */
    private static function findUserByUsernameOrEmail($username) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "SELECT u.*, COALESCE(GROUP_CONCAT(g.name), '') as user_groups
                FROM `$usersTable` u
                LEFT JOIN " . Environment::getTableName('user_groups') . " ug ON u.id = ug.user_id
                LEFT JOIN " . Environment::getTableName('groups') . " g ON ug.group_id = g.id
                WHERE u.username = ? OR u.email = ?
                GROUP BY u.id";
        $stmt = Database::query($sql, [$username, $username]);
        return $stmt->fetch();
    }
    
    private static function getUserById($userId) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "SELECT u.*, COALESCE(GROUP_CONCAT(g.name), '') as user_groups
                FROM `$usersTable` u
                LEFT JOIN " . Environment::getTableName('user_groups') . " ug ON u.id = ug.user_id
                LEFT JOIN " . Environment::getTableName('groups') . " g ON ug.group_id = g.id
                WHERE u.id = ?
                GROUP BY u.id";
        $stmt = Database::query($sql, [$userId]);
        return $stmt->fetch();
    }
    
    private static function usernameExists($username) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "SELECT COUNT(*) as count FROM `$usersTable` WHERE username = ?";
        $stmt = Database::query($sql, [$username]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    private static function emailExists($email) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "SELECT COUNT(*) as count FROM `$usersTable` WHERE email = ?";
        $stmt = Database::query($sql, [$email]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    private static function incrementFailedLoginAttempts($userId) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET failed_login_attempts = failed_login_attempts + 1";
        
        // Lock account after 5 failed attempts for 30 minutes
        $sql .= ", locked_until = CASE WHEN failed_login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE) ELSE locked_until END";
        $sql .= " WHERE id = ?";
        
        Database::query($sql, [$userId]);
    }
    
    private static function resetFailedLoginAttempts($userId) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?";
        Database::query($sql, [$userId]);
    }
    
    private static function updateLastLogin($userId) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET last_login = NOW() WHERE id = ?";
        Database::query($sql, [$userId]);
    }
    
    private static function addUserToGroup($userId, $groupName) 
    {
        $groupsTable = Environment::getTableName('groups');
        $userGroupsTable = Environment::getTableName('user_groups');
        
        $sql = "INSERT INTO `$userGroupsTable` (user_id, group_id) 
                SELECT ?, g.id FROM `$groupsTable` g WHERE g.name = ?";
        
        Database::query($sql, [$userId, $groupName]);
    }
    
    /**
     * Enable TOTP for user
     */
    public static function enableTOTP($userId) 
    {
        $secret = TOTPHelper::generateSecret();
        $backupCodes = TOTPHelper::generateBackupCodes();
        
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET totp_secret = ?, totp_backup_codes = ? WHERE id = ?";
        
        try {
            Database::query($sql, [$secret, json_encode($backupCodes), $userId]);
            return [
                'success' => true, 
                'secret' => $secret, 
                'backup_codes' => $backupCodes,
                'qr_url' => TOTPHelper::generateQRUrl($secret, self::getCurrentUser()['username'])
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij inschakelen TOTP: ' . $e->getMessage()];
        }
    }
    
    /**
     * Verify and enable TOTP
     */
    public static function verifyAndEnableTOTP($userId, $code) 
    {
        $user = self::getUserById($userId);
        if (!$user || !$user['totp_secret']) {
            return ['success' => false, 'message' => 'TOTP niet geconfigureerd'];
        }
        
        if (!TOTPHelper::verifyCode($user['totp_secret'], $code)) {
            return ['success' => false, 'message' => 'Ongeldige TOTP code'];
        }
        
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET totp_enabled = 1 WHERE id = ?";
        
        try {
            Database::query($sql, [$userId]);
            return ['success' => true, 'message' => 'TOTP succesvol ingeschakeld'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij inschakelen TOTP: ' . $e->getMessage()];
        }
    }
    
    /**
     * Disable TOTP for user
     */
    public static function disableTOTP($userId) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET totp_enabled = 0, totp_secret = NULL, totp_backup_codes = NULL WHERE id = ?";
        
        try {
            Database::query($sql, [$userId]);
            return ['success' => true, 'message' => 'TOTP succesvol uitgeschakeld'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij uitschakelen TOTP: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate new backup codes
     */
    public static function generateNewBackupCodes($userId) 
    {
        $backupCodes = TOTPHelper::generateBackupCodes();
        
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET totp_backup_codes = ? WHERE id = ?";
        
        try {
            Database::query($sql, [json_encode($backupCodes), $userId]);
            return ['success' => true, 'backup_codes' => $backupCodes];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fout bij genereren backup codes: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update backup codes
     */
    private static function updateBackupCodes($userId, $newBackupCodes) 
    {
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET totp_backup_codes = ? WHERE id = ?";
        Database::query($sql, [$newBackupCodes, $userId]);
    }
} 