<?php

/**
 * EmailVerificationHelper - Email Verification Management
 * Handles email verification tokens, sending verification emails, and verification process
 */

class EmailVerificationHelper {
    
    /**
     * Generate and send email verification token
     */
    public static function sendVerificationEmail($userId, $email, $resend = false) {
        try {
            // Check if email functionality is available
            if (!MailHelper::isAvailable()) {
                error_log('Email verification: Mail functionality not available');
                return ['success' => false, 'message' => 'Email functionaliteit is niet beschikbaar'];
            }
            
            // Get user info
            $usersTable = Environment::getTableName('users');
            $sql = "SELECT username, first_name, last_name FROM `$usersTable` WHERE id = ?";
            $stmt = Database::query($sql, [$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Gebruiker niet gevonden'];
            }
            
            // Generate verification token
            $token = self::generateVerificationToken($userId, $email);
            
            if (!$token) {
                return ['success' => false, 'message' => 'Fout bij genereren verificatie token'];
            }
            
            // Create verification URL
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? Environment::get('APP_URL', 'localhost');
            $verificationUrl = "$protocol://$host" . dirname($_SERVER['SCRIPT_NAME'] ?? '') . "/verify-email.php?token=$token";
            
            // Email content
            $subject = $resend ? 'Email verificatie - Nieuwe link' : 'Welkom! Bevestig je email adres';
            
            $body = self::getVerificationEmailTemplate([
                'username' => $user['username'],
                'first_name' => $user['first_name'],
                'verification_url' => $verificationUrl,
                'resend' => $resend
            ]);
            
            // Send email
            $emailSent = MailHelper::sendMail($email, $subject, $body);
            
            if ($emailSent) {
                // Mark reminder as sent if this is a resend
                if ($resend) {
                    $sql = "UPDATE `$usersTable` SET verification_reminder_sent = TRUE WHERE id = ?";
                    Database::query($sql, [$userId]);
                }
                
                return ['success' => true, 'message' => 'Verificatie email verzonden'];
            } else {
                return ['success' => false, 'message' => 'Fout bij verzenden email'];
            }
            
        } catch (Exception $e) {
            error_log('Email verification error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Onverwachte fout bij verzenden verificatie email'];
        }
    }
    
    /**
     * Generate verification token for user
     */
    public static function generateVerificationToken($userId, $email) {
        try {
            // Remove existing tokens for this user
            self::cleanupUserTokens($userId);
            
            // Generate secure token
            $token = bin2hex(random_bytes(64)); // 128 character token
            
            // Token expires in 24 hours
            $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60));
            
            // Store token in database
            $tokensTable = Environment::getTableName('email_verification_tokens');
            $sql = "INSERT INTO `$tokensTable` (user_id, token, email, expires_at) VALUES (?, ?, ?, ?)";
            
            Database::query($sql, [$userId, $token, $email, $expiresAt]);
            
            return $token;
            
        } catch (Exception $e) {
            error_log('Generate verification token error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify email using token
     */
    public static function verifyEmail($token) {
        try {
            $tokensTable = Environment::getTableName('email_verification_tokens');
            $usersTable = Environment::getTableName('users');
            
            // Get token details
            $sql = "SELECT user_id, email, expires_at FROM `$tokensTable` 
                    WHERE token = ? AND verified_at IS NULL";
            $stmt = Database::query($sql, [$token]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                return ['success' => false, 'message' => 'Ongeldige of verlopen verificatie token'];
            }
            
            // Check if token is expired
            if (strtotime($tokenData['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Verificatie token is verlopen', 'expired' => true];
            }
            
            // Begin transaction
            $connection = Database::getConnection();
            $connection->beginTransaction();
            
            try {
                // Mark token as verified
                $sql = "UPDATE `$tokensTable` SET verified_at = NOW() WHERE token = ?";
                Database::query($sql, [$token]);
                
                // Update user as verified
                $sql = "UPDATE `$usersTable` SET email_verified = TRUE, email_verified_at = NOW() WHERE id = ?";
                Database::query($sql, [$tokenData['user_id']]);
                
                // Commit transaction
                $connection->commit();
                
                // Send welcome notification if available
                if (class_exists('NotificationHelper') && NotificationHelper::isAvailable()) {
                    NotificationHelper::sendToUser(
                        $tokenData['user_id'],
                        'Email geverifieerd!',
                        'Je email adres is succesvol bevestigd. Welkom bij Collectiebeheer!',
                        ['type' => 'email_verified'],
                        ['tag' => 'email_verification', 'requireInteraction' => true]
                    );
                }
                
                return ['success' => true, 'message' => 'Email succesvol geverifieerd!'];
                
            } catch (Exception $e) {
                $connection->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('Email verification error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Fout bij verifiëren email'];
        }
    }
    
    /**
     * Check if user email is verified
     */
    public static function isEmailVerified($userId) {
        try {
            $usersTable = Environment::getTableName('users');
            $sql = "SELECT email_verified FROM `$usersTable` WHERE id = ?";
            $stmt = Database::query($sql, [$userId]);
            $user = $stmt->fetch();
            
            return $user ? (bool)$user['email_verified'] : false;
            
        } catch (Exception $e) {
            error_log('Check email verified error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pending verification token for user
     */
    public static function getPendingToken($userId) {
        try {
            $tokensTable = Environment::getTableName('email_verification_tokens');
            $sql = "SELECT token, expires_at FROM `$tokensTable` 
                    WHERE user_id = ? AND verified_at IS NULL AND expires_at > NOW()
                    ORDER BY created_at DESC LIMIT 1";
            $stmt = Database::query($sql, [$userId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log('Get pending token error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up expired tokens
     */
    public static function cleanupExpiredTokens() {
        try {
            $tokensTable = Environment::getTableName('email_verification_tokens');
            $sql = "DELETE FROM `$tokensTable` WHERE expires_at < NOW()";
            Database::query($sql);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Cleanup expired tokens error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up tokens for specific user
     */
    public static function cleanupUserTokens($userId) {
        try {
            $tokensTable = Environment::getTableName('email_verification_tokens');
            $sql = "DELETE FROM `$tokensTable` WHERE user_id = ?";
            Database::query($sql, [$userId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Cleanup user tokens error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users needing verification reminder
     */
    public static function getUsersNeedingReminder($hours = 72) {
        try {
            $usersTable = Environment::getTableName('users');
            $sql = "SELECT id, username, email, first_name, last_name 
                    FROM `$usersTable` 
                    WHERE email_verified = FALSE 
                    AND verification_reminder_sent = FALSE 
                    AND registration_method = 'local'
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
                    AND is_active = TRUE";
            
            $stmt = Database::query($sql, [$hours]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log('Get users needing reminder error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send verification reminders to users who haven't verified
     */
    public static function sendVerificationReminders() {
        $users = self::getUsersNeedingReminder();
        $sent = 0;
        $failed = 0;
        
        foreach ($users as $user) {
            $result = self::sendVerificationEmail($user['id'], $user['email'], true);
            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }
        }
        
        return ['sent' => $sent, 'failed' => $failed, 'total' => count($users)];
    }
    
    /**
     * Manually verify user (admin function)
     */
    public static function manuallyVerifyUser($userId) {
        try {
            $usersTable = Environment::getTableName('users');
            $sql = "UPDATE `$usersTable` 
                    SET email_verified = TRUE, email_verified_at = NOW() 
                    WHERE id = ?";
            
            Database::query($sql, [$userId]);
            
            // Clean up any pending tokens
            self::cleanupUserTokens($userId);
            
            return ['success' => true, 'message' => 'Gebruiker handmatig geverifieerd'];
            
        } catch (Exception $e) {
            error_log('Manual verify user error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Fout bij handmatig verifiëren'];
        }
    }
    
    /**
     * Get verification statistics
     */
    public static function getVerificationStats() {
        try {
            $usersTable = Environment::getTableName('users');
            $tokensTable = Environment::getTableName('email_verification_tokens');
            
            // Total users
            $sql = "SELECT COUNT(*) as total FROM `$usersTable` WHERE registration_method = 'local'";
            $stmt = Database::query($sql);
            $total = $stmt->fetch()['total'];
            
            // Verified users
            $sql = "SELECT COUNT(*) as verified FROM `$usersTable` 
                    WHERE registration_method = 'local' AND email_verified = TRUE";
            $stmt = Database::query($sql);
            $verified = $stmt->fetch()['verified'];
            
            // Pending verification
            $sql = "SELECT COUNT(*) as pending FROM `$usersTable` 
                    WHERE registration_method = 'local' AND email_verified = FALSE";
            $stmt = Database::query($sql);
            $pending = $stmt->fetch()['pending'];
            
            // Active tokens
            $sql = "SELECT COUNT(*) as active_tokens FROM `$tokensTable` 
                    WHERE verified_at IS NULL AND expires_at > NOW()";
            $stmt = Database::query($sql);
            $activeTokens = $stmt->fetch()['active_tokens'];
            
            return [
                'total_local_users' => $total,
                'verified_users' => $verified,
                'pending_verification' => $pending,
                'verification_rate' => $total > 0 ? round(($verified / $total) * 100, 1) : 0,
                'active_tokens' => $activeTokens
            ];
            
        } catch (Exception $e) {
            error_log('Get verification stats error: ' . $e->getMessage());
            return [
                'total_local_users' => 0,
                'verified_users' => 0,
                'pending_verification' => 0,
                'verification_rate' => 0,
                'active_tokens' => 0
            ];
        }
    }
    
    /**
     * Get email verification template
     */
    private static function getVerificationEmailTemplate($params) {
        $username = htmlspecialchars($params['username']);
        $firstName = htmlspecialchars($params['first_name']);
        $verificationUrl = htmlspecialchars($params['verification_url']);
        $resend = $params['resend'] ?? false;
        
        if ($resend) {
            $greeting = "Beste $firstName,";
            $intro = "Je hebt een nieuwe email verificatie link aangevraagd.";
        } else {
            $greeting = "Welkom $firstName!";
            $intro = "Bedankt voor je registratie bij Collectiebeheer. Om je account te activeren, moet je eerst je email adres bevestigen.";
        }
        
        return "
        <!DOCTYPE html>
        <html lang='nl'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verificatie - Collectiebeheer</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 28px; font-weight: bold; color: #007bff; margin-bottom: 10px; }
                .subtitle { color: #666; font-size: 16px; }
                .content { margin: 20px 0; }
                .verification-button { display: inline-block; background-color: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .verification-button:hover { background-color: #0056b3; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 14px; color: #666; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>📚 Collectiebeheer</div>
                    <div class='subtitle'>Je persoonlijke collectie manager</div>
                </div>
                
                <div class='content'>
                    <h2>Email Verificatie</h2>
                    <p>$greeting</p>
                    <p>$intro</p>
                    
                    <p>Klik op de onderstaande knop om je email adres te bevestigen:</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$verificationUrl' class='verification-button'>Email Adres Bevestigen</a>
                    </div>
                    
                    <div class='warning'>
                        <strong>Let op:</strong> Deze link is 24 uur geldig. Als de link niet werkt, kopieer dan de volgende URL en plak deze in je browser:<br>
                        <code>$verificationUrl</code>
                    </div>
                    
                    <p>Nadat je je email hebt bevestigd, kun je:</p>
                    <ul>
                        <li>✅ Volledig toegang krijgen tot alle functies</li>
                        <li>📧 Email notificaties ontvangen</li>
                        <li>🔗 Je collectie delen met anderen</li>
                        <li>🔔 Push notificaties ontvangen (optioneel)</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p><strong>Heb je deze email niet verwacht?</strong></p>
                    <p>Als je je niet hebt geregistreerd bij Collectiebeheer, kun je deze email veilig negeren. Je email adres wordt niet toegevoegd aan onze database zonder bevestiging.</p>
                    
                    <hr style='margin: 20px 0; border: none; border-top: 1px solid #eee;'>
                    
                    <p style='text-align: center; margin: 0;'>
                        <strong>Collectiebeheer Team</strong><br>
                        <small>© " . date('Y') . " Collectiebeheer. Alle rechten voorbehouden.</small>
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
} 