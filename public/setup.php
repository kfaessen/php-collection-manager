<?php
/**
 * Setup Wizard - First time installation
 */

// Include dependencies
require_once '../includes/functions.php';

// Check if setup is needed
if (!Database::needsSetup()) {
    // Setup already completed, redirect to main app
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup') {
    $username = Utils::sanitize($_POST['username']);
    $email = Utils::sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = Utils::sanitize($_POST['first_name']);
    $lastName = Utils::sanitize($_POST['last_name']);
    
    $errors = [];
    
    // Validate input
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Gebruikersnaam moet minimaal 3 tekens bevatten';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geldig email adres is verplicht';
    }
    
    if (empty($firstName)) {
        $errors[] = 'Voornaam is verplicht';
    }
    
    if (empty($lastName)) {
        $errors[] = 'Achternaam is verplicht';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Wachtwoorden komen niet overeen';
    }
    
    // Validate password strength
    $passwordValidation = Authentication::validatePassword($password);
    if (!$passwordValidation['valid']) {
        $errors[] = $passwordValidation['message'];
    }
    
    if (empty($errors)) {
        // Create admin user (skip email verification for setup)
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'skip_verification' => true
        ];
        
        $result = Authentication::register($userData);
        
        if ($result['success']) {
            // Add user to admin group
            $usersTable = Environment::getTableName('users');
            $groupsTable = Environment::getTableName('groups');
            $userGroupsTable = Environment::getTableName('user_groups');
            
            $sql = "INSERT INTO `$userGroupsTable` (user_id, group_id) 
                    SELECT ?, g.id FROM `$groupsTable` g WHERE g.name = 'admin'";
            
            Database::query($sql, [$result['user_id']]);
            
            // Manually verify email for setup user (since they're admin)
            if (class_exists('\CollectionManager\EmailVerificationHelper')) {
                \CollectionManager\EmailVerificationHelper::manuallyVerifyUser($result['user_id']);
            }
            
            // Auto-login the new admin
            Authentication::login($username, $password);
            
            // Redirect to main app
            header('Location: index.php?setup=complete');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Collectiebeheer</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .setup-header h1 {
            color: #0d6efd;
            margin-bottom: 10px;
        }
        
        .setup-header p {
            color: #6c757d;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .btn-setup {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">
    <div class="setup-container">
        <div class="setup-header">
            <h1><i class="bi bi-gear-fill"></i> Setup Collectiebeheer</h1>
            <p>Welkom! Om te beginnen moet u een administrator account aanmaken.</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Fout:</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="setup">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="first_name" class="form-label">Voornaam *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="last_name" class="form-label">Achternaam *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="username" class="form-label">Gebruikersnaam *</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                <div class="form-text">Minimaal 3 tekens, alleen letters, cijfers en underscores</div>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email adres *</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                <div class="form-text">Dit wordt gebruikt voor inloggen en notificaties</div>
            </div>
            
            <div class="password-requirements">
                <strong>Wachtwoord vereisten:</strong>
                <ul>
                    <li>Minimaal 8 tekens</li>
                    <li>Minimaal 1 hoofdletter</li>
                    <li>Minimaal 1 kleine letter</li>
                    <li>Minimaal 1 cijfer</li>
                    <li>Minimaal 1 speciaal teken (!@#$%^&*)</li>
                </ul>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Wachtwoord *</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Bevestig wachtwoord *</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-setup">
                <i class="bi bi-check-circle"></i> Administrator Account Aanmaken
            </button>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                Na aanmaken wordt u automatisch ingelogd en kunt u beginnen met het beheren van uw collectie.
            </small>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Real-time password validation
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Check password strength
            const requirements = [
                { regex: /.{8,}/, text: 'Minimaal 8 tekens' },
                { regex: /[A-Z]/, text: 'Minimaal 1 hoofdletter' },
                { regex: /[a-z]/, text: 'Minimaal 1 kleine letter' },
                { regex: /[0-9]/, text: 'Minimaal 1 cijfer' },
                { regex: /[^A-Za-z0-9]/, text: 'Minimaal 1 speciaal teken' }
            ];
            
            // Visual feedback could be added here
        });
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Wachtwoorden komen niet overeen');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html> 