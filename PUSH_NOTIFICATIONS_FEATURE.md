# Web Push Notifications Functionaliteit

## Overzicht
De Web Push Notifications functionaliteit voegt native browser push notifications toe aan de Collection Manager applicatie. Dit stelt gebruikers in staat om meldingen te ontvangen over nieuwe items, updates en andere belangrijke gebeurtenissen, zelfs wanneer de applicatie niet actief open staat.

## 🎯 Doelstellingen

### Gebruikersbetrokkenheid
- **Real-time meldingen**: Directe notificaties voor nieuwe collectie-items
- **Gebruikersretentie**: Herinneringen en updates om gebruikers betrokken te houden
- **Personalisatie**: Aanpasbare notificatie-instellingen per gebruiker
- **Cross-platform**: Werkt op desktop en mobiele browsers

### Technische Doelen
- **PWA integratie**: Volledige Progressive Web App ervaring
- **Offline ondersteuning**: Notificaties blijven werken offline
- **Betrouwbaarheid**: Robuuste delivery en retry mechanismen
- **Privacy**: Gebruikerscontrole over notificatie-instellingen

## 🏗️ Architectuur

### Componenten Overzicht
```
Browser Client ←→ Service Worker ←→ Push Service ←→ Application Server
     ↑                                                        ↓
Push Manager                                           NotificationHelper
     ↑                                                        ↓
JavaScript API                                         Database Storage
```

### Server-side Componenten
- **NotificationHelper.php**: Core push notification management
- **notifications.php**: API endpoint voor client communicatie
- **Database Schema**: Subscription en preference opslag
- **Service Worker Integration**: Background message handling

### Client-side Componenten
- **JavaScript Push API**: Browser notification registration
- **Service Worker**: Background notification handling
- **UI Components**: Permission requests en settings
- **VAPID Authentication**: Secure message verification

## 📊 Database Schema

### Push Subscriptions Tabel
```sql
CREATE TABLE push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh_key TEXT NOT NULL,
    auth_key TEXT NOT NULL,
    user_agent TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Notification Logs Tabel
```sql
CREATE TABLE notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    status ENUM('sent', 'failed', 'error') NOT NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Notification Preferences Tabel
```sql
CREATE TABLE notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    item_added BOOLEAN DEFAULT 1,
    item_updated BOOLEAN DEFAULT 1,
    collection_shared BOOLEAN DEFAULT 1,
    reminders BOOLEAN DEFAULT 1,
    marketing BOOLEAN DEFAULT 0,
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Scheduled Notifications Tabel
```sql
CREATE TABLE scheduled_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    data JSON,
    options JSON,
    send_at TIMESTAMP NOT NULL,
    sent BOOLEAN DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🔧 Server-side Implementatie

### NotificationHelper Class
```php
class NotificationHelper {
    // Core Methods
    public static function subscribe($userId, $endpoint, $keys, $userAgent = null)
    public static function unsubscribe($userId, $endpoint = null)
    public static function sendToUser($userId, $title, $body, $data = [], $options = [])
    public static function sendToAll($title, $body, $data = [], $options = [])
    
    // Collection-specific
    public static function sendCollectionNotification($userId, $type, $itemTitle, $data = [])
    
    // Utility Methods
    public static function isAvailable()
    public static function getPublicKey()
    public static function testNotification($userId)
}
```

### VAPID Configuration
```php
// Environment variables required
PUSH_NOTIFICATIONS_ENABLED=true
VAPID_SUBJECT=mailto:admin@collectiebeheer.app
VAPID_PUBLIC_KEY=your_vapid_public_key_here
VAPID_PRIVATE_KEY=your_vapid_private_key_here
```

### API Endpoints
```php
// notifications.php endpoints
GET  /notifications.php?action=get_vapid_key
POST /notifications.php?action=subscribe
POST /notifications.php?action=unsubscribe
POST /notifications.php?action=test_notification
GET  /notifications.php?action=get_preferences
POST /notifications.php?action=update_preferences
POST /notifications.php?action=send_notification (admin)
```

## 💻 Client-side Implementatie

### Service Worker Integration
```javascript
// Service worker registration
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('./sw.js')
        .then(registration => {
            console.log('SW registered:', registration);
        });
}

// Push event handling
self.addEventListener('push', function(event) {
    const options = {
        body: event.data.text(),
        icon: './assets/icons/android-chrome-192x192.png',
        badge: './assets/icons/favicon-32x32.png',
        vibrate: [200, 100, 200],
        data: { url: './public/index.php' }
    };
    
    event.waitUntil(
        self.registration.showNotification('Collection Manager', options)
    );
});
```

### Push Subscription Management
```javascript
// Subscribe to push notifications
async function subscribeToPush() {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
    });
    
    return await sendSubscriptionToServer(subscription);
}

// Unsubscribe from push notifications
async function unsubscribeFromPush() {
    if (pushSubscription) {
        await pushSubscription.unsubscribe();
        await removeSubscriptionFromServer(pushSubscription);
    }
}
```

