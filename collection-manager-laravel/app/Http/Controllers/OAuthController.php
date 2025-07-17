<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\OAuthService;

class OAuthController extends Controller
{
    protected $oauthService;

    public function __construct(OAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    /**
     * Redirect to OAuth provider
     */
    public function redirect(Request $request, string $provider)
    {
        if (!$this->oauthService->isEnabled($provider)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'OAuth login is niet beschikbaar.']);
        }

        $redirectUri = route('oauth.callback', $provider);
        $authUrl = $this->oauthService->getAuthUrl($provider, $redirectUri);

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request, string $provider)
    {
        if (!$this->oauthService->isEnabled($provider)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'OAuth login is niet beschikbaar.']);
        }

        // Verify state parameter
        $state = $request->get('state');
        if (!$this->oauthService->verifyState($state)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Ongeldige OAuth state.']);
        }

        // Check for authorization code
        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('login')
                ->withErrors(['email' => 'OAuth autorisatie mislukt.']);
        }

        try {
            // Exchange code for access token
            $redirectUri = route('oauth.callback', $provider);
            $tokenData = $this->oauthService->getAccessToken($provider, $code, $redirectUri);
            $accessToken = $tokenData['access_token'];

            // Get user information
            $userInfo = $this->oauthService->getUserInfo($provider, $accessToken);

            // Find or create user
            $user = $this->oauthService->findOrCreateUser($userInfo, $provider);

            // Login user
            Auth::login($user);
            $request->session()->regenerate();

            // Clear OAuth session data
            $this->oauthService->clearSession();

            // Redirect based on user role
            if ($user->hasRole('admin') || $user->hasPermission('admin.access')) {
                return redirect()->intended('/admin');
            }

            return redirect()->intended('/');

        } catch (\Exception $e) {
            // Clear OAuth session data
            $this->oauthService->clearSession();

            return redirect()->route('login')
                ->withErrors(['email' => 'OAuth login mislukt: ' . $e->getMessage()]);
        }
    }
} 