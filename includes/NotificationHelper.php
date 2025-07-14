<?php
namespace CollectionManager;

/**
 * NotificationHelper - Web Push Notifications Management
 * Handles browser push notifications, subscription management, and message sending
 */

class NotificationHelper {
    
    private static $vapidKeys = null;
    private static $webPushAuth = null;
    
    /**
     * Initialize VAPID keys for web push
     */
    public static function init() {
        // Load VAPID keys from environment
        self::$vapidKeys = [
            'subject' => Environment::get('VAPID_SUBJECT', 'mailto:admin@collectiebeheer.app'),
            'publicKey' => Environment::get('VAPID_PUBLIC_KEY'),
            'privateKey' => Environment::get('VAPID_PRIVATE_KEY')
        ];
        
        // Initialize WebPush library if available
        if (class_exists('Minishlink\WebPush\WebPush')) {
            self::$webPushAuth = [
                'VAPID' => self::$vapidKeys
            ];
        }
    }
    
    /**
     * Check if web push notifications are available
     */
    public static function isAvailable() {
        return class_exists('Minishlink\WebPush\WebPush') && 
               !empty(self::$vapidKeys['publicKey']) && 
               !empty(self::$vapidKeys['privateKey']);
    }
    
    /**
     * Get VAPID public key for client-side subscription
     */
    public static function getPublicKey() {
        self::init();
        return self::$vapidKeys['publicKey'] ?? null;
    }
    
