<?php
/**
 * OAuth Login Handler
 * Handles OAuth login and callback for Google and Facebook
 */

// Include dependencies
require_once '../includes/functions.php';

// Check if setup is needed
if (Database::needsSetup()) {
    header('Location: setup.php');
    exit;
}

$action = $_GET['action'] ?? '';
$provider = $_GET['provider'] ?? '';

try {
    
    switch ($action) {
        case 'login':
            handleOAuthLogin($provider);
            break;
            
        case 'callback':
            handleOAuthCallback($provider);
            break;
            
        default:
            Utils::errorResponse('Ongeldige OAuth actie');
    }
    
} catch (Exception $e) {
    error_log('OAuth Error: ' . $e->getMessage());
    
    // Redirect to login with error message
    $errorMessage = urlencode('OAuth inloggen mislukt: ' . $e->getMessage());
    header("Location: login.php?error=$errorMessage");
    exit;
}

/**
 * Handle OAuth login initiation
 */
function handleOAuthLogin($provider) {
    if (empty($provider)) {
        throw new Exception('Geen OAuth provider opgegeven');
    }
    
    if (!OAuthHelper::isEnabled($provider)) {
        throw new Exception("OAuth provider '$provider' is niet geconfigureerd");
    }
    
    // Get current domain for redirect URI
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $redirectUri = "$protocol://$host" . dirname($_SERVER['SCRIPT_NAME']) . "/oauth.php?action=callback&provider=$provider";
    
    // Get authorization URL
    $authUrl = OAuthHelper::getAuthorizationUrl($provider, $redirectUri);
    
    // Redirect to OAuth provider
    header("Location: $authUrl");
    exit;
}

/**
 * Handle OAuth callback
 */
function handleOAuthCallback($provider) {
    if (empty($provider)) {
        throw new Exception('Geen OAuth provider opgegeven');
    }
    
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $error = $_GET['error'] ?? '';
    
    // Check for OAuth error
    if (!empty($error)) {
        $errorDescription = $_GET['error_description'] ?? $error;
        throw new Exception("OAuth fout: $errorDescription");
    }
    
    if (empty($code)) {
        throw new Exception('Geen OAuth authorization code ontvangen');
    }
    
    if (empty($state)) {
        throw new Exception('Geen OAuth state parameter ontvangen');
    }
    
    // Handle the callback and get user data
    $providerData = OAuthHelper::handleCallback($provider, $code, $state);
    
    // Check if user is already logged in (linking account)
    if (Authentication::isLoggedIn()) {
        // Link social account to current user
        $currentUserId = Authentication::getCurrentUserId();
        OAuthHelper::linkSocialAccount($currentUserId, $provider, $providerData);
        
        // Redirect to profile with success message
        $message = urlencode("$provider account succesvol gekoppeld");
        header("Location: profile.php?success=$message");
        exit;
    }
    
    // Find or create user from social login
    $user = OAuthHelper::findOrCreateUserFromSocial($provider, $providerData);
    
    // Update user avatar if available and not set
    if (!empty($providerData['avatar']) && empty($user['avatar_url'])) {
        $usersTable = Environment::getTableName('users');
        $sql = "UPDATE `$usersTable` SET avatar_url = ? WHERE id = ?";
        Database::query($sql, [$providerData['avatar'], $user['id']]);
        $user['avatar_url'] = $providerData['avatar'];
    }
    
    // Log the user in
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = Authentication::userHasPermission($user['id'], 'system_admin');
    
    // Update last login
    $usersTable = Environment::getTableName('users');
    $sql = "UPDATE `$usersTable` SET last_login = NOW() WHERE id = ?";
    Database::query($sql, [$user['id']]);
    
    // Redirect to main app
    $isNewUser = !empty($_GET['new_user']);
    if ($isNewUser) {
        $message = urlencode('Welkom! Je account is succesvol aangemaakt via ' . ucfirst($provider));
        header("Location: index.php?welcome=$message");
    } else {
        header('Location: index.php');
    }
    exit;
}
?> 