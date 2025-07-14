/**
 * Collection Manager - Service Worker
 * Provides offline functionality, caching, and push notifications
 */

const CACHE_NAME = 'collection-manager-v2.0.0';
const STATIC_CACHE_NAME = 'collection-manager-static-v2.0.0';
const DYNAMIC_CACHE_NAME = 'collection-manager-dynamic-v2.0.0';
const IMAGE_CACHE_NAME = 'collection-manager-images-v2.0.0';

// Files to cache for offline functionality
const STATIC_ASSETS = [
    // Core pages
    './public/index.php',
    './public/login.php',
    './public/profile.php',
    
    // Styles and scripts
    './assets/css/style.css',
    './assets/js/app.js',
    
    // External CDN resources
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://unpkg.com/html5-qrcode',
    
    // PWA assets
    './manifest.json',
    './assets/icons/android-chrome-192x192.png',
    './assets/icons/android-chrome-512x512.png',
    './assets/icons/apple-touch-icon.png',
    './assets/icons/favicon-32x32.png',
    './assets/icons/favicon-16x16.png',
    
    // Offline page
    './offline.html'
];

// API endpoints that should be cached dynamically
const API_ENDPOINTS = [
    './public/index.php',
    './public/api-manager.php',
    './public/language.php'
];

// Network-first strategies for these paths
const NETWORK_FIRST_PATHS = [
    '/public/index.php',
    '/public/admin.php',
    '/public/profile.php',
    '/api/',
    '/oauth.php'
];

// Cache-first strategies for these file types
const CACHE_FIRST_EXTENSIONS = [
    '.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg',
    '.css', '.js', '.woff', '.woff2', '.ttf'
];

/**
 * Service Worker Installation
 */
self.addEventListener('install', function(event) {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        Promise.all([
            // Cache static assets
            caches.open(STATIC_CACHE_NAME).then(function(cache) {
                console.log('Service Worker: Caching static assets...');
                return cache.addAll(STATIC_ASSETS);
            }),
            
            // Create other cache stores
            caches.open(DYNAMIC_CACHE_NAME),
            caches.open(IMAGE_CACHE_NAME)
        ]).then(function() {
            console.log('Service Worker: Installation complete');
            return self.skipWaiting();
        }).catch(function(error) {
            console.error('Service Worker: Installation failed', error);
        })
    );
});

/**
 * Service Worker Activation
 */
self.addEventListener('activate', function(event) {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        Promise.all([
            // Clean up old caches
            cleanupOldCaches(),
            
            // Claim all clients
            self.clients.claim()
        ]).then(function() {
            console.log('Service Worker: Activation complete');
        })
    );
});

/**
 * Fetch Event Handler
 */
self.addEventListener('fetch', function(event) {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip chrome-extension and other non-http(s) requests
    if (!url.protocol.startsWith('http')) {
        return;
    }
    
    // Handle different types of requests
    if (isImageRequest(request)) {
        event.respondWith(handleImageRequest(request));
    } else if (isAPIRequest(request)) {
        event.respondWith(handleAPIRequest(request));
    } else if (isStaticAsset(request)) {
        event.respondWith(handleStaticAssetRequest(request));
    } else if (isPageRequest(request)) {
        event.respondWith(handlePageRequest(request));
    } else {
        event.respondWith(handleGenericRequest(request));
    }
});

/**
 * Push Notification Handler
 */
self.addEventListener('push', function(event) {
    console.log('Service Worker: Push message received', event);
    
    let options = {
        body: 'You have a new notification',
        icon: './assets/icons/android-chrome-192x192.png',
        badge: './assets/icons/favicon-32x32.png',
        vibrate: [200, 100, 200],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Open Collection',
                icon: './assets/icons/action-explore.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: './assets/icons/action-close.png'
            }
        ]
    };
    
    if (event.data) {
        const data = event.data.json();
        options.title = data.title || 'Collection Manager';
        options.body = data.body || options.body;
        options.icon = data.icon || options.icon;
        options.data = { ...options.data, ...data };
    }
    
    event.waitUntil(
        self.registration.showNotification('Collection Manager', options)
    );
});

/**
 * Notification Click Handler
 */
self.addEventListener('notificationclick', function(event) {
    console.log('Service Worker: Notification click received', event);
    
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('./public/index.php')
        );
    } else if (event.action === 'close') {
        // Just close the notification
        return;
    } else {
        // Default action - open the app
        event.waitUntil(
            clients.matchAll().then(function(clientList) {
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url === './public/index.php' && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow('./public/index.php');
                }
            })
        );
    }
});

/**
 * Background Sync Handler
 */
