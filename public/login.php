<?php
/**
 * Login Page
 */

// Include dependencies
require_once '../includes/functions.php';

// Check if setup is needed
if (Database::needsSetup()) {
    header('Location: setup.php');
    exit;
}

// If already logged in, redirect to main app
if (Authentication::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$requiresTOTP = false;
$username = '';
$password = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['totp_code'])) {
        // TOTP verification step
        $username = $_POST['username'];
        $password = $_POST['password'];
        $totpCode = Utils::sanitize($_POST['totp_code']);
        
        $result = Authentication::login($username, $password, $totpCode);
        
        if ($result['success']) {
            // Login successful, redirect to main app
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
            $requiresTOTP = true;
        }
    } else {
        // Initial login step
        $username = Utils::sanitize($_POST['username']);
        $password = $_POST['password'];
        
        if (!empty($username) && !empty($password)) {
            $result = Authentication::login($username, $password);
            
            if ($result['success']) {
                // Login successful, redirect to main app
                header('Location: index.php');
                exit;
            } else {
                if (isset($result['requires_totp']) && $result['requires_totp']) {
                    $requiresTOTP = true;
                    $error = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        } else {
            $error = 'Vul alle velden in';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - Collectiebeheer</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #0d6efd;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #6c757d;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .login-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-links a {
            color: #0d6efd;
            text-decoration: none;
        }
        
        .login-links a:hover {
            text-decoration: underline;
        }
        
        .alert {
            margin-bottom: 20px;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .form-control:focus {
            border-left: none;
            box-shadow: none;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: #86b7fe;
        }
        
        .password-toggle {
            cursor: pointer;
            background: none;
            border: none;
            color: #6c757d;
        }
        
        .password-toggle:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <div class="login-container">
        <div class="login-header">
            <h1><i class="bi bi-collection"></i> Collectiebeheer</h1>
            <p>Log in om uw collectie te beheren</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['setup']) && $_GET['setup'] === 'complete'): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle"></i> Setup voltooid! Welkom bij Collectiebeheer.
            </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="username" class="form-label">Gebruikersnaam of Email</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($username) ?>" 
                           placeholder="Voer uw gebruikersnaam of email in" required
                           <?= $requiresTOTP ? 'readonly' : '' ?>>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Wachtwoord</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" 
                           value="<?= htmlspecialchars($password) ?>"
                           placeholder="Voer uw wachtwoord in" required
                           <?= $requiresTOTP ? 'readonly' : '' ?>>
                    <?php if (!$requiresTOTP): ?>
                        <button type="button" class="btn btn-outline-secondary password-toggle" 
                                onclick="togglePassword()">
                            <i class="bi bi-eye" id="passwordToggleIcon"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($requiresTOTP): ?>
                <div class="form-group">
                    <label for="totp_code" class="form-label">Twee-factor authenticatie code</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="text" class="form-control" id="totp_code" name="totp_code" 
                               placeholder="000000" maxlength="6" pattern="[0-9]{6}" required
                               autocomplete="off">
                    </div>
                    <div class="form-text">
                        Voer de 6-cijferige code in van je authenticator app, of een backup code.
                    </div>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary btn-login">
                <i class="bi bi-box-arrow-in-right"></i> 
                <?= $requiresTOTP ? 'Verifiëren' : 'Inloggen' ?>
            </button>
        </form>
        
        <div class="login-links">
            <small class="text-muted">
                Beheerder? <a href="admin.php">Beheer toegang</a>
            </small>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
        
        // Focus on appropriate field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const totpField = document.getElementById('totp_code');
            if (totpField) {
                totpField.focus();
            } else {
                document.getElementById('username').focus();
            }
        });
        
        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const totpField = document.getElementById('totp_code');
            
            if (!username || !password) {
                e.preventDefault();
                alert('Vul alle velden in');
                return;
            }
            
            if (totpField && !totpField.value.trim()) {
                e.preventDefault();
                alert('Voer de twee-factor authenticatie code in');
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Bezig met inloggen...';
            submitButton.disabled = true;
            
            // Re-enable button after 3 seconds if form wasn't submitted
            setTimeout(() => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 3000);
        });
        
        // Handle Enter key in fields
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });
        
        const totpField = document.getElementById('totp_code');
        if (totpField) {
            totpField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('loginForm').dispatchEvent(new Event('submit'));
                }
            });
        }
    </script>
</body>
</html> 