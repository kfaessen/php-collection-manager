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

        try {
            return $this->oauthService->redirect($provider);
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'OAuth redirect mislukt: ' . $e->getMessage()]);
        }
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

        try {
            // Handle OAuth callback and get user
            $user = $this->oauthService->callback($provider);

            // Login user
            Auth::login($user);
            $request->session()->regenerate();

            // Redirect based on user role
            if ($user->hasRole('admin') || $user->hasPermission('admin.access')) {
                return redirect()->intended('/admin');
            }

            return redirect()->intended('/');

        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'OAuth login mislukt: ' . $e->getMessage()]);
        }
    }
} 