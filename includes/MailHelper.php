<?php
namespace CollectionManager;

class MailHelper
{
    public static function sendMail($to, $subject, $body, $altBody = '')
    {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log('PHPMailer not available - mail functionality disabled');
            return false;
        }
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = Environment::get('SMTP_HOST');
            $mail->Port = Environment::get('SMTP_PORT', 587);
            $mail->SMTPAuth = true;
            $mail->Username = Environment::get('SMTP_USER');
            $mail->Password = Environment::get('SMTP_PASS');
            $mail->SMTPSecure = Environment::get('SMTP_SECURE', 'tls');
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(Environment::get('SMTP_FROM'), Environment::get('SMTP_FROM_NAME', 'Collectiebeheer'));
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if mail functionality is available
     */
    public static function isAvailable()
    {
        return class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
} 