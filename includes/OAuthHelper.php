<?php
namespace CollectionManager;

use CollectionManager\Environment;
use CollectionManager\Database;
use CollectionManager\Utils;

class OAuthHelper 
{
    /**
     * Check if OAuth is enabled and configured
     */
    public static function isEnabled($provider = null) 
    {
        if (!Environment::get('OAUTH_ENABLED', true)) {
            return false;
        }
        
        if ($provider) {
            switch (strtolower($provider)) {
                case 'google':
                    return !empty(Environment::get('GOOGLE_CLIENT_ID')) && !empty(Environment::get('GOOGLE_CLIENT_SECRET'));
                case 'facebook':
                    return !empty(Environment::get('FACEBOOK_APP_ID')) && !empty(Environment::get('FACEBOOK_APP_SECRET'));
                default:
                    return false;
            }
        }
        
        // Check if any provider is configured
        return self::isEnabled('google') || self::isEnabled('facebook');
    }
    
    /**
     * Get OAuth authorization URL
     */
    public static function getAuthorizationUrl($provider, $redirectUri = null) 
    {
        if (!self::isEnabled($provider)) {
            throw new \Exception("OAuth provider '$provider' niet geconfigureerd");
        }
        
        $state = self::generateState($provider);
        
        switch (strtolower($provider)) {
            case 'google':
                return self::getGoogleAuthUrl($state, $redirectUri);
            case 'facebook':
                return self::getFacebookAuthUrl($state, $redirectUri);
            default:
                throw new \Exception("Onbekende OAuth provider: $provider");
        }
    }
    
    /**
     * Handle OAuth callback
     */
    public static function handleCallback($provider, $code, $state) 
    {
        if (!self::validateState($provider, $state)) {
            throw new \Exception('Ongeldige OAuth state parameter');
        }
        
        try {
            switch (strtolower($provider)) {
                case 'google':
                    return self::handleGoogleCallback($code);
                case 'facebook':
                    return self::handleFacebookCallback($code);
                default:
                    throw new \Exception("Onbekende OAuth provider: $provider");
            }
        } finally {
            // Clean up state
            self::cleanupState($state);
        }
    }
    