    /**
     * Subscribe user to push notifications
     */
    public static function subscribe($userId, $endpoint, $keys, $userAgent = null) {
        try {
            $db = Database::getConnection();
            
            // Validate subscription data
            if (empty($endpoint) || empty($keys['p256dh']) || empty($keys['auth'])) {
                throw new Exception('Invalid subscription data');
            }
            
            // Check if subscription already exists
            $stmt = $db->prepare("
                SELECT id FROM push_subscriptions 
                WHERE user_id = ? AND endpoint = ?
            ");
            $stmt->execute([$userId, $endpoint]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing subscription
                $stmt = $db->prepare("
                    UPDATE push_subscriptions 
                    SET p256dh_key = ?, auth_key = ?, user_agent = ?, updated_at = NOW()
                    WHERE user_id = ? AND endpoint = ?
                ");
                $stmt->execute([
                    $keys['p256dh'],
                    $keys['auth'],
                    $userAgent,
                    $userId,
                    $endpoint
                ]);
            } else {
                // Create new subscription
                $stmt = $db->prepare("
                    INSERT INTO push_subscriptions 
                    (user_id, endpoint, p256dh_key, auth_key, user_agent, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $userId,
                    $endpoint,
                    $keys['p256dh'],
                    $keys['auth'],
                    $userAgent
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Push subscription error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Unsubscribe user from push notifications
     */
    public static function unsubscribe($userId, $endpoint = null) {
        try {
            $db = Database::getConnection();
            
            if ($endpoint) {
                // Remove specific subscription
                $stmt = $db->prepare("
                    DELETE FROM push_subscriptions 
                    WHERE user_id = ? AND endpoint = ?
                ");
                $stmt->execute([$userId, $endpoint]);
            } else {
                // Remove all subscriptions for user
                $stmt = $db->prepare("
                    DELETE FROM push_subscriptions 
                    WHERE user_id = ?
                ");
                $stmt->execute([$userId]);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Push unsubscribe error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's push subscriptions
     */
    public static function getUserSubscriptions($userId) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT * FROM push_subscriptions 
                WHERE user_id = ? AND is_active = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Get subscriptions error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send push notification to specific user
     */
    public static function sendToUser($userId, $title, $body, $data = [], $options = []) {
        $subscriptions = self::getUserSubscriptions($userId);
        
        if (empty($subscriptions)) {
            return false;
        }
        
        $success = true;
        foreach ($subscriptions as $subscription) {
            $result = self::sendToSubscription($subscription, $title, $body, $data, $options);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Send push notification to multiple users
     */
    public static function sendToUsers($userIds, $title, $body, $data = [], $options = []) {
        $success = true;
        foreach ($userIds as $userId) {
            $result = self::sendToUser($userId, $title, $body, $data, $options);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Send push notification to all subscribed users
     */
    public static function sendToAll($title, $body, $data = [], $options = []) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT * FROM push_subscriptions 
                WHERE is_active = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($subscriptions)) {
                return false;
            }
            
            $success = true;
            foreach ($subscriptions as $subscription) {
                $result = self::sendToSubscription($subscription, $title, $body, $data, $options);
                if (!$result) {
                    $success = false;
                }
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log('Send to all error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send push notification to specific subscription
     */
    public static function sendToSubscription($subscription, $title, $body, $data = [], $options = []) {
        if (!self::isAvailable()) {
            return false;
        }
        
        try {
            self::init();
            
            $webPush = new \Minishlink\WebPush\WebPush(self::$webPushAuth);
            
            // Prepare subscription object
            $subscriptionObject = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $subscription['endpoint'],
                'keys' => [
                    'p256dh' => $subscription['p256dh_key'],
                    'auth' => $subscription['auth_key']
                ]
            ]);
            
            // Prepare notification payload
            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => '/assets/icons/android-chrome-192x192.png',
                'badge' => '/assets/icons/favicon-32x32.png',
                'image' => $options['image'] ?? null,
                'data' => array_merge([
                    'url' => '/public/index.php',
                    'timestamp' => time()
                ], $data),
                'actions' => $options['actions'] ?? [
                    [
                        'action' => 'open',
                        'title' => I18nHelper::t('open_app', [], 'notifications'),
                        'icon' => '/assets/icons/action-open.png'
                    ],
                    [
                        'action' => 'close',
                        'title' => I18nHelper::t('close', [], 'notifications'),
                        'icon' => '/assets/icons/action-close.png'
                    ]
                ],
                'tag' => $options['tag'] ?? 'general',
                'renotify' => $options['renotify'] ?? false,
                'requireInteraction' => $options['requireInteraction'] ?? false,
                'silent' => $options['silent'] ?? false,
                'vibrate' => $options['vibrate'] ?? [200, 100, 200],
                'timestamp' => time() * 1000
            ]);
            
            // Send notification
            $report = $webPush->sendOneNotification(
                $subscriptionObject,
                $payload,
                ['TTL' => $options['ttl'] ?? 86400] // 24 hours default
            );
            
            // Handle result
            if ($report->isSuccess()) {
                // Log successful send
                self::logNotification($subscription['user_id'], $title, $body, 'sent');
                return true;
            } else {
                // Handle failed send
                if ($report->isSubscriptionExpired()) {
                    // Remove expired subscription
                    self::removeSubscription($subscription['id']);
                }
                
                error_log('Push notification failed: ' . $report->getReason());
                self::logNotification($subscription['user_id'], $title, $body, 'failed', $report->getReason());
                return false;
            }
            
        } catch (Exception $e) {
            error_log('Push notification error: ' . $e->getMessage());
            self::logNotification($subscription['user_id'] ?? 0, $title, $body, 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove expired or invalid subscription
     */
    private static function removeSubscription($subscriptionId) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE push_subscriptions 
                SET is_active = 0, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$subscriptionId]);
            
        } catch (Exception $e) {
            error_log('Remove subscription error: ' . $e->getMessage());
        }
    }
    
    /**
     * Log notification activity
     */
    private static function logNotification($userId, $title, $body, $status, $error = null) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO notification_logs 
                (user_id, title, body, status, error_message, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $title, $body, $status, $error]);
            
        } catch (Exception $e) {
            error_log('Log notification error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get notification statistics
     */
    public static function getStats($userId = null, $days = 30) {
        try {
            $db = Database::getConnection();
            
            $whereClause = $userId ? "WHERE user_id = ?" : "";
            $params = $userId ? [$userId, $days] : [$days];
            
            $stmt = $db->prepare("
                SELECT 
                    status,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM notification_logs 
                $whereClause
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY status, DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Get notification stats error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up old notification logs
     */
    public static function cleanupLogs($days = 90) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                DELETE FROM notification_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log('Cleanup logs error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Generate VAPID keys (for initial setup)
     */
    public static function generateVapidKeys() {
        if (!class_exists('Minishlink\WebPush\VAPID')) {
            throw new Exception('WebPush library not available');
        }
        
        return \Minishlink\WebPush\VAPID::createVapidKeys();
    }
    
    /**
     * Test notification send capability
     */
    public static function testNotification($userId) {
        return self::sendToUser(
            $userId,
            I18nHelper::t('test_notification_title', [], 'notifications'),
            I18nHelper::t('test_notification_body', [], 'notifications'),
            ['test' => true],
            ['tag' => 'test', 'requireInteraction' => true]
        );
    }
    
    /**
     * Send collection-related notifications
     */
    public static function sendCollectionNotification($userId, $type, $itemTitle, $data = []) {
        $notifications = [
            'item_added' => [
                'title' => I18nHelper::t('notification_item_added_title', [], 'notifications'),
                'body' => I18nHelper::t('notification_item_added_body', ['item' => $itemTitle], 'notifications')
            ],
            'item_updated' => [
                'title' => I18nHelper::t('notification_item_updated_title', [], 'notifications'),
                'body' => I18nHelper::t('notification_item_updated_body', ['item' => $itemTitle], 'notifications')
            ],
            'collection_shared' => [
                'title' => I18nHelper::t('notification_collection_shared_title', [], 'notifications'),
                'body' => I18nHelper::t('notification_collection_shared_body', [], 'notifications')
            ],
            'reminder' => [
                'title' => I18nHelper::t('notification_reminder_title', [], 'notifications'),
                'body' => I18nHelper::t('notification_reminder_body', ['item' => $itemTitle], 'notifications')
            ]
        ];
        
        if (!isset($notifications[$type])) {
            return false;
        }
        
        $notification = $notifications[$type];
        
        return self::sendToUser(
            $userId,
            $notification['title'],
            $notification['body'],
            array_merge(['type' => $type, 'item_title' => $itemTitle], $data),
            ['tag' => $type]
        );
    }
    
    /**
     * Schedule delayed notification (would require cron job implementation)
     */
    public static function scheduleNotification($userId, $title, $body, $sendAt, $data = [], $options = []) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO scheduled_notifications 
                (user_id, title, body, data, options, send_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $title,
                $body,
                json_encode($data),
                json_encode($options),
                $sendAt
            ]);
            
            return $db->lastInsertId();
            
        } catch (Exception $e) {
            error_log('Schedule notification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process scheduled notifications (for cron job)
     */
    public static function processScheduledNotifications() {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT * FROM scheduled_notifications 
                WHERE send_at <= NOW() AND sent = 0
                ORDER BY send_at ASC
                LIMIT 100
            ");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            foreach ($notifications as $notification) {
                $success = self::sendToUser(
                    $notification['user_id'],
                    $notification['title'],
                    $notification['body'],
                    json_decode($notification['data'], true) ?: [],
                    json_decode($notification['options'], true) ?: []
                );
                
                // Mark as sent
                $updateStmt = $db->prepare("
                    UPDATE scheduled_notifications 
                    SET sent = 1, sent_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$notification['id']]);
                
                $processed++;
            }
            
            return $processed;
            
        } catch (Exception $e) {
            error_log('Process scheduled notifications error: ' . $e->getMessage());
            return 0;
        }
    }
} 