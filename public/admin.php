<?php
/**
 * Admin Interface - Gebruikers en rechtenbeheer
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../includes/functions.php';
} catch (Exception $e) {
    die("Fout bij laden van functies: " . $e->getMessage());
}

// Alleen toegankelijk voor beheerders met error handling
try {
    Authentication::requirePermission('system_admin');
} catch (Exception $e) {
    die("Toegang geweigerd: " . $e->getMessage());
}

$tab = $_GET['tab'] ?? 'users';
if (!in_array($tab, ['users', 'groups', 'stats', 'verification'])) {
    $tab = 'users';
}

// Statistieken ophalen met error handling
try {
    $userStats = UserManager::getUserStats();
    $allGroups = UserManager::getAllGroups();
    $allPermissions = UserManager::getAllPermissions();
    $verificationStats = EmailVerificationHelper::getVerificationStats();
} catch (Exception $e) {
    $userStats = ['total_users' => 0, 'active_users' => 0, 'new_last_month' => 0];
    $allGroups = [];
    $allPermissions = [];
    $verificationStats = ['total_local_users' => 0, 'verified_users' => 0, 'pending_verification' => 0, 'verification_rate' => 0, 'active_tokens' => 0];
    $feedback = 'Fout bij ophalen statistieken: ' . $e->getMessage();
}

// Formulierverwerking voor nieuwe groepen en gebruikers
if (empty($feedback)) {
    $feedback = '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_group'])) {
        try {
            $name = Utils::sanitize($_POST['group_name']);
            $description = Utils::sanitize($_POST['group_desc']);
            $result = UserManager::createGroup($name, $description);
            if ($result['success']) {
                header('Location: group.php?id=' . $result['group_id']);
                exit;
            } else {
                $feedback = $result['message'];
            }
        } catch (Exception $e) {
            $feedback = 'Fout bij aanmaken groep: ' . $e->getMessage();
        }
    }
    if (isset($_POST['create_user'])) {
        try {
            $userData = [
                'username' => Utils::sanitize($_POST['username']),
                'email' => Utils::sanitize($_POST['email']),
                'password' => $_POST['password'],
                'first_name' => Utils::sanitize($_POST['first_name']),
                'last_name' => Utils::sanitize($_POST['last_name'])
            ];
            $result = Authentication::register($userData);
            if ($result['success']) {
                header('Location: user.php?id=' . $result['user_id']);
                exit;
            } else {
                $feedback = $result['message'];
            }
        } catch (Exception $e) {
            $feedback = 'Fout bij aanmaken gebruiker: ' . $e->getMessage();
        }
    }
    
    // Handle email verification actions
    if (isset($_POST['verify_user'])) {
        try {
            $userId = (int)$_POST['user_id'];
            $result = EmailVerificationHelper::manuallyVerifyUser($userId);
            $feedback = $result['message'];
        } catch (Exception $e) {
            $feedback = 'Fout bij verifiëren gebruiker: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['resend_verification'])) {
        try {
            $userId = (int)$_POST['user_id'];
            $email = Utils::sanitize($_POST['email']);
            $result = EmailVerificationHelper::sendVerificationEmail($userId, $email, true);
            $feedback = $result['message'];
        } catch (Exception $e) {
            $feedback = 'Fout bij verzenden verificatie email: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['send_verification_reminders'])) {
        try {
            $result = EmailVerificationHelper::sendVerificationReminders();
            $feedback = "Verificatie herinneringen verzonden: {$result['sent']} geslaagd, {$result['failed']} mislukt van {$result['total']} gebruikers.";
        } catch (Exception $e) {
            $feedback = 'Fout bij verzenden herinneringen: ' . $e->getMessage();
        }
    }
    
    if (isset($_POST['cleanup_expired_tokens'])) {
        try {
            EmailVerificationHelper::cleanupExpiredTokens();
            $feedback = 'Verlopen verificatie tokens zijn opgeschoond.';
        } catch (Exception $e) {
            $feedback = 'Fout bij opschonen tokens: ' . $e->getMessage();
        }
    }
}

// Gebruikers ophalen met error handling
try {
    $users = UserManager::getAllUsers(100, 0);
} catch (Exception $e) {
    $users = [];
    $feedback = 'Fout bij ophalen gebruikers: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beheer - Collectiebeheer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .admin-header { margin-bottom: 30px; }
        .admin-tabs .nav-link.active { background: #0d6efd; color: #fff; }
        .admin-table th, .admin-table td { vertical-align: middle; }
        .badge-group { font-size: 13px; margin-right: 2px; }
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
        <div class="admin-header text-center">
            <h2><i class="bi bi-gear"></i> Beheer</h2>
            <p class="text-muted">Beheer gebruikers, groepen en rechten</p>
        </div>
        <?php if ($feedback): ?>
            <div class="alert alert-info"><?= htmlspecialchars($feedback) ?></div>
        <?php endif; ?>
        <ul class="nav nav-tabs admin-tabs mb-4" id="adminTabs">
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'users' ? ' active' : '' ?>" href="?tab=users"><i class="bi bi-people"></i> Gebruikers</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'groups' ? ' active' : '' ?>" href="?tab=groups"><i class="bi bi-shield-lock"></i> Groepen & Rechten</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'stats' ? ' active' : '' ?>" href="?tab=stats"><i class="bi bi-bar-chart"></i> Statistieken</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'verification' ? ' active' : '' ?>" href="?tab=verification"><i class="bi bi-envelope-check"></i> Email Verificatie</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'system' ? ' active' : '' ?>" href="?tab=system"><i class="bi bi-gear"></i> Systeem</a>
            </li>
        </ul>
        <div class="tab-content">
            <!-- Gebruikersbeheer -->
            <div class="tab-pane fade<?= $tab === 'users' ? ' show active' : '' ?>" id="users">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="bi bi-people"></i> Gebruikers</h4>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus"></i> Nieuwe gebruiker</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Gebruiker</th>
                                <th>Email</th>
                                <th>Groepen</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Laatst ingelogd</th>
                                <th>Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?? 'N/A' ?></td>
                                    <td>
                                        <a href="user.php?id=<?= $user['id'] ?? '' ?>" class="text-decoration-none">
                                            <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>
                                        </a><br><small class="text-muted">@<?= htmlspecialchars($user['username'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                    <td>
                                        <?php if (!empty($user['user_groups'])): ?>
                                            <?php foreach (explode(',', $user['user_groups']) as $group): ?>
                                                <span class="badge bg-secondary badge-group"><?= htmlspecialchars($group) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Geen groepen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $user['collection_count'] ?? 0 ?></td>
                                    <td>
                                        <?php if (isset($user['is_active']) && $user['is_active']): ?>
                                            <span class="badge bg-success">Actief</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Geblokkeerd</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($user['last_login']) ? date('d-m-Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                                    <td>
                                        <a href="user.php?id=<?= $user['id'] ?? '' ?>" class="btn btn-sm btn-outline-primary" title="Bewerken"><i class="bi bi-pencil"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-danger" title="Verwijderen"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Geen gebruikers gevonden</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Groepen & Rechten -->
            <div class="tab-pane fade<?= $tab === 'groups' ? ' show active' : '' ?>" id="groups">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="bi bi-shield-lock"></i> Groepen & Rechten</h4>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal"><i class="bi bi-plus-circle"></i> Nieuwe groep</a>
                </div>
                <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                    <div class="alert alert-success">Groep succesvol verwijderd!</div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Groepen</h5>
                        <ul class="list-group mb-4">
                            <?php if (!empty($allGroups)): ?>
                                <?php foreach ($allGroups as $group): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="group.php?id=<?= $group['id'] ?>" class="text-decoration-none">
                                            <strong><?= htmlspecialchars($group['name']) ?></strong>
                                        </a>
                                        <?php if ($group['description']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($group['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary rounded-pill me-2"><?= $group['user_count'] ?> gebruikers</span>
                                        <a href="group.php?id=<?= $group['id'] ?>" class="btn btn-sm btn-outline-primary" title="Bewerken">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center text-muted">Geen groepen gevonden</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Rechten</h5>
                        <ul class="list-group mb-4">
                            <?php if (!empty($allPermissions)): ?>
                                <?php foreach ($allPermissions as $perm): ?>
                                <li class="list-group-item">
                                    <span><?= htmlspecialchars($perm['name']) ?></span>
                                    <small class="text-muted ms-2">- <?= htmlspecialchars($perm['description']) ?></small>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center text-muted">Geen rechten gevonden</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Statistieken -->
            <div class="tab-pane fade<?= $tab === 'stats' ? ' show active' : '' ?>" id="stats">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-people"></i> Gebruikers</h5>
                                <p class="card-text display-6 mb-0"><?= $userStats['total_users'] ?? 0 ?></p>
                                <small class="text-muted">Totaal</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-person-check"></i> Actief</h5>
                                <p class="card-text display-6 mb-0"><?= $userStats['active_users'] ?? 0 ?></p>
                                <small class="text-muted">Actieve gebruikers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-person-plus"></i> Nieuw</h5>
                                <p class="card-text display-6 mb-0"><?= $userStats['new_last_month'] ?? 0 ?></p>
                                <small class="text-muted">Aangemaakt deze maand</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Systeem -->
            <div class="tab-pane fade<?= $tab === 'system' ? ' show active' : '' ?>" id="system">
                <h4><i class="bi bi-gear"></i> Systeembeheer</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-cloud-download"></i> API Integraties</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Beheer API integraties voor automatische metadata en cover afbeeldingen.</p>
                                <a href="api-manager.php" class="btn btn-primary">
                                    <i class="bi bi-gear"></i> API Manager Openen
                                </a>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-shield-lock"></i> TOTP Beheer</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Beheer twee-factor authenticatie instellingen.</p>
                                <a href="totp-setup.php" class="btn btn-outline-primary">
                                    <i class="bi bi-shield-lock"></i> TOTP Setup
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-translate"></i> Taalinstellingen</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Beheer ondersteunde talen en vertalingen.</p>
                                <div class="d-grid gap-2">
                                    <?php if (I18nHelper::isEnabled()): ?>
                                        <?php 
                                        $currentLang = I18nHelper::getCurrentLanguage();
                                        $availableLanguages = I18nHelper::getAvailableLanguages();
                                        ?>
                                        <p><strong>Huidige taal:</strong> <?= $currentLang ?></p>
                                        <p><strong>Beschikbare talen:</strong> <?= count($availableLanguages) ?></p>
                                        
                                        <?php echo CollectionManager\LanguageSwitcher::render('buttons', true, true); ?>
                                    <?php else: ?>
                                        <p class="text-muted">Meertalige ondersteuning is uitgeschakeld</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-link-45deg"></i> OAuth Providers</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text">Sociale login providers status.</p>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Google:</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="badge bg-<?= OAuthHelper::isEnabled('google') ? 'success' : 'secondary' ?>">
                                            <?= OAuthHelper::isEnabled('google') ? 'Actief' : 'Inactief' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <strong>Facebook:</strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="badge bg-<?= OAuthHelper::isEnabled('facebook') ? 'success' : 'secondary' ?>">
                                            <?= OAuthHelper::isEnabled('facebook') ? 'Actief' : 'Inactief' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Verificatie Tab -->
            <div class="tab-pane fade<?= $tab === 'verification' ? ' show active' : '' ?>" id="verification">
                <div class="row">
                    <!-- Verificatie Statistieken -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Verificatie Statistieken</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="border rounded p-3">
                                            <div class="h4 text-primary"><?= $verificationStats['total_local_users'] ?></div>
                                            <small>Lokale Gebruikers</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-3">
                                            <div class="h4 text-success"><?= $verificationStats['verified_users'] ?></div>
                                            <small>Geverifieerd</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mt-3">
                                        <div class="border rounded p-3">
                                            <div class="h4 text-warning"><?= $verificationStats['pending_verification'] ?></div>
                                            <small>Wacht op Verificatie</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mt-3">
                                        <div class="border rounded p-3">
                                            <div class="h4 text-info"><?= $verificationStats['verification_rate'] ?>%</div>
                                            <small>Verificatie Ratio</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Actieve tokens:</strong> <?= $verificationStats['active_tokens'] ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Verificatie Acties -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-tools"></i> Verificatie Beheer</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <form method="POST" class="d-inline">
                                        <button type="submit" name="send_verification_reminders" class="btn btn-primary w-100">
                                            <i class="bi bi-envelope-paper"></i> Herinneringen Versturen
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="d-inline">
                                        <button type="submit" name="cleanup_expired_tokens" class="btn btn-warning w-100">
                                            <i class="bi bi-trash3"></i> Verlopen Tokens Opschonen
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Email Status:</strong>
                                        <?php if (MailHelper::isAvailable()): ?>
                                            <span class="badge bg-success">Beschikbaar</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Niet Beschikbaar</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Onverifieerde Gebruikers -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Onverifieerde Gebruikers</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Get unverified users
                        try {
                            $unverifiedUsers = [];
                            $usersTable = Environment::getTableName('users');
                            $sql = "SELECT id, username, email, first_name, last_name, created_at, verification_reminder_sent 
                                    FROM `$usersTable` 
                                    WHERE email_verified = FALSE AND registration_method = 'local' 
                                    ORDER BY created_at DESC";
                            $stmt = Database::query($sql);
                            $unverifiedUsers = $stmt->fetchAll();
                        } catch (Exception $e) {
                            $unverifiedUsers = [];
                        }
                        ?>
                        
                        <?php if (empty($unverifiedUsers)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">Alle gebruikers geverifieerd!</h5>
                                <p class="text-muted">Er zijn geen gebruikers die wachten op email verificatie.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Gebruiker</th>
                                            <th>Email</th>
                                            <th>Aangemaakt</th>
                                            <th>Herinnering</th>
                                            <th>Acties</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($unverifiedUsers as $user): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td>
                                                    <?= date('d-m-Y H:i', strtotime($user['created_at'])) ?><br>
                                                    <small class="text-muted"><?= Utils::timeAgo($user['created_at']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($user['verification_reminder_sent']): ?>
                                                        <span class="badge bg-info">Verstuurd</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Geen</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <button type="submit" name="verify_user" class="btn btn-success btn-sm" title="Handmatig verifiëren">
                                                                <i class="bi bi-check-circle"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="email" value="<?= htmlspecialchars($user['email']) ?>">
                                                            <button type="submit" name="resend_verification" class="btn btn-primary btn-sm" title="Verificatie email opnieuw versturen">
                                                                <i class="bi bi-envelope-arrow-up"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals voor toevoegen gebruiker/groep -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="#">
                    <div class="modal-header">
                        <h5 class="modal-title">Nieuwe gebruiker toevoegen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add-username" class="form-label">Gebruikersnaam</label>
                            <input type="text" class="form-control" id="add-username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="add-email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-first-name" class="form-label">Voornaam</label>
                            <input type="text" class="form-control" id="add-first-name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-last-name" class="form-label">Achternaam</label>
                            <input type="text" class="form-control" id="add-last-name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-password" class="form-label">Wachtwoord</label>
                            <input type="password" class="form-control" id="add-password" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" name="create_user" class="btn btn-primary">Toevoegen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="#">
                    <div class="modal-header">
                        <h5 class="modal-title">Nieuwe groep toevoegen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add-group-name" class="form-label">Groepsnaam</label>
                            <input type="text" class="form-control" id="add-group-name" name="group_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-group-desc" class="form-label">Beschrijving</label>
                            <input type="text" class="form-control" id="add-group-desc" name="group_desc">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="submit" name="create_group" class="btn btn-primary">Toevoegen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html> 