    /**
     * Link social account to existing user
     */
    public static function linkSocialAccount($userId, $provider, $providerData) 
    {
        $socialLoginsTable = Environment::getTableName('social_logins');
        
        // Check if this social account is already linked to another user
        $sql = "SELECT user_id FROM `$socialLoginsTable` WHERE provider = ? AND provider_id = ?";
        $stmt = Database::query($sql, [$provider, $providerData['id']]);
        $existing = $stmt->fetch();
        
        if ($existing && $existing['user_id'] != $userId) {
            throw new \Exception('Dit sociale account is al gekoppeld aan een andere gebruiker');
        }
        
        // Insert or update social login record
        $sql = "INSERT INTO `$socialLoginsTable` 
                (user_id, provider, provider_id, provider_email, provider_name, provider_avatar, access_token, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                provider_email = VALUES(provider_email),
                provider_name = VALUES(provider_name),
                provider_avatar = VALUES(provider_avatar),
                access_token = VALUES(access_token),
                expires_at = VALUES(expires_at),
                updated_at = CURRENT_TIMESTAMP";
        
        $expiresAt = isset($providerData['expires_at']) ? date('Y-m-d H:i:s', $providerData['expires_at']) : null;
        
        Database::query($sql, [
            $userId,
            $provider,
            $providerData['id'],
            $providerData['email'] ?? '',
            $providerData['name'] ?? '',
            $providerData['avatar'] ?? '',
            $providerData['access_token'] ?? '',
            $expiresAt
        ]);
        
        return true;
    }
    
    /**
     * Get user's social accounts
     */
    public static function getUserSocialAccounts($userId) 
    {
        $socialLoginsTable = Environment::getTableName('social_logins');
        $sql = "SELECT provider, provider_email, provider_name, provider_avatar, created_at 
                FROM `$socialLoginsTable` WHERE user_id = ? ORDER BY created_at DESC";
        
        $stmt = Database::query($sql, [$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Unlink social account
     */
    public static function unlinkSocialAccount($userId, $provider) 
    {
        $socialLoginsTable = Environment::getTableName('social_logins');
        $sql = "DELETE FROM `$socialLoginsTable` WHERE user_id = ? AND provider = ?";
        
        Database::query($sql, [$userId, $provider]);
        return true;
    }
    
    /**
     * Find or create user from social login
     */
    public static function findOrCreateUserFromSocial($provider, $providerData) 
    {
        $socialLoginsTable = Environment::getTableName('social_logins');
        $usersTable = Environment::getTableName('users');
        
        // First, check if this social account already exists
        $sql = "SELECT sl.user_id, u.* FROM `$socialLoginsTable` sl 
                JOIN `$usersTable` u ON sl.user_id = u.id 
                WHERE sl.provider = ? AND sl.provider_id = ?";
        $stmt = Database::query($sql, [$provider, $providerData['id']]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Update social login info
            self::linkSocialAccount($existingUser['id'], $provider, $providerData);
            return $existingUser;
        }
        
        // Check if user exists by email
        if (!empty($providerData['email'])) {
            $sql = "SELECT * FROM `$usersTable` WHERE email = ?";
            $stmt = Database::query($sql, [$providerData['email']]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Link this social account to existing user
                self::linkSocialAccount($existingUser['id'], $provider, $providerData);
                return $existingUser;
            }
        }
        
        // Create new user
        $username = self::generateUniqueUsername($providerData['name'] ?? $providerData['email'] ?? 'user');
        $userData = [
            'username' => $username,
            'email' => $providerData['email'] ?? '',
            'password' => '', // No password for social users
            'first_name' => $providerData['given_name'] ?? $providerData['first_name'] ?? '',
            'last_name' => $providerData['family_name'] ?? $providerData['last_name'] ?? '',
            'avatar_url' => $providerData['avatar'] ?? '',
            'email_verified' => true, // Social logins are considered verified
            'registration_method' => $provider
        ];
        
        // Generate a random password (user won't use it, but database requires it)
        $randomPassword = bin2hex(random_bytes(16));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO `$usersTable` 
                (username, email, password_hash, first_name, last_name, avatar_url, email_verified, email_verified_at, registration_method, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1)";
        
        Database::query($sql, [
            $userData['username'],
            $userData['email'],
            $passwordHash,
            $userData['first_name'],
            $userData['last_name'],
            $userData['avatar_url'],
            $userData['email_verified'],
            $userData['registration_method']
        ]);
        
        $userId = Database::lastInsertId();
        
        // Add user to default 'user' group
        self::addUserToDefaultGroup($userId);
        
        // Link social account
        self::linkSocialAccount($userId, $provider, $providerData);
        
        // Get the created user
        $sql = "SELECT * FROM `$usersTable` WHERE id = ?";
        $stmt = Database::query($sql, [$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Generate unique username
     */
    private static function generateUniqueUsername($baseName) 
    {
        $usersTable = Environment::getTableName('users');
        $baseName = Utils::sanitize($baseName);
        $baseName = preg_replace('/[^a-zA-Z0-9_]/', '', $baseName);
        $baseName = substr($baseName, 0, 20);
        
        if (empty($baseName)) {
            $baseName = 'user';
        }
        
        $username = $baseName;
        $counter = 1;
        
        while (true) {
            $sql = "SELECT COUNT(*) as count FROM `$usersTable` WHERE username = ?";
            $stmt = Database::query($sql, [$username]);
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                return $username;
            }
            
            $username = $baseName . $counter;
            $counter++;
        }
    }
    
    /**
     * Add user to default group
     */
    private static function addUserToDefaultGroup($userId) 
    {
        $groupsTable = Environment::getTableName('groups');
        $userGroupsTable = Environment::getTableName('user_groups');
        
        $sql = "INSERT INTO `$userGroupsTable` (user_id, group_id) 
                SELECT ?, g.id FROM `$groupsTable` g WHERE g.name = 'user' LIMIT 1";
        
        Database::query($sql, [$userId]);
    }
    
    /**
     * Generate OAuth state parameter
     */
    private static function generateState($provider) 
    {
        $state = bin2hex(random_bytes(32));
        $oauthStatesTable = Environment::getTableName('oauth_states');
        
        $expiresAt = date('Y-m-d H:i:s', time() + Environment::get('OAUTH_STATE_LIFETIME', 600));
        
        $sql = "INSERT INTO `$oauthStatesTable` (id, provider, expires_at) VALUES (?, ?, ?)";
        Database::query($sql, [$state, $provider, $expiresAt]);
        
        return $state;
    }
    
    /**
     * Validate OAuth state parameter
     */
    private static function validateState($provider, $state) 
    {
        $oauthStatesTable = Environment::getTableName('oauth_states');
        
        $sql = "SELECT * FROM `$oauthStatesTable` WHERE id = ? AND provider = ? AND expires_at > NOW()";
        $stmt = Database::query($sql, [$state, $provider]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Clean up expired state tokens
     */
    private static function cleanupState($state) 
    {
        $oauthStatesTable = Environment::getTableName('oauth_states');
        
        // Delete this specific state
        $sql = "DELETE FROM `$oauthStatesTable` WHERE id = ?";
        Database::query($sql, [$state]);
        
        // Clean up expired states
        $sql = "DELETE FROM `$oauthStatesTable` WHERE expires_at < NOW()";
        Database::query($sql);
    }
    
    /**
     * Get Google OAuth authorization URL
     */
    private static function getGoogleAuthUrl($state, $redirectUri = null) 
    {
        $clientId = Environment::get('GOOGLE_CLIENT_ID');
        $redirectUri = $redirectUri ?? Environment::get('GOOGLE_REDIRECT_URI');
        
        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'openid email profile',
            'response_type' => 'code',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Get Facebook OAuth authorization URL
     */
    private static function getFacebookAuthUrl($state, $redirectUri = null) 
    {
        $appId = Environment::get('FACEBOOK_APP_ID');
        $redirectUri = $redirectUri ?? Environment::get('FACEBOOK_REDIRECT_URI');
        
        $params = [
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => 'email,public_profile',
            'response_type' => 'code',
            'state' => $state
        ];
        
        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }
    
    /**
     * Handle Google OAuth callback
     */
    private static function handleGoogleCallback($code) 
    {
        // Exchange code for access token
        $tokenData = self::exchangeGoogleCodeForToken($code);
        
        // Get user info from Google
        $userInfo = self::getGoogleUserInfo($tokenData['access_token']);
        
        return [
            'id' => $userInfo['sub'],
            'email' => $userInfo['email'] ?? '',
            'name' => $userInfo['name'] ?? '',
            'given_name' => $userInfo['given_name'] ?? '',
            'family_name' => $userInfo['family_name'] ?? '',
            'avatar' => $userInfo['picture'] ?? '',
            'access_token' => $tokenData['access_token'],
            'expires_at' => isset($tokenData['expires_in']) ? time() + $tokenData['expires_in'] : null
        ];
    }
    
    /**
     * Handle Facebook OAuth callback
     */
    private static function handleFacebookCallback($code) 
    {
        // Exchange code for access token
        $tokenData = self::exchangeFacebookCodeForToken($code);
        
        // Get user info from Facebook
        $userInfo = self::getFacebookUserInfo($tokenData['access_token']);
        
        return [
            'id' => $userInfo['id'],
            'email' => $userInfo['email'] ?? '',
            'name' => $userInfo['name'] ?? '',
            'first_name' => $userInfo['first_name'] ?? '',
            'last_name' => $userInfo['last_name'] ?? '',
            'avatar' => $userInfo['picture']['data']['url'] ?? '',
            'access_token' => $tokenData['access_token'],
            'expires_at' => isset($tokenData['expires_in']) ? time() + $tokenData['expires_in'] : null
        ];
    }
    
    /**
     * Exchange Google authorization code for access token
     */
    private static function exchangeGoogleCodeForToken($code) 
    {
        $url = 'https://oauth2.googleapis.com/token';
        $data = [
            'client_id' => Environment::get('GOOGLE_CLIENT_ID'),
            'client_secret' => Environment::get('GOOGLE_CLIENT_SECRET'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => Environment::get('GOOGLE_REDIRECT_URI')
        ];
        
        $response = self::makeHttpRequest($url, 'POST', $data);
        $tokenData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($tokenData['access_token'])) {
            throw new \Exception('Fout bij ophalen Google access token');
        }
        
        return $tokenData;
    }
    
    /**
     * Exchange Facebook authorization code for access token
     */
    private static function exchangeFacebookCodeForToken($code) 
    {
        $url = 'https://graph.facebook.com/v18.0/oauth/access_token';
        $params = [
            'client_id' => Environment::get('FACEBOOK_APP_ID'),
            'client_secret' => Environment::get('FACEBOOK_APP_SECRET'),
            'code' => $code,
            'redirect_uri' => Environment::get('FACEBOOK_REDIRECT_URI')
        ];
        
        $response = self::makeHttpRequest($url . '?' . http_build_query($params), 'GET');
        $tokenData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($tokenData['access_token'])) {
            throw new \Exception('Fout bij ophalen Facebook access token');
        }
        
        return $tokenData;
    }
    
    /**
     * Get Google user information
     */
    private static function getGoogleUserInfo($accessToken) 
    {
        $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $headers = ['Authorization: Bearer ' . $accessToken];
        
        $response = self::makeHttpRequest($url, 'GET', null, $headers);
        $userInfo = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Fout bij ophalen Google gebruikersinfo');
        }
        
        return $userInfo;
    }
    
    /**
     * Get Facebook user information
     */
    private static function getFacebookUserInfo($accessToken) 
    {
        $fields = 'id,name,email,first_name,last_name,picture.type(large)';
        $url = "https://graph.facebook.com/v18.0/me?fields=$fields&access_token=" . urlencode($accessToken);
        
        $response = self::makeHttpRequest($url, 'GET');
        $userInfo = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Fout bij ophalen Facebook gebruikersinfo');
        }
        
        return $userInfo;
    }
    
    /**
     * Make HTTP request
     */
    private static function makeHttpRequest($url, $method = 'GET', $data = null, $headers = []) 
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => Environment::get('API_TIMEOUT', 30),
            CURLOPT_USERAGENT => 'CollectionManager/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
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
            throw new \Exception('HTTP request fout: ' . $error);
        }
        
        if ($httpCode >= 400) {
            throw new \Exception("HTTP fout $httpCode: $response");
        }
        
        return $response;
    }
} 