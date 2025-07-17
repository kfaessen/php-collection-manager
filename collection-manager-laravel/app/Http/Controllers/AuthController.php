<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\TOTPService;

class AuthController extends Controller
{
    protected $totpService;

    public function __construct(TOTPService $totpService)
    {
        $this->totpService = $totpService;
    }

    /**
     * Show the login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/admin');
        }
        return view('auth.login');
    }

    /**
     * Handle login attempt
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'totp_code' => 'nullable|string|size:6',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'De opgegeven inloggegevens zijn niet correct.',
            ])->withInput($request->only('email'));
        }

        // Check if TOTP is required
        if ($user->totp_enabled) {
            if (!$request->filled('totp_code')) {
                // Store user ID in session for TOTP verification
                session(['pending_totp_user_id' => $user->id]);
                return view('auth.totp.verify', compact('user'));
            }

            // Verify TOTP code
            if (!$this->totpService->verifyCode($user->totp_secret, $request->totp_code)) {
                return back()->withErrors([
                    'totp_code' => 'Ongeldige twee-factor authenticatie code.',
                ])->withInput($request->only('email'));
            }
        }

        // Login successful
        Auth::login($user);
        $request->session()->regenerate();
        session()->forget('pending_totp_user_id');
        
        // Update last login
        $user->update(['last_login' => now()]);
        
        // Check if user has admin access
        if ($user->hasRole('admin') || $user->hasPermission('admin.access')) {
            return redirect()->intended('/admin');
        }
        
        return redirect()->intended('/');
    }

    /**
     * Handle TOTP verification
     */
    public function verifyTOTP(Request $request)
    {
        $request->validate([
            'totp_code' => 'required|string|size:6',
        ]);

        $userId = session('pending_totp_user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        if ($this->totpService->verifyCode($user->totp_secret, $request->totp_code)) {
            Auth::login($user);
            $request->session()->regenerate();
            session()->forget('pending_totp_user_id');
            
            // Update last login
            $user->update(['last_login' => now()]);
            
            if ($user->hasRole('admin') || $user->hasPermission('admin.access')) {
                return redirect()->intended('/admin');
            }
            
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'totp_code' => 'Ongeldige twee-factor authenticatie code.',
        ]);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }

    /**
     * Show the registration form
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect('/admin');
        }
        return view('auth.register');
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect('/admin');
    }
} 