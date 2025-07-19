<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TOTPService;
use App\Models\User;

class TOTPController extends Controller
{
    protected $totpService;

    public function __construct(TOTPService $totpService)
    {
        $this->totpService = $totpService;
        $this->middleware('auth');
    }

    /**
     * Show TOTP setup page
     */
    public function showSetup()
    {
        $user = Auth::user();
        
        if ($user->totp_enabled) {
            return redirect()->route('profile')->with('info', 'TOTP is al ingeschakeld.');
        }

        // Generate new secret if not exists
        if (!$user->totp_secret) {
            $user->update([
                'totp_secret' => $this->totpService->generateSecret(),
                'totp_backup_codes' => $this->totpService->generateBackupCodes()
            ]);
        }

        $qrCode = $this->totpService->generateQRCode(
            $user->totp_secret,
            $user->email
        );

        return view('auth.totp.setup', compact('qrCode', 'user'));
    }

    /**
     * Enable TOTP
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        
        if ($this->totpService->verifyCode($user->totp_secret, $request->code)) {
            $user->update(['totp_enabled' => true]);
            
            return redirect()->route('profile')
                ->with('success', 'Twee-factor authenticatie is succesvol ingeschakeld.');
        }

        return back()->withErrors(['code' => 'Ongeldige authenticatie code.']);
    }

    /**
     * Disable TOTP
     */
    public function disable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        
        if ($this->totpService->verifyCode($user->totp_secret, $request->code)) {
            $user->update([
                'totp_enabled' => false,
                'totp_secret' => null,
                'totp_backup_codes' => null
            ]);
            
            return redirect()->route('profile')
                ->with('success', 'Twee-factor authenticatie is uitgeschakeld.');
        }

        return back()->withErrors(['code' => 'Ongeldige authenticatie code.']);
    }

    /**
     * Show backup codes
     */
    public function showBackupCodes()
    {
        $user = Auth::user();
        
        if (!$user->totp_enabled) {
            return redirect()->route('totp.setup');
        }

        return view('auth.totp.backup-codes', compact('user'));
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes()
    {
        $user = Auth::user();
        
        $user->update([
            'totp_backup_codes' => $this->totpService->generateBackupCodes()
        ]);

        return redirect()->route('totp.backup-codes')
            ->with('success', 'Backup codes zijn opnieuw gegenereerd.');
    }
} 