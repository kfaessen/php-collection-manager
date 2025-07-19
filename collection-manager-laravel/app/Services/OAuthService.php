<?php

namespace App\Services;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use App\Models\User;
use Spatie\Permission\Models\Role;

class OAuthService
{
    protected $providers = ['google', 'facebook'];

    /**
     * Redirect to OAuth provider
     */
    public function redirect(string $provider)
    {
        if (!in_array($provider, $this->providers)) {
            throw new \InvalidArgumentException("Unsupported OAuth provider: {$provider}");
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle OAuth callback
     */
    public function callback(string $provider): User
    {
        if (!in_array($provider, $this->providers)) {
            throw new \InvalidArgumentException("Unsupported OAuth provider: {$provider}");
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            throw new \Exception("Failed to authenticate with {$provider}: " . $e->getMessage());
        }

        return $this->findOrCreateUser($socialUser, $provider);
    }

    /**
     * Find or create user from OAuth data
     */
    public function findOrCreateUser($socialUser, string $provider): User
    {
        $email = $socialUser->getEmail();
        
        if (!$email) {
            throw new \Exception("Email is required for OAuth registration");
        }

        // Try to find existing user by email
        $user = User::where('email', $email)->first();

        if ($user) {
            // Update user's OAuth information
            $user->update([
                'avatar_url' => $socialUser->getAvatar(),
                'last_login' => now(),
            ]);

            return $user;
        }

        // Create new user
        $user = User::create([
            'name' => $socialUser->getName(),
            'email' => $email,
            'password' => bcrypt(Str::random(32)), // Random password for OAuth users
            'avatar_url' => $socialUser->getAvatar(),
            'registration_method' => $provider,
            'email_verified_at' => now(), // OAuth emails are pre-verified
            'last_login' => now(),
        ]);

        // Assign default user role
        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $user->assignRole($userRole);
        }

        return $user;
    }

    /**
     * Check if OAuth provider is enabled
     */
    public function isEnabled(string $provider): bool
    {
        if (!in_array($provider, $this->providers)) {
            return false;
        }

        $config = config("services.{$provider}");
        return !empty($config['client_id']) && !empty($config['client_secret']);
    }

    /**
     * Get enabled providers
     */
    public function getEnabledProviders(): array
    {
        return array_filter($this->providers, function ($provider) {
            return $this->isEnabled($provider);
        });
    }
} 