### UI Components
```javascript
// Notification permission button
function createNotificationButton() {
    const button = document.createElement('button');
    button.className = 'btn btn-outline-primary btn-sm me-2';
    button.innerHTML = '<i class="bi bi-bell"></i>';
    button.addEventListener('click', togglePushNotifications);
    return button;
}

// Permission request modal
function showNotificationPermissionModal() {
    // Custom modal for better UX than browser default
    // Shows benefits and allows user choice
}
```

## 🔔 Notification Types

### Collection Notifications
```php
// Item added
NotificationHelper::sendCollectionNotification(
    $userId, 
    'item_added', 
    $itemTitle, 
    ['item_id' => $id, 'type' => $type]
);

// Item updated
NotificationHelper::sendCollectionNotification(
    $userId, 
    'item_updated', 
    $itemTitle, 
    ['item_id' => $id]
);

// Collection shared
NotificationHelper::sendCollectionNotification(
    $userId, 
    'collection_shared', 
    '', 
    ['share_url' => $shareUrl]
);
```

### System Notifications
- **Maintenance**: Planned downtime notifications
- **Updates**: New feature announcements
- **Security**: Important security updates
- **Reminders**: Collection maintenance reminders

### Marketing Notifications (Optional)
- **New features**: Product updates
- **Tips**: Usage tips and tricks
- **Community**: User-generated content highlights

## ⚙️ Configuration & Settings

### Environment Configuration
```bash
# Core Settings
PUSH_NOTIFICATIONS_ENABLED=true
VAPID_SUBJECT=mailto:admin@collectiebeheer.app
VAPID_PUBLIC_KEY=BGtaku...
VAPID_PRIVATE_KEY=Pl7v8I...

# Delivery Settings
NOTIFICATION_DEFAULT_TTL=86400          # 24 hours
NOTIFICATION_QUIET_HOURS_START=22:00    # 10 PM
NOTIFICATION_QUIET_HOURS_END=08:00      # 8 AM
NOTIFICATION_MAX_RETRIES=3
NOTIFICATION_CLEANUP_DAYS=90
```

### User Preferences
```php
// Default notification preferences
$preferences = [
    'item_added' => true,        // New items in collection
    'item_updated' => true,      // Item updates
    'collection_shared' => true, // When collection is shared
    'reminders' => true,         // Periodic reminders
    'marketing' => false,        // Promotional messages
    'quiet_hours_start' => '22:00:00',
    'quiet_hours_end' => '08:00:00'
];
```

## 🔒 Security & Privacy

### VAPID Authentication
- **Public/Private Key Pair**: Secure server identification
- **Subject**: Contact information for push service
- **Token-based**: No credentials stored client-side

### Data Protection
```php
// Subscription data encryption
$subscription = [
    'endpoint' => $endpoint,        // Push service URL
    'keys' => [
        'p256dh' => $p256dhKey,    // Client public key
        'auth' => $authSecret      // Authentication secret
    ]
];
```

### User Control
- **Explicit consent**: Clear permission requests
- **Granular control**: Per-notification-type settings
- **Easy unsubscribe**: One-click unsubscribe
- **Data deletion**: Complete subscription removal

## 📱 Browser Compatibility

### Supported Browsers
- **Chrome**: 50+ (Desktop & Mobile)
- **Firefox**: 44+ (Desktop & Mobile)
- **Safari**: 16+ (macOS, iOS 16.4+)
- **Edge**: 79+ (Chromium-based)
- **Opera**: 39+ (Desktop & Mobile)

### Feature Detection
```javascript
// Check for push notification support
function checkPushSupport() {
    if (!('serviceWorker' in navigator)) {
        return false;
    }
    
    if (!('PushManager' in window)) {
        return false;
    }
    
    if (!('Notification' in window)) {
        return false;
    }
    
    return true;
}
```

### Progressive Enhancement
- **Graceful degradation**: App works without notifications
- **Fallback mechanisms**: Alternative notification methods
- **Feature detection**: Runtime capability checking

## 🔄 Message Flow & Lifecycle

### Subscription Flow
1. **User grants permission**
2. **Service worker registration**
3. **Push manager subscription**
4. **VAPID key exchange**
5. **Server-side storage**

### Message Delivery
1. **Server triggers notification**
2. **Push service delivery**
3. **Service worker receives**
4. **Notification display**
5. **User interaction tracking**

### Error Handling
```php
// Subscription validation and retry
try {
    $success = NotificationHelper::sendToUser($userId, $title, $body);
    if (!$success) {
        // Log failure and schedule retry
        NotificationHelper::scheduleRetry($userId, $notification);
    }
} catch (Exception $e) {
    // Handle expired subscriptions
    if ($e instanceof SubscriptionExpiredException) {
        NotificationHelper::removeExpiredSubscription($subscription);
    }
}
```

## 📊 Analytics & Monitoring

### Delivery Metrics
```php
// Track notification performance
$stats = NotificationHelper::getStats($userId, 30); // Last 30 days
// Returns: sent, delivered, clicked, failed counts
```

### User Engagement
- **Click-through rates**: Notification interaction tracking
- **Subscription retention**: Long-term subscription health
- **Preference analysis**: Popular notification types
- **Delivery success**: Technical delivery metrics

