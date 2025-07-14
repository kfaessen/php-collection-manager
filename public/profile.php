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
    $userPerms = [];
    foreach ($userGroups as $g) {
        foreach (UserManager::getGroupPermissions($g['id']) as $p) {
            $userPerms[$p['name']] = $p['description'];
        }
    }
    
    // Haal items van gebruiker op
    $items = CollectionManager::getItems('', '', 1000, 0, $currentUser['id']);
} catch (Exception $e) {
    $userGroups = [];
    $userPerms = [];
    $items = [];
    $feedback = 'Fout bij ophalen gebruikersgegevens: ' . $e->getMessage();
}

// Handle form submissions
if (empty($feedback)) {
    $feedback = '';
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        try {
            $userData = [
                'first_name' => Utils::sanitize($_POST['first_name']),
                'last_name' => Utils::sanitize($_POST['last_name']),
                'email' => Utils::sanitize($_POST['email'])
            ];
            
            // Valideer e-mail
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Ongeldig e-mailadres';
            } else {
                $result = UserManager::updateUser($currentUser['id'], $userData);
                if ($result['success']) {
                    $feedback = 'Profiel succesvol bijgewerkt';
                    // Refresh user data
                    $currentUser = Authentication::getCurrentUser();
                } else {
                    $error = $result['message'];
                }
            }
        } catch (Exception $e) {
            $error = 'Fout bij bijwerken profiel: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['change_password'])) {
        try {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Valideer wachtwoorden
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = 'Alle wachtwoordvelden zijn verplicht';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Nieuwe wachtwoorden komen niet overeen';
            } else {
                // Valideer wachtwoord sterkte
                $passwordValidation = Authentication::validatePassword($newPassword);
                if (!$passwordValidation['valid']) {
                    $error = $passwordValidation['message'];
                } else {
                    // Controleer huidig wachtwoord
                    $loginResult = Authentication::login($currentUser['username'], $currentPassword);
                    if (!$loginResult['success']) {
                        $error = 'Huidig wachtwoord is incorrect';
                    } else {
                        $result = UserManager::changePassword($currentUser['id'], $newPassword);
                        if ($result['success']) {
                            $feedback = 'Wachtwoord succesvol gewijzigd';
                        } else {
                            $error = $result['message'];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error = 'Fout bij wijzigen wachtwoord: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['unlink_provider'])) {
        try {
            $provider = Utils::sanitize($_POST['unlink_provider']);
            $result = OAuthHelper::unlinkSocialAccount($currentUser['id'], $provider);
            if ($result) {
                $feedback = ucfirst($provider) . ' account succesvol ontkoppeld';
            } else {
                $error = 'Fout bij ontkoppelen van account';
            }
        } catch (Exception $e) {
            $error = 'Fout bij ontkoppelen account: ' . $e->getMessage();
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-collection"></i> Collectiebeheer
            </a>
            <div class="d-flex">
                <a href="index.php" class="btn btn-outline-light me-2"><i class="bi bi-arrow-left"></i> Terug naar overzicht</a>
                <a href="logout.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-right"></i> Uitloggen</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h2><i class="bi bi-person-circle"></i> Mijn Profiel</h2>
                
                <?php if ($feedback): ?>
                    <div class="alert alert-success mt-3"><?= htmlspecialchars($feedback) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <!-- Profielgegevens -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Profielgegevens</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gebruikersnaam</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?>" readonly>
                                    <small class="text-muted">Gebruikersnaam kan niet worden gewijzigd</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">E-mail</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Voornaam</label>
                                    <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($currentUser['first_name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Achternaam</label>
                                    <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($currentUser['last_name']) ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Profiel bijwerken
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Wachtwoord wijzigen -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lock"></i> Wachtwoord wijzigen</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Huidig wachtwoord</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nieuw wachtwoord</label>
                                    <input type="password" class="form-control" name="new_password" required>
                                    <small class="text-muted">Minimaal 8 tekens, hoofdletter, kleine letter, cijfer en speciaal teken</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bevestig nieuw wachtwoord</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="bi bi-key"></i> Wachtwoord wijzigen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Account informatie -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Account informatie</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Account status:</strong> 
                            <?php if (isset($currentUser['is_active']) && $currentUser['is_active']): ?>
                                <span class="badge bg-success">Actief</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactief</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Lid sinds:</strong> <?= Utils::formatDate($currentUser['created_at'] ?? '') ?></p>
                        <p><strong>Laatste login:</strong> <?= $currentUser['last_login'] ? Utils::formatDate($currentUser['last_login']) : 'Nog niet ingelogd' ?></p>
                        
                        <?php if (isset($currentUser['totp_enabled']) && $currentUser['totp_enabled']): ?>
                            <p><strong>Twee-factor authenticatie:</strong> <span class="badge bg-success">Ingeschakeld</span></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Groepen -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Mijn groepen</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userGroups)): ?>
                            <p class="text-muted">Je bent nog niet toegewezen aan groepen.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($userGroups as $group): ?>
                                    <li class="list-group-item">
                                        <i class="bi bi-person-badge"></i> <?= htmlspecialchars($group['name']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Rechten -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-shield-check"></i> Mijn rechten</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($userPerms)): ?>
                            <p class="text-muted">Je hebt nog geen specifieke rechten.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($userPerms as $perm => $desc): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($perm) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($desc) ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Social Accounts -->
                <?php if (OAuthHelper::isEnabled()): ?>
                    <?php $socialAccounts = OAuthHelper::getUserSocialAccounts($currentUser['id']); ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Gekoppelde accounts</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($socialAccounts)): ?>
                                <p class="text-muted">Geen sociale accounts gekoppeld.</p>
                            <?php else: ?>
                                <ul class="list-group list-group-flush mb-3">
                                    <?php foreach ($socialAccounts as $account): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>
                                                    <?php if ($account['provider'] === 'google'): ?>
                                                        <i class="bi bi-google text-danger"></i> Google
                                                    <?php elseif ($account['provider'] === 'facebook'): ?>
                                                        <i class="bi bi-facebook text-primary"></i> Facebook
                                                    <?php else: ?>
                                                        <?= ucfirst($account['provider']) ?>
                                                    <?php endif; ?>
                                                </strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($account['provider_email']) ?></small>
                                            </div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="unlink_provider" value="<?= $account['provider'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Weet je zeker dat je dit account wilt ontkoppelen?')">
                                                    <i class="bi bi-unlink"></i> Ontkoppelen
                                                </button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <?php if (OAuthHelper::isEnabled('google')): ?>
                                    <?php $hasGoogle = false; ?>
                                    <?php foreach ($socialAccounts as $account): ?>
                                        <?php if ($account['provider'] === 'google') $hasGoogle = true; ?>
                                    <?php endforeach; ?>
                                    <?php if (!$hasGoogle): ?>
                                        <a href="oauth.php?action=login&provider=google" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-google"></i> Google koppelen
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (OAuthHelper::isEnabled('facebook')): ?>
                                    <?php $hasFacebook = false; ?>
                                    <?php foreach ($socialAccounts as $account): ?>
                                        <?php if ($account['provider'] === 'facebook') $hasFacebook = true; ?>
                                    <?php endforeach; ?>
                                    <?php if (!$hasFacebook): ?>
                                        <a href="oauth.php?action=login&provider=facebook" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-facebook"></i> Facebook koppelen
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mijn collectie -->
        <div class="row mt-5">
            <div class="col-12">
                <h3><i class="bi bi-collection"></i> Mijn collectie</h3>
                <p class="text-muted">Je hebt <?= count($items) ?> items in je collectie.</p>
                
                <div class="row">
                    <?php if (empty($items)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <h4>Nog geen items in je collectie</h4>
                                <p>Begin met het toevoegen van je eerste item!</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg"></i> Item toevoegen
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($items, 0, 8) as $item): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card h-100 item-card">
                                    <?php if ($item['cover_image']): ?>
                                        <img src="<?= htmlspecialchars($item['cover_image']) ?>" class="card-img-top item-cover" alt="Cover">
                                    <?php else: ?>
                                        <div class="card-img-top placeholder-cover d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image fs-1 text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                        <div class="mb-2">
                                            <span class="badge bg-secondary"><?= ucfirst($item['type']) ?></span>
                                            <?php if ($item['platform']): ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($item['platform']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($item['description']): ?>
                                            <p class="card-text text-muted small flex-grow-1">
                                                <?= htmlspecialchars(substr($item['description'], 0, 100)) ?>
                                                <?= strlen($item['description']) > 100 ? '...' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="mt-auto">
                                            <small class="text-muted">
                                                Toegevoegd: <?= Utils::formatDate($item['created_at']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($items) > 8): ?>
                            <div class="col-12 text-center">
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-right"></i> Bekijk alle items
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html>
</html>