self.addEventListener('sync', function(event) {
    console.log('Service Worker: Background sync', event.tag);
    
    if (event.tag === 'background-sync-items') {
        event.waitUntil(syncPendingItems());
    }
});

/**
 * Message Handler
 */
self.addEventListener('message', function(event) {
    console.log('Service Worker: Message received', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            cacheUrls(event.data.payload)
        );
    }
});

/**
 * Helper Functions
 */

function isImageRequest(request) {
    return request.destination === 'image' || 
           /\.(png|jpg|jpeg|gif|webp|svg)$/i.test(request.url);
}

function isAPIRequest(request) {
    return request.url.includes('/api/') || 
           request.url.includes('action=') ||
           API_ENDPOINTS.some(endpoint => request.url.includes(endpoint));
}

function isStaticAsset(request) {
    return CACHE_FIRST_EXTENSIONS.some(ext => request.url.includes(ext)) ||
           request.destination === 'style' ||
           request.destination === 'script' ||
           request.destination === 'font';
}

function isPageRequest(request) {
    return request.destination === 'document';
}

function shouldUseNetworkFirst(request) {
    return NETWORK_FIRST_PATHS.some(path => request.url.includes(path));
}

/**
 * Request Handlers
 */

async function handleImageRequest(request) {
    try {
        // Try cache first for images
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // If not in cache, fetch from network
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(IMAGE_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('Service Worker: Image request failed', error);
        // Return placeholder image
        return caches.match('./assets/icons/placeholder-image.png');
    }
}

async function handleAPIRequest(request) {
    try {
        // For API requests, try network first
        const networkResponse = await fetch(request);
        
        // Cache successful GET responses
        if (networkResponse.ok && request.method === 'GET') {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('Service Worker: API request failed, trying cache', error);
        
        // If network fails, try cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline response
        return new Response(JSON.stringify({
            success: false,
            message: 'Offline - no cached data available',
            offline: true
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

async function handleStaticAssetRequest(request) {
    try {
        // Try cache first for static assets
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // If not in cache, fetch from network
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('Service Worker: Static asset request failed', error);
        throw error;
    }
}

async function handlePageRequest(request) {
    try {
        if (shouldUseNetworkFirst(request)) {
            // Network first for dynamic pages
            return await handleNetworkFirstRequest(request);
        } else {
            // Cache first for other pages
            return await handleCacheFirstRequest(request);
        }
    } catch (error) {
        console.log('Service Worker: Page request failed', error);
        return caches.match('./offline.html');
    }
}

async function handleNetworkFirstRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        // If network fails, try cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

async function handleCacheFirstRequest(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    // If not in cache, fetch from network
    const networkResponse = await fetch(request);
    
    // Cache successful responses
    if (networkResponse.ok) {
        const cache = await caches.open(DYNAMIC_CACHE_NAME);
        cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
}

async function handleGenericRequest(request) {
    try {
        return await fetch(request);
    } catch (error) {
        console.log('Service Worker: Generic request failed', error);
        throw error;
    }
}

/**
 * Cache Management
 */

async function cleanupOldCaches() {
    const cacheNames = await caches.keys();
    const currentCaches = [STATIC_CACHE_NAME, DYNAMIC_CACHE_NAME, IMAGE_CACHE_NAME];
    
    return Promise.all(
        cacheNames.map(cacheName => {
            if (!currentCaches.includes(cacheName)) {
                console.log('Service Worker: Deleting old cache', cacheName);
                return caches.delete(cacheName);
            }
        })
    );
}

async function cacheUrls(urls) {
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    return cache.addAll(urls);
}

/**
 * Background Sync
 */

async function syncPendingItems() {
    try {
        // This would sync any pending items that were created while offline
        const pendingItems = await getStoredPendingItems();
        
        for (const item of pendingItems) {
            try {
                const response = await fetch('./public/index.php', {
                    method: 'POST',
                    body: new FormData(item)
                });
                
                if (response.ok) {
                    await removePendingItem(item.id);
                }
            } catch (error) {
                console.log('Service Worker: Failed to sync item', error);
            }
        }
    } catch (error) {
        console.log('Service Worker: Background sync failed', error);
    }
}

async function getStoredPendingItems() {
    // This would retrieve pending items from IndexedDB
    // For now, return empty array
    return [];
}

async function removePendingItem(itemId) {
    // This would remove a pending item from IndexedDB
    console.log('Service Worker: Removing pending item', itemId);
}

/**
 * Utility Functions
 */

function isOnline() {
    return navigator.onLine;
}

function broadcastMessage(message) {
    return self.clients.matchAll().then(clients => {
        clients.forEach(client => {
            client.postMessage(message);
        });
    });
}

// Log service worker loading
console.log('Service Worker: Script loaded'); 