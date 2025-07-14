<?php
/**
 * Email Verification Endpoint
 * Handles email verification via tokens sent to users
 */

// Include dependencies
require_once '../includes/functions.php';

// Check if setup is needed
if (Database::needsSetup()) {
    header('Location: setup.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$expired = false;

if (empty($token)) {
    $error = 'Geen verificatie token opgegeven.';
} else {
    // Verify the email using the token
    $result = EmailVerificationHelper::verifyEmail($token);
    
    if ($result['success']) {
        $success = $result['message'];
        
        // Auto-login user after successful verification
        // Get user by token first
        $tokensTable = Environment::getTableName('email_verification_tokens');
        $sql = "SELECT user_id FROM `$tokensTable` WHERE token = ? AND verified_at IS NOT NULL";
        $stmt = Database::query($sql, [$token]);
        $tokenData = $stmt->fetch();
        
        if ($tokenData) {
            $usersTable = Environment::getTableName('users');
            $sql = "SELECT * FROM `$usersTable` WHERE id = ?";
            $stmt = Database::query($sql, [$tokenData['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                // Set session for auto-login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = Authentication::userHasPermission($user['id'], 'system_admin');
                
                // Update last login
                $usersTable = Environment::getTableName('users');
                $sql = "UPDATE `$usersTable` SET last_login = NOW() WHERE id = ?";
                Database::query($sql, [$user['id']]);
            }
        }
    } else {
        $error = $result['message'];
        $expired = $result['expired'] ?? false;
    }
}

// Get user info for resend functionality
$userId = null;
$userEmail = null;
if ($expired && !empty($token)) {
    $tokensTable = Environment::getTableName('email_verification_tokens');
    $sql = "SELECT user_id, email FROM `$tokensTable` WHERE token = ?";
    $stmt = Database::query($sql, [$token]);
    $tokenData = $stmt->fetch();
    
    if ($tokenData) {
        $userId = $tokenData['user_id'];
        $userEmail = $tokenData['email'];
    }
}

// Handle resend verification email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
    $resendUserId = $_POST['user_id'] ?? '';
    $resendEmail = $_POST['email'] ?? '';
    
    if (!empty($resendUserId) && !empty($resendEmail)) {
        $resendResult = EmailVerificationHelper::sendVerificationEmail($resendUserId, $resendEmail, true);
        
        if ($resendResult['success']) {
            $success = 'Een nieuwe verificatie email is verzonden naar ' . htmlspecialchars($resendEmail);
            $error = '';
        } else {
            $error = 'Fout bij verzenden nieuwe verificatie email: ' . $resendResult['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verificatie - Collectiebeheer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .verification-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .verification-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .verification-body {
            padding: 40px 30px;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .verification-actions {
            margin-top: 30px;
            text-align: center;
        }
        .btn-resend {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-resend:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .features-list {
            margin: 20px 0;
            padding-left: 0;
            list-style: none;
        }
        .features-list li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }
        .features-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="verification-card">
                        <div class="verification-header">
                            <h1><i class="bi bi-envelope-check"></i> Email Verificatie</h1>
                            <p class="mb-0">Collectiebeheer Account Activatie</p>
                        </div>
                        
                        <div class="verification-body">
                            <?php if ($success): ?>
                                <div class="text-center">
                                    <div class="success-icon">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                    <h3 class="text-success mb-3">Email Geverifieerd!</h3>
                                    <div class="alert alert-success">
                                        <?= htmlspecialchars($success) ?>
                                    </div>
                                    
                                    <p>Je account is nu volledig actief. Je kunt genieten van alle functies:</p>
                                    
                                    <ul class="features-list">
                                        <li>Volledige toegang tot je collectie</li>
                                        <li>Email notificaties ontvangen</li>
                                        <li>Je collectie delen met anderen</li>
                                        <li>Push notificaties inschakelen</li>
                                        <li>API integraties gebruiken</li>
                                    </ul>
                                    
                                    <div class="verification-actions">
                                        <a href="index.php" class="btn btn-primary btn-lg">
                                            <i class="bi bi-house"></i> Ga naar Dashboard
                                        </a>
                                    </div>
                                </div>
                                
                            <?php elseif ($error): ?>
                                <div class="text-center">
                                    <div class="error-icon">
                                        <i class="bi bi-exclamation-circle-fill"></i>
                                    </div>
                                    <h3 class="text-danger mb-3">Verificatie Mislukt</h3>
                                    <div class="alert alert-danger">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                    
                                    <?php if ($expired && $userId && $userEmail): ?>
                                        <p>De verificatie link is verlopen, maar je kunt een nieuwe aanvragen:</p>
                                        
                                        <form method="POST" class="mt-3">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                                            <input type="hidden" name="email" value="<?= htmlspecialchars($userEmail) ?>">
                                            <button type="submit" name="resend_verification" class="btn btn-resend">
                                                <i class="bi bi-envelope-arrow-up"></i> Nieuwe Verificatie Email Versturen
                                            </button>
                                        </form>
                                        
                                    <?php else: ?>
                                        <p>Mogelijke oplossingen:</p>
                                        <ul class="text-start">
                                            <li>Controleer of de link volledig is gekopieerd</li>
                                            <li>Probeer de nieuwste email in je inbox</li>
                                            <li>Controleer je spam/ongewenste email folder</li>
                                            <li>Neem contact op met de beheerder</li>
                                        </ul>
                                    <?php endif; ?>
                                    
                                    <div class="verification-actions">
                                        <a href="login.php" class="btn btn-outline-primary">
                                            <i class="bi bi-arrow-left"></i> Terug naar Inloggen
                                        </a>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <div class="text-center">
                                    <div class="error-icon">
                                        <i class="bi bi-question-circle-fill"></i>
                                    </div>
                                    <h3 class="text-warning mb-3">Geen Token</h3>
                                    <div class="alert alert-warning">
                                        Er is geen verificatie token opgegeven. 
                                    </div>
                                    
                                    <p>Als je een verificatie email hebt ontvangen, klik dan op de link in die email of kopieer de volledige URL.</p>
                                    
                                    <div class="verification-actions">
                                        <a href="login.php" class="btn btn-outline-primary">
                                            <i class="bi bi-arrow-left"></i> Terug naar Inloggen
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-white">
                            <small>
                                Heb je vragen? <a href="mailto:support@collectiebeheer.app" class="text-white"><u>Neem contact op</u></a>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-redirect to dashboard after successful verification
        <?php if ($success): ?>
        setTimeout(function() {
            if (confirm('Wil je nu naar het dashboard gaan?')) {
                window.location.href = 'index.php';
            }
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html> 