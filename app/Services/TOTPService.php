<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class TOTPService
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new TOTP secret
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR code for TOTP setup
     */
    public function generateQRCode(string $secret, string $email, string $appName = 'Collection Manager'): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $appName,
            $email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Verify TOTP code
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Generate backup codes
     */
    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }
        return $codes;
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode(array $backupCodes, string $code): bool
    {
        $index = array_search($code, $backupCodes);
        if ($index !== false) {
            // Remove used backup code
            unset($backupCodes[$index]);
            return true;
        }
        return false;
    }
} 