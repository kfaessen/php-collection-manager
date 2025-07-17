<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\User;

class OAuthService
{
    protected $providers = [
        'google' => [
            'client_id' => null,
            'client_secret' => null,
            'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'user_info_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
            'scopes' => ['email', 'profile'],
        ],
        'facebook' => [
            'client_id' => null,
            'client_secret' => null,
            'auth_url' => 'https://www.facebook.com/v12.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v12.0/oauth/access_token',
            'user_info_url' => 'https://graph.facebook.com/me',
            'scopes' => ['email', 'public_profile'],
        ],
    ];

    public function __construct()
    {
        // Load OAuth credentials from environment
        $this->providers['google']['client_id'] = env('GOOGLE_CLIENT_ID');
        $this->providers['google']['client_secret'] = env('GOOGLE_CLIENT_SECRET');
        $this->providers['facebook']['client_id'] = env('FACEBOOK_CLIENT_ID');
        $this->providers['facebook']['client_secret'] = env('FACEBOOK_CLIENT_SECRET');
    }

    /**
     * Get OAuth authorization URL
     */
    public function getAuthUrl(string $provider, string $redirectUri): string
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Unsupported OAuth provider: {$provider}");
        }

        $config = $this->providers[$provider];
        $state = Str::random(40);

        // Store state in session for security
        session(['oauth_state' => $state, 'oauth_provider' => $provider]);

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
        ];

        if ($provider === 'facebook') {
            $params['display'] = 'popup';
        }

        return $config['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $provider, string $code, string $redirectUri): array
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Unsupported OAuth provider: {$provider}");
        }

        $config = $this->providers[$provider];

        $response = Http::asForm()->post($config['token_url'], [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to get access token: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Get user information from OAuth provider
     */
    public function getUserInfo(string $provider, string $accessToken): array
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Unsupported OAuth provider: {$provider}");
        }

        $config = $this->providers[$provider];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ])->get($config['user_info_url']);

        if (!$response->successful()) {
            throw new \Exception("Failed to get user info: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Find or create user from OAuth data
     */
    public function findOrCreateUser(array $userInfo, string $provider): User
    {
        $email = $userInfo['email'] ?? null;
        
        if (!$email) {
            throw new \Exception("Email is required for OAuth registration");
        }

        // Try to find existing user by email
        $user = User::where('email', $email)->first();

        if ($user) {
            // Update user's OAuth information
            $user->update([
                'avatar_url' => $userInfo['picture'] ?? $userInfo['avatar'] ?? null,
                'last_login' => now(),
            ]);

            return $user;
        }

        // Create new user
        $user = User::create([
            'name' => $userInfo['name'] ?? $userInfo['given_name'] . ' ' . $userInfo['family_name'],
            'email' => $email,
            'password' => bcrypt(Str::random(32)), // Random password for OAuth users
            'avatar_url' => $userInfo['picture'] ?? $userInfo['avatar'] ?? null,
            'registration_method' => $provider,
            'email_verified_at' => now(), // OAuth emails are pre-verified
            'last_login' => now(),
        ]);

        // Assign default user role
        $userRole = \App\Models\Role::where('name', 'user')->first();
        if ($userRole) {
            $user->roles()->attach($userRole);
        }

        return $user;
    }

    /**
     * Verify OAuth state
     */
    public function verifyState(string $state): bool
    {
        return session('oauth_state') === $state;
    }

    /**
     * Get current OAuth provider from session
     */
    public function getCurrentProvider(): ?string
    {
        return session('oauth_provider');
    }

    /**
     * Clear OAuth session data
     */
    public function clearSession(): void
    {
        session()->forget(['oauth_state', 'oauth_provider']);
    }

    /**
     * Check if OAuth provider is enabled
     */
    public function isEnabled(string $provider): bool
    {
        if (!isset($this->providers[$provider])) {
            return false;
        }

        $config = $this->providers[$provider];
        return !empty($config['client_id']) && !empty($config['client_secret']);
    }
} 