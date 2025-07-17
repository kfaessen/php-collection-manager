<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PushNotificationService;

class NotificationController extends Controller
{
    protected $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Get VAPID public key
     */
    public function getVapidKey()
    {
        return response()->json([
            'publicKey' => $this->pushService->getVapidPublicKey()
        ]);
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'subscription.keys.auth' => 'required|string',
            'subscription.keys.p256dh' => 'required|string',
        ]);

        $user = Auth::user();
        $subscription = $request->input('subscription');

        // Store subscription in user's session or database
        $subscriptions = session('push_subscriptions', []);
        $subscriptions[] = $subscription;
        session(['push_subscriptions' => $subscriptions]);

        // Update user's push notification preference
        $user->update(['push_notifications' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'subscription.endpoint' => 'required|string',
        ]);

        $user = Auth::user();
        $endpoint = $request->input('subscription.endpoint');

        // Remove subscription from session
        $subscriptions = session('push_subscriptions', []);
        $subscriptions = array_filter($subscriptions, function($sub) use ($endpoint) {
            return $sub['endpoint'] !== $endpoint;
        });
        session(['push_subscriptions' => $subscriptions]);

        // Update user's push notification preference if no subscriptions left
        if (empty($subscriptions)) {
            $user->update(['push_notifications' => false]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Test push notification
     */
    public function test(Request $request)
    {
        $user = Auth::user();
        $subscriptions = session('push_subscriptions', []);

        if (empty($subscriptions)) {
            return response()->json([
                'success' => false,
                'message' => 'Geen push notification subscriptions gevonden.'
            ]);
        }

        $payload = $this->pushService->createPayload(
            'Test Notificatie',
            'Dit is een test notificatie van Collection Manager.',
            null,
            route('admin.dashboard')
        );

        $results = $this->pushService->sendToMultiple($subscriptions, $payload);

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $totalCount = count($results);

        return response()->json([
            'success' => true,
            'message' => "Test notificatie verzonden: {$successCount}/{$totalCount} succesvol.",
            'results' => $results
        ]);
    }

    /**
     * Get notification settings
     */
    public function settings()
    {
        $user = Auth::user();
        $subscriptions = session('push_subscriptions', []);

        return response()->json([
            'enabled' => $this->pushService->isEnabled(),
            'userEnabled' => $user->push_notifications,
            'subscriptionCount' => count($subscriptions),
            'vapidPublicKey' => $this->pushService->getVapidPublicKey()
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'push_notifications' => 'boolean',
            'email_notifications' => 'boolean',
        ]);

        $user = Auth::user();
        $user->update($request->only(['push_notifications', 'email_notifications']));

        return response()->json([
            'success' => true,
            'message' => 'Notificatie voorkeuren bijgewerkt.'
        ]);
    }
} 