<?php
namespace CollectionManager;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    public static function sendMail($to, $subject, $body, $altBody = '')
    {
        $mail = new PHPMailer(true);
        try {
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
        } catch (Exception $e) {
            error_log('Mail error: ' . $mail->ErrorInfo);
            return false;
        }
    }
} 