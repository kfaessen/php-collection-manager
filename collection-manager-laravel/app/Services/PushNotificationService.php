<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected $vapidPublicKey;
    protected $vapidPrivateKey;
    protected $vapidSubject;

    public function __construct()
    {
        $this->vapidPublicKey = env('VAPID_PUBLIC_KEY');
        $this->vapidPrivateKey = env('VAPID_PRIVATE_KEY');
        $this->vapidSubject = env('VAPID_SUBJECT', 'mailto:admin@collectionmanager.local');
    }

    /**
     * Get VAPID public key
     */
    public function getVapidPublicKey(): ?string
    {
        return $this->vapidPublicKey;
    }

    /**
     * Check if push notifications are enabled
     */
    public function isEnabled(): bool
    {
        return !empty($this->vapidPublicKey) && !empty($this->vapidPrivateKey);
    }

    /**
     * Send push notification to a subscription
     */
    public function sendNotification(string $endpoint, string $auth, string $p256dh, array $payload): bool
    {
        if (!$this->isEnabled()) {
            Log::warning('Push notifications not configured');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->generateAuthorizationHeader($endpoint),
                'Content-Type' => 'application/json',
                'TTL' => '86400', // 24 hours
            ])->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info('Push notification sent successfully', ['endpoint' => $endpoint]);
                return true;
            } else {
                Log::error('Failed to send push notification', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending push notification', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple subscriptions
     */
    public function sendToMultiple(array $subscriptions, array $payload): array
    {
        $results = [];
        
        foreach ($subscriptions as $subscription) {
            $results[] = [
                'subscription' => $subscription,
                'success' => $this->sendNotification(
                    $subscription['endpoint'],
                    $subscription['keys']['auth'],
                    $subscription['keys']['p256dh'],
                    $payload
                )
            ];
        }

        return $results;
    }

    /**
     * Generate VAPID authorization header
     */
    protected function generateAuthorizationHeader(string $endpoint): string
    {
        $expiration = time() + 12 * 3600; // 12 hours
        $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
        
        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256'
        ];

        $payload = [
            'aud' => $audience,
            'exp' => $expiration,
            'sub' => $this->vapidSubject
        ];

        $jwt = $this->generateJWT($header, $payload);
        
        return 'vapid t=' . $jwt . ', k=' . $this->vapidPublicKey;
    }

    /**
     * Generate JWT token
     */
    protected function generateJWT(array $header, array $payload): string
    {
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $data = $headerEncoded . '.' . $payloadEncoded;
        $signature = $this->sign($data);
        
        return $data . '.' . $this->base64UrlEncode($signature);
    }

    /**
     * Sign data with VAPID private key
     */
    protected function sign(string $data): string
    {
        $key = openssl_pkey_get_private($this->vapidPrivateKey);
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);
        
        return $signature;
    }

    /**
     * Base64 URL encode
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Create notification payload
     */
    public function createPayload(string $title, string $body, string $icon = null, string $url = null): array
    {
        $payload = [
            'title' => $title,
            'body' => $body,
            'icon' => $icon ?: asset('images/icon-192x192.png'),
            'badge' => asset('images/badge-72x72.png'),
            'vibrate' => [100, 50, 100],
            'data' => [
                'dateOfArrival' => time(),
                'primaryKey' => 1
            ],
            'actions' => [
                [
                    'action' => 'explore',
                    'title' => 'Bekijken',
                    'icon' => asset('images/checkmark.png')
                ],
                [
                    'action' => 'close',
                    'title' => 'Sluiten',
                    'icon' => asset('images/xmark.png')
                ]
            ]
        ];

        if ($url) {
            $payload['data']['url'] = $url;
        }

        return $payload;
    }

    /**
     * Send collection update notification
     */
    public function sendCollectionUpdateNotification(array $subscriptions, string $collectionName): array
    {
        $payload = $this->createPayload(
            'Collectie Bijgewerkt',
            "De collectie '{$collectionName}' is bijgewerkt.",
            null,
            route('admin.dashboard')
        );

        return $this->sendToMultiple($subscriptions, $payload);
    }

    /**
     * Send new item notification
     */
    public function sendNewItemNotification(array $subscriptions, string $itemName, string $collectionName): array
    {
        $payload = $this->createPayload(
            'Nieuw Item Toegevoegd',
            "{$itemName} is toegevoegd aan {$collectionName}.",
            null,
            route('admin.dashboard')
        );

        return $this->sendToMultiple($subscriptions, $payload);
    }
} 