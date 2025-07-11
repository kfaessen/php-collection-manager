<?php
/**
 * User Profile - Profiel beheer
 */

require_once '../includes/functions.php';

// Alleen toegankelijk voor ingelogde gebruikers
Authentication::requireLogin();

$currentUser = Authentication::getCurrentUser();
$feedback = '';

// Formulierverwerking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $userData = [
            'username' => Utils::sanitize($_POST['username']),
            'email' => Utils::sanitize($_POST['email']),
            'first_name' => Utils::sanitize($_POST['first_name']),
            'last_name' => Utils::sanitize($_POST['last_name'])
        ];
        
        $result = UserManager::updateUser($currentUser['id'], $userData);
        if ($result['success']) {
            $feedback = 'Profiel succesvol bijgewerkt!';
            // Refresh current user data
            $currentUser = Authentication::getCurrentUser();
        } else {
            $feedback = $result['message'];
        }
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($currentPassword, $currentUser['password_hash'])) {
            $feedback = 'Huidig wachtwoord is onjuist';
        } elseif ($newPassword !== $confirmPassword) {
            $feedback = 'Nieuwe wachtwoorden komen niet overeen';
        } else {
            $result = UserManager::changePassword($currentUser['id'], $newPassword);
            if ($result['success']) {
                $feedback = 'Wachtwoord succesvol gewijzigd!';
            } else {
                $feedback = $result['message'];
            }
        }
    }
}

// Get user groups
$userGroups = UserManager::getUserGroups($currentUser['id']);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiel - Collectiebeheer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .profile-header { margin-bottom: 30px; }
        .profile-card { border: none; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .profile-avatar { width: 100px; height: 100px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #6c757d; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-collection"></i> Collectiebeheer
            </a>
            <div class="d-flex">
                <a href="index.php" class="btn btn-outline-light me-2"><i class="bi bi-house"></i> Terug</a>
                <a href="logout.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-right"></i> Uitloggen</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="profile-header text-center">
            <h2><i class="bi bi-person"></i> Mijn Profiel</h2>
            <p class="text-muted">Beheer uw persoonlijke gegevens en instellingen</p>
        </div>
        
        <?php if ($feedback): ?>
            <div class="alert alert-info"><?= htmlspecialchars($feedback) ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Profiel Informatie -->
            <div class="col-md-8">
                <div class="card profile-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-circle"></i> Profiel Informatie</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="#">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Gebruikersnaam</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">Voornaam</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($currentUser['first_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Achternaam</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($currentUser['last_name']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Profiel Bijwerken
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Wachtwoord Wijzigen -->
                <div class="card profile-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lock"></i> Wachtwoord Wijzigen</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="#">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Huidig Wachtwoord</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nieuw Wachtwoord</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <small class="text-muted">Minimaal 8 tekens, 1 hoofdletter, 1 kleine letter, 1 cijfer</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Bevestig Nieuw Wachtwoord</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="bi bi-key"></i> Wachtwoord Wijzigen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Profiel Overzicht -->
                <div class="card profile-card mb-4">
                    <div class="card-body text-center">
                        <div class="profile-avatar mx-auto mb-3">
                            <i class="bi bi-person"></i>
                        </div>
                        <h5><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></h5>
                        <p class="text-muted">@<?= htmlspecialchars($currentUser['username']) ?></p>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <h6 class="mb-0"><?= count($userGroups) ?></h6>
                                <small class="text-muted">Groepen</small>
                            </div>
                            <div class="col-6">
                                <h6 class="mb-0"><?= $currentUser['last_login'] ? date('d-m-Y', strtotime($currentUser['last_login'])) : 'Nooit' ?></h6>
                                <small class="text-muted">Laatste login</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Groepen -->
                <div class="card profile-card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-shield-lock"></i> Mijn Groepen</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userGroups)): ?>
                            <p class="text-muted small">U bent nog niet toegewezen aan groepen.</p>
                        <?php else: ?>
                            <?php foreach ($userGroups as $group): ?>
                                <span class="badge bg-primary me-1 mb-1"><?= htmlspecialchars($group['name']) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Account Status -->
                <div class="card profile-card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> Account Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Status:</span>
                            <?php if ($currentUser['is_active']): ?>
                                <span class="badge bg-success">Actief</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Gedeactiveerd</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>TOTP:</span>
                            <?php if ($currentUser['totp_enabled']): ?>
                                <span class="badge bg-success">Ingeschakeld</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Uitgeschakeld</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Lid sinds:</span>
                            <small class="text-muted"><?= date('d-m-Y', strtotime($currentUser['created_at'])) ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html>