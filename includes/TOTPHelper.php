<?php
namespace CollectionManager;

class TOTPHelper 
{
    /**
     * Generate a new TOTP secret
     */
    public static function generateSecret() 
    {
        return self::base32Encode(random_bytes(20));
    }
    
    /**
     * Generate QR code URL for TOTP setup
     */
    public static function generateQRUrl($secret, $username, $issuer = null) 
    {
        if ($issuer === null) {
            $issuer = Environment::get('TOTP_ISSUER', 'Collectiebeheer');
        }
        
        $label = urlencode($username);
        $issuer = urlencode($issuer);
        $secret = urlencode($secret);
        
        return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }
    
    /**
     * Generate backup codes
     */
    public static function generateBackupCodes($count = null) 
    {
        if ($count === null) {
            $count = Environment::get('TOTP_BACKUP_CODES_COUNT', 10);
        }
        
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = sprintf('%08d', mt_rand(0, 99999999));
        }
        return $codes;
    }
    
    /**
     * Verify TOTP code
     */
    public static function verifyCode($secret, $code, $window = null) 
    {
        if ($window === null) {
            $window = Environment::get('TOTP_WINDOW', 1);
        }
        
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return false;
        }
        
        $timeSlice = floor(time() / 30);
        
        for ($i = -$window; $i <= $window; $i++) {
            $calculatedCode = self::generateCode($secret, $timeSlice + $i);
            if (self::timingSafeEquals($calculatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP code for a specific time slice
     */
    private static function generateCode($secret, $timeSlice) 
    {
        $secretkey = self::base32Decode($secret);
        
        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        
        // Generate HMAC-SHA1
        $hash = hash_hmac('SHA1', $time, $secretkey, true);
        
        // Use last nipple of result as index/offset
        $offset = ord(substr($hash, -1)) & 0x0F;
        
        // Generate 4 byte code
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 encode
     */
    private static function base32Encode($data) 
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base = strlen($alphabet);
        $encoded = '';
        
        $bits = 0;
        $buffer = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $buffer = ($buffer << 8) | ord($data[$i]);
            $bits += 8;
            
            while ($bits >= 5) {
                $bits -= 5;
                $encoded .= $alphabet[($buffer >> $bits) & 31];
            }
        }
        
        if ($bits > 0) {
            $encoded .= $alphabet[($buffer << (5 - $bits)) & 31];
        }
        
        return $encoded;
    }
    
    /**
     * Base32 decode
     */
    private static function base32Decode($data) 
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base = strlen($alphabet);
        $decoded = '';
        
        $bits = 0;
        $buffer = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = strtoupper($data[$i]);
            $pos = strpos($alphabet, $char);
            
            if ($pos === false) {
                continue;
            }
            
            $buffer = ($buffer << 5) | $pos;
            $bits += 5;
            
            while ($bits >= 8) {
                $bits -= 8;
                $decoded .= chr(($buffer >> $bits) & 255);
            }
        }
        
        return $decoded;
    }
    
    /**
     * Timing safe string comparison
     */
    private static function timingSafeEquals($safeString, $userString) 
    {
        if (function_exists('hash_equals')) {
            return hash_equals($safeString, $userString);
        }
        
        $safeLen = strlen($safeString);
        $userLen = strlen($userString);
        
        if ($userLen != $safeLen) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < $userLen; $i++) {
            $result |= (ord($safeString[$i]) ^ ord($userString[$i]));
        }
        
        return $result === 0;
    }
    
    /**
     * Verify backup code
     */
    public static function verifyBackupCode($backupCodes, $code) 
    {
        if (empty($backupCodes)) {
            return false;
        }
        
        $codes = json_decode($backupCodes, true);
        if (!is_array($codes)) {
            return false;
        }
        
        $index = array_search($code, $codes);
        if ($index !== false) {
            // Remove used backup code
            unset($codes[$index]);
            return json_encode(array_values($codes));
        }
        
        return false;
    }
} 