<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        // If user is already authenticated, redirect to dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login attempt.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $username = $request->input('username');
        $password = $request->input('password');

        // Try to find user by username or email
        $user = User::where('username', $username)
                   ->orWhere('email', $username)
                   ->first();

        if (!$user) {
            return back()->withErrors([
                'username' => 'Gebruikersnaam of e-mailadres niet gevonden.',
            ])->withInput();
        }

        // Check if user is active
        if (!$user->is_active) {
            return back()->withErrors([
                'username' => 'Account is gedeactiveerd.',
            ])->withInput();
        }

        // Check if user is locked
        if ($user->isLocked()) {
            return back()->withErrors([
                'username' => 'Account is tijdelijk geblokkeerd. Probeer het later opnieuw.',
            ])->withInput();
        }

        // Verify password
        if (!Hash::check($password, $user->password_hash)) {
            // Increment failed login attempts
            $user->increment('failed_login_attempts');
            
            // Lock account if too many failed attempts
            if ($user->failed_login_attempts >= 5) {
                $user->update([
                    'locked_until' => now()->addMinutes(30)
                ]);
                
                return back()->withErrors([
                    'username' => 'Te veel mislukte inlogpogingen. Account is 30 minuten geblokkeerd.',
                ])->withInput();
            }

            return back()->withErrors([
                'password' => 'Wachtwoord is incorrect.',
            ])->withInput();
        }

        // Reset failed login attempts on successful login
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login' => now(),
        ]);

        // Log in the user
        Auth::login($user);

        // Check if user needs TOTP verification
        if ($user->hasTOTPEnabled()) {
            // Store user ID in session for TOTP verification
            session(['totp_user_id' => $user->id]);
            return redirect()->route('totp.verify');
        }

        // Redirect to intended page or dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }

    /**
     * Show TOTP verification form.
     */
    public function showTOTPVerification()
    {
        if (!session('totp_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.totp-verify');
    }

    /**
     * Handle TOTP verification.
     */
    public function verifyTOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'totp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $userId = session('totp_user_id');
        $totpCode = $request->input('totp_code');

        $user = User::find($userId);
        
        if (!$user || !$user->hasTOTPEnabled()) {
            return redirect()->route('login');
        }

        // Verify TOTP code
        if ($this->verifyTOTPCode($user, $totpCode)) {
            // Clear TOTP session
            session()->forget('totp_user_id');
            
            // Log in the user
            Auth::login($user);
            
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'totp_code' => 'TOTP code is incorrect.',
        ]);
    }

    /**
     * Verify TOTP code.
     */
    private function verifyTOTPCode(User $user, string $code): bool
    {
        // This is a simplified implementation
        // In a real application, you would use a proper TOTP library
        // like robthree/twofactorauth
        
        if (!$user->totp_secret) {
            return false;
        }

        // For now, we'll use a simple verification
        // In production, use proper TOTP verification
        $expectedCode = $this->generateTOTPCode($user->totp_secret);
        
        return $code === $expectedCode;
    }

    /**
     * Generate TOTP code (simplified).
     */
    private function generateTOTPCode(string $secret): string
    {
        // This is a placeholder implementation
        // In production, use a proper TOTP library
        $timeSlice = floor(time() / 30);
        $hash = hash_hmac('sha1', $timeSlice, $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
} 