### Performance Monitoring
- **Delivery latency**: Time from send to display
- **Failure rates**: Technical delivery failures
- **Subscription churn**: User unsubscribe patterns
- **Battery impact**: Client-side performance

## 🛠️ Development & Testing

### Local Development Setup
```bash
# Generate VAPID keys
php -r "
require 'vendor/autoload.php';
$keys = \Minishlink\WebPush\VAPID::createVapidKeys();
echo 'Public Key: ' . $keys['publicKey'] . PHP_EOL;
echo 'Private Key: ' . $keys['privateKey'] . PHP_EOL;
"

# Add to .env file
VAPID_PUBLIC_KEY=generated_public_key
VAPID_PRIVATE_KEY=generated_private_key
```

### Testing Strategy
```javascript
// Test notification sending
async function testNotifications() {
    // Test subscription
    await subscribeToPush();
    
    // Test notification delivery
    await sendTestNotification();
    
    // Test unsubscription
    await unsubscribeFromPush();
}
```

### Debug Tools
- **Browser DevTools**: Service worker debugging
- **Push Testing**: Online VAPID testing tools
- **Server Logs**: Detailed delivery logging
- **Network Monitoring**: Push service communication

## 🚀 Deployment Considerations

### Server Requirements
- **HTTPS**: Required for push notifications
- **PHP 7.4+**: Modern PHP for WebPush library
- **Composer**: Package management for dependencies
- **Cron Jobs**: Scheduled notification processing

### Dependency Installation
```bash
# Install WebPush library
composer require minishlink/web-push

# Install JSON Web Token support
composer require firebase/php-jwt
```

### Production Configuration
```php
// Production VAPID setup
$auth = [
    'VAPID' => [
        'subject' => 'mailto:admin@your-domain.com',
        'publicKey' => 'your-production-public-key',
        'privateKey' => 'your-production-private-key'
    ]
];
```

## 📋 Usage Examples

### Basic Implementation
```php
// Send welcome notification
NotificationHelper::sendToUser(
    $userId,
    'Welkom bij Collection Manager!',
    'Begin met het toevoegen van je eerste items.',
    ['action' => 'welcome'],
    ['requireInteraction' => true]
);
```

### Collection Updates
```php
// Notify about new item
NotificationHelper::sendCollectionNotification(
    $userId,
    'item_added',
    'Super Mario Odyssey',
    ['item_id' => 123, 'type' => 'game']
);
```

### Admin Broadcasts
```php
// Send to all users
NotificationHelper::sendToAll(
    'Systeem Update',
    'De applicatie wordt vanavond bijgewerkt.',
    ['maintenance' => true],
    ['tag' => 'maintenance', 'requireInteraction' => true]
);
```

## 🔮 Future Enhancements

### Planned Features
- **Rich notifications**: Images and action buttons
- **Notification scheduling**: Time-based delivery
- **A/B testing**: Message optimization
- **Deep linking**: Direct navigation to specific content

### Advanced Features
- **Geolocation**: Location-based notifications
- **Machine learning**: Personalized timing
- **Integration**: Third-party service webhooks
- **Batch operations**: Bulk notification management

## 🎯 Best Practices

### User Experience
- **Clear value proposition**: Explain notification benefits
- **Gradual permission**: Request after user engagement
- **Relevant content**: Personalized and timely messages
- **Respect preferences**: Honor quiet hours and opt-outs

### Technical Implementation
- **Error handling**: Robust failure recovery
- **Performance**: Minimal client-side impact
- **Accessibility**: Screen reader compatible
- **Testing**: Comprehensive cross-browser testing

### Privacy & Compliance
- **GDPR compliance**: Data protection regulations
- **Clear consent**: Explicit user permission
- **Data minimization**: Store only necessary data
- **Right to deletion**: Complete data removal

## 📚 Resources & Documentation

### Technical References
- [Web Push Protocol](https://tools.ietf.org/html/rfc8030)
- [Push API Specification](https://w3c.github.io/push-api/)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [VAPID Specification](https://tools.ietf.org/html/rfc8292)

### Libraries & Tools
- [web-push-libs](https://github.com/web-push-libs) - Multi-language libraries
- [PWA Builder](https://www.pwabuilder.com/) - Microsoft PWA tools
- [Workbox](https://developers.google.com/web/tools/workbox) - Google PWA toolkit

### Testing Tools
- [Push Companion](https://web-push-codelab.glitch.me/) - Online testing tool
- [PWA Testing](https://pwa-test.com/) - Comprehensive PWA testing
- [Lighthouse](https://developers.google.com/web/tools/lighthouse) - Performance auditing

## 🎉 Conclusie

De Web Push Notifications functionaliteit transformeert de Collection Manager in een moderne, engaging applicatie die gebruikers verbonden houdt met hun collecties. Door gebruik te maken van moderne web standards en best practices biedt het systeem een betrouwbare, veilige en gebruiksvriendelijke notificatie-ervaring die de algehele waarde van de applicatie significant verhoogt. 