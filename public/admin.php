<?php
/**
 * Admin Interface - Gebruikers en rechtenbeheer
 */

require_once '../includes/functions.php';

// Alleen toegankelijk voor beheerders
Authentication::requirePermission('manage_users');

$tab = $_GET['tab'] ?? 'users';

// Statistieken ophalen
$userStats = UserManager::getUserStats();
$allGroups = UserManager::getAllGroups();
$allPermissions = UserManager::getAllPermissions();

// Formulierverwerking voor nieuwe groepen en gebruikers
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_group'])) {
        $name = Utils::sanitize($_POST['group_name']);
        $description = Utils::sanitize($_POST['group_desc']);
        $result = UserManager::createGroup($name, $description);
        if ($result['success']) {
            header('Location: group.php?id=' . $result['group_id']);
            exit;
        } else {
            $feedback = $result['message'];
        }
    }
    if (isset($_POST['create_user'])) {
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
    }
}

// Gebruikers ophalen
$users = UserManager::getAllUsers(100, 0);

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
                <button class="btn btn-outline-light" onclick="logout()"><i class="bi bi-box-arrow-right"></i> Uitloggen</button>
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
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <a href="user.php?id=<?= $user['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                        </a><br><small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php foreach (explode(',', $user['groups']) as $group): ?>
                                            <span class="badge bg-secondary badge-group"><?= htmlspecialchars($group) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td><?= $user['collection_count'] ?></td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Actief</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Geblokkeerd</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $user['last_login'] ? date('d-m-Y H:i', strtotime($user['last_login'])) : '-' ?></td>
                                    <td>
                                        <a href="user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" title="Bewerken"><i class="bi bi-pencil"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-danger" title="Verwijderen"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Rechten</h5>
                        <ul class="list-group mb-4">
                            <?php foreach ($allPermissions as $perm): ?>
                                <li class="list-group-item">
                                    <span><?= htmlspecialchars($perm['name']) ?></span>
                                    <small class="text-muted ms-2">- <?= htmlspecialchars($perm['description']) ?></small>
                                </li>
                            <?php endforeach; ?>
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
                                <p class="card-text display-6 mb-0"><?= $userStats['total_users'] ?></p>
                                <small class="text-muted">Totaal</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-person-check"></i> Actief</h5>
                                <p class="card-text display-6 mb-0"><?= $userStats['active_users'] ?></p>
                                <small class="text-muted">Actieve gebruikers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-person-plus"></i> Nieuw</h5>
                                <p class="card-text display-6 mb-0"><?= $userStats['new_last_month'] ?></p>
                                <small class="text-muted">Aangemaakt deze maand</small>
                            </div>
                        </div>
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