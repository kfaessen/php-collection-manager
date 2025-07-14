<?php

/**
 * Push Notifications API Endpoint
 * Handles push notification subscriptions, unsubscriptions, and sending
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include dependencies
require_once '../includes/functions.php';

// Check if setup is needed
if (Database::needsSetup()) {
    Utils::jsonResponse(['success' => false, 'message' => 'Database setup required']);
    exit;
}

// Check if user is logged in for most actions
$requireAuth = !in_array($_GET['action'] ?? $_POST['action'] ?? '', ['get_vapid_key']);

if ($requireAuth && !Authentication::isLoggedIn()) {
    Utils::jsonResponse(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get current user ID
$userId = Authentication::getCurrentUserId();

// Handle different request methods
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    
    switch ($action) {
        case 'get_vapid_key':
            handleGetVapidKey();
            break;
            
        case 'subscribe':
            if ($method === 'POST') {
                handleSubscribe($userId);
            } else {
                Utils::jsonResponse(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'unsubscribe':
            if ($method === 'POST') {
                handleUnsubscribe($userId);
            } else {
                Utils::jsonResponse(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'test_notification':
            if ($method === 'POST') {
                handleTestNotification($userId);
            } else {
                Utils::jsonResponse(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'get_preferences':
            handleGetPreferences($userId);
            break;
            
        case 'update_preferences':
            if ($method === 'POST') {
                handleUpdatePreferences($userId);
            } else {
                Utils::jsonResponse(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'get_subscriptions':
            handleGetSubscriptions($userId);
            break;
            
        case 'send_notification':
            if ($method === 'POST' && Authentication::hasPermission('manage_users')) {
                handleSendNotification();
            } else {
                Utils::jsonResponse(['success' => false, 'message' => 'Admin permission required']);
            }
            break;
            
        case 'get_stats':
            if (Authentication::hasPermission('manage_users')) {
                handleGetStats($userId);
            } else {
                Utils::jsonResponse(['success' => false, 'message' => 'Admin permission required']);
            }
            break;
            
        default:
            Utils::jsonResponse(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log('Notification API Error: ' . $e->getMessage());
    Utils::jsonResponse(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Get VAPID public key
 */
function handleGetVapidKey() {
    $publicKey = \CollectionManager\NotificationHelper::getPublicKey();
    
    if ($publicKey) {
        Utils::jsonResponse([
            'success' => true,
            'publicKey' => $publicKey
        ]);
    } else {
        Utils::jsonResponse([
            'success' => false,
            'message' => 'Push notifications not configured'
        ]);
    }
}

/**
 * Handle push notification subscription
 */
function handleSubscribe($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['subscription'])) {
        Utils::jsonResponse(['success' => false, 'message' => 'Subscription data required']);
        return;
    }
    
    $subscription = $input['subscription'];
    $userAgent = $input['userAgent'] ?? null;
    
    // Validate subscription data
    if (empty($subscription['endpoint']) || 
        empty($subscription['keys']['p256dh']) || 
        empty($subscription['keys']['auth'])) {
        Utils::jsonResponse(['success' => false, 'message' => 'Invalid subscription data']);
        return;
    }
    
    $success = \CollectionManager\NotificationHelper::subscribe(
        $userId,
        $subscription['endpoint'],
        $subscription['keys'],
        $userAgent
    );
    
    if ($success) {
        // Create notification preferences if they don't exist
        createDefaultNotificationPreferences($userId);
        
        Utils::jsonResponse([
            'success' => true,
            'message' => I18nHelper::t('notification_subscribed', [], 'notifications')
        ]);
    } else {
        Utils::jsonResponse([
            'success' => false,
            'message' => 'Failed to subscribe to notifications'
        ]);
    }
}

/**
 * Handle push notification unsubscription
 */
function handleUnsubscribe($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $endpoint = $input['endpoint'] ?? null;
    
    $success = \CollectionManager\NotificationHelper::unsubscribe($userId, $endpoint);
    
    if ($success) {
        Utils::jsonResponse([
            'success' => true,
            'message' => I18nHelper::t('notification_unsubscribed', [], 'notifications')
        ]);
    } else {
        Utils::jsonResponse([
            'success' => false,
            'message' => 'Failed to unsubscribe from notifications'
        ]);
    }
}

/**
 * Handle test notification
 */
function handleTestNotification($userId) {
    if (!\CollectionManager\NotificationHelper::isAvailable()) {
        Utils::jsonResponse([
            'success' => false,
            'message' => 'Push notifications not available'
        ]);
        return;
    }
    
    $success = \CollectionManager\NotificationHelper::testNotification($userId);
    
    if ($success) {
        Utils::jsonResponse([
            'success' => true,
            'message' => 'Test notification sent'
        ]);
    } else {
        Utils::jsonResponse([
            'success' => false,
            'message' => 'Failed to send test notification'
        ]);
    }
}

/**
 * Get notification preferences
 */
function handleGetPreferences($userId) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM notification_preferences 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$preferences) {
            // Create default preferences
            $preferences = createDefaultNotificationPreferences($userId);
        }
        
        Utils::jsonResponse([
            'success' => true,
            'preferences' => $preferences
        ]);
        
    } catch (Exception $e) {
        error_log('Get preferences error: ' . $e->getMessage());
        Utils::jsonResponse([
            'success' => false,
            'message' => 'Failed to get preferences'
        ]);
    }
}

/**
 * Update notification preferences
 */
function handleUpdatePreferences($userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['preferences'])) {
        Utils::jsonResponse(['success' => false, 'message' => 'Preferences data required']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        $prefs = $input['preferences'];
        
        $stmt = $db->prepare("
            UPDATE notification_preferences SET
                item_added = ?,
                item_updated = ?,
                collection_shared = ?,
                reminders = ?,
                marketing = ?,
                quiet_hours_start = ?,
                quiet_hours_end = ?,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        
        $success = $stmt->execute([
            $prefs['item_added'] ?? 1,
            $prefs['item_updated'] ?? 1,
            $prefs['collection_shared'] ?? 1,
            $prefs['reminders'] ?? 1,
            $prefs['marketing'] ?? 0,
            $prefs['quiet_hours_start'] ?? '22:00:00',
            $prefs['quiet_hours_end'] ?? '08:00:00',
            $userId
        ]);
        
        if ($success) {
            Utils::jsonResponse([
                'success' => true,
                'message' => 'Preferences updated successfully'
            ]);
        } else {
            Utils::jsonResponse([
                'success' => false,
                'message' => 'Failed to update preferences'
            ]);
        }
        
    } catch (Exception $e) {
        error_log('Update preferences error: ' . $e->getMessage());
        Utils::jsonResponse([
            'success' => false,
            'message' => 'Failed to update preferences'
        ]);
    }
}

/**
 * Get user's push subscriptions
 */
function handleGetSubscriptions($userId) {
    $subscriptions = \CollectionManager\NotificationHelper::getUserSubscriptions($userId);
    
    // Remove sensitive data before sending to client
    $safeSubscriptions = array_map(function($sub) {
        return [
            'id' => $sub['id'],
            'user_agent' => $sub['user_agent'],
            'created_at' => $sub['created_at'],
            'is_active' => $sub['is_active']
        ];
    }, $subscriptions);
    
    Utils::jsonResponse([
        'success' => true,
        'subscriptions' => $safeSubscriptions
    ]);
}

/**
 * Send notification (admin only)
 */
function handleSendNotification() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['title', 'body'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            Utils::jsonResponse(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    $title = $input['title'];
    $body = $input['body'];
    $data = $input['data'] ?? [];
    $options = $input['options'] ?? [];
    $targetUsers = $input['users'] ?? null;
    
    if ($targetUsers) {
        // Send to specific users
        $success = \CollectionManager\NotificationHelper::sendToUsers($targetUsers, $title, $body, $data, $options);
    } else {
        // Send to all users
        $success = \CollectionManager\NotificationHelper::sendToAll($title, $body, $data, $options);
    }
    
    if ($success) {
        Utils::jsonResponse([
            'success' => true,
            'message' => 'Notifications sent successfully'
        ]);
    } else {
        Utils::jsonResponse([
            'success' => false,
            'message' => 'Failed to send notifications'
        ]);
    }
}

/**
 * Get notification statistics (admin only)
 */
function handleGetStats($userId = null) {
    $days = intval($_GET['days'] ?? 30);
    $userFilter = $_GET['user_id'] ?? $userId;
    
    $stats = \CollectionManager\NotificationHelper::getStats($userFilter, $days);
    
    Utils::jsonResponse([
        'success' => true,
        'stats' => $stats
    ]);
}

/**
 * Create default notification preferences for user
 */
function createDefaultNotificationPreferences($userId) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT IGNORE INTO notification_preferences 
            (user_id, item_added, item_updated, collection_shared, reminders, marketing)
            VALUES (?, 1, 1, 1, 1, 0)
        ");
        $stmt->execute([$userId]);
        
        // Return default preferences
        return [
            'user_id' => $userId,
            'item_added' => true,
            'item_updated' => true,
            'collection_shared' => true,
            'reminders' => true,
            'marketing' => false,
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '08:00:00'
        ];
        
    } catch (Exception $e) {
        error_log('Create default preferences error: ' . $e->getMessage());
        return null;
    }
} 