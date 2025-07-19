<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show user profile
     */
    public function show()
    {
        $user = Auth::user();
        return view('profile.show', compact('user'));
    }

    /**
     * Update profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return back()->with('success', 'Profiel succesvol bijgewerkt.');
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Huidig wachtwoord is niet correct.']);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Wachtwoord succesvol gewijzigd.');
    }

    /**
     * Disable TOTP
     */
    public function disableTOTP(Request $request)
    {
        $request->validate([
            'totp_code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        
        // Verify TOTP code
        $totpService = app(\App\Services\TOTPService::class);
        if (!$totpService->verifyCode($user->totp_secret, $request->totp_code)) {
            return back()->withErrors(['totp_code' => 'Ongeldige authenticatie code.']);
        }

        $user->update([
            'totp_enabled' => false,
            'totp_secret' => null,
            'totp_backup_codes' => null
        ]);

        return back()->with('success', 'Twee-factor authenticatie is uitgeschakeld.');
    }
} 