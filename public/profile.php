<?php
/**
 * User Profile Page
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../includes/functions.php';
} catch (Exception $e) {
    die("Fout bij laden van functies: " . $e->getMessage());
}

// Require login with error handling
try {
    Authentication::requireLogin();
} catch (Exception $e) {
    die("Authenticatie fout: " . $e->getMessage());
}

$currentUser = Authentication::getCurrentUser();
if (!$currentUser) {
    die("Geen gebruiker gevonden");
}

try {
    $userGroups = UserManager::getUserGroups($currentUser['id']);
} catch (Exception $e) {
    $userGroups = [];
    $feedback = 'Fout bij ophalen gebruikersgroepen: ' . $e->getMessage();
}

// Handle form submissions
if (empty($feedback)) {
    $feedback = '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        try {
            $userData = [
                'first_name' => Utils::sanitize($_POST['first_name']),
                'last_name' => Utils::sanitize($_POST['last_name']),
                'email' => Utils::sanitize($_POST['email'])
            ];
            
            $result = UserManager::updateUser($currentUser['id'], $userData);
            if ($result['success']) {
                $feedback = 'Profiel succesvol bijgewerkt';
                // Refresh user data
                $currentUser = Authentication::getCurrentUser();
            } else {
                $feedback = $result['message'];
            }
        } catch (Exception $e) {
            $feedback = 'Fout bij bijwerken profiel: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['change_password'])) {
        try {
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
                    $feedback = 'Wachtwoord succesvol gewijzigd';
                } else {
                    $feedback = $result['message'];
                }
            }
        } catch (Exception $e) {
            $feedback = 'Fout bij wijzigen wachtwoord: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Profiel - Collectiebeheer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="bi bi-person-circle"></i> Mijn Profiel</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($feedback): ?>
                            <div class="alert alert-info"><?= htmlspecialchars($feedback) ?></div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Profiel Informatie</h5>
                                <form method="POST" action="#">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Gebruikersnaam</label>
                                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">Voornaam</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($currentUser['first_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Achternaam</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($currentUser['last_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" required>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">Profiel Bijwerken</button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Wachtwoord Wijzigen</h5>
                                <form method="POST" action="#">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Huidig Wachtwoord</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nieuw Wachtwoord</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Bevestig Nieuw Wachtwoord</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-warning">Wachtwoord Wijzigen</button>
                                </form>
                                
                                <hr>
                                
                                <h5>Account Informatie</h5>
                                <p><strong>Lid sinds:</strong> <?= Utils::formatDate($currentUser['created_at'] ?? '') ?></p>
                                <p><strong>Laatste login:</strong> <?= $currentUser['last_login'] ? Utils::formatDate($currentUser['last_login']) : 'Nog niet ingelogd' ?></p>
                                <p><strong>Status:</strong> 
                                    <?php if (isset($currentUser['is_active']) && $currentUser['is_active']): ?>
                                        <span class="badge bg-success">Actief</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Geblokkeerd</span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if (!empty($userGroups)): ?>
                                    <p><strong>Groepen:</strong></p>
                                    <div class="mb-2">
                                        <?php foreach ($userGroups as $group): ?>
                                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($group['name']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p><strong>Groepen:</strong> <span class="text-muted">Geen groepen</span></p>
                                <?php endif; ?>
                                
                                <?php if (isset($currentUser['totp_enabled']) && $currentUser['totp_enabled']): ?>
                                    <p><strong>Twee-factor authenticatie:</strong> <span class="badge bg-success">Ingeschakeld</span></p>
                                <?php endif; ?>
                            </div>
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