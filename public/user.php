<?php
require_once '../includes/functions.php';
Authentication::requirePermission('manage_users');

$userId = intval($_GET['id'] ?? 0);
$user = UserManager::getUserById($userId);
if (!$user) {
    http_response_code(404);
    echo '<h2>Gebruiker niet gevonden.</h2>';
    exit;
}

$allGroups = UserManager::getAllGroups();
$userGroups = UserManager::getUserGroups($userId);
$allPermissions = UserManager::getAllPermissions();

// Haal items van gebruiker op
$items = CollectionManager::getItems('', '', 1000, 0, $userId);

// Haal rechten van gebruiker op
$userPerms = [];
foreach ($userGroups as $g) {
    foreach (UserManager::getGroupPermissions($g['id']) as $p) {
        $userPerms[$p['name']] = $p['description'];
    }
}

// Formulierverwerking (bewerken, wachtwoord reset, groepen)
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $fields = [
            'username' => Utils::sanitize($_POST['username']),
            'email' => Utils::sanitize($_POST['email']),
            'first_name' => Utils::sanitize($_POST['first_name']),
            'last_name' => Utils::sanitize($_POST['last_name']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        $result = UserManager::updateUser($userId, $fields);
        $feedback = $result['message'];
        $user = UserManager::getUserById($userId);
    }
    if (isset($_POST['reset_password'])) {
        $newPass = $_POST['new_password'];
        $result = UserManager::changePassword($userId, $newPass);
        $feedback = $result['message'];
    }
    if (isset($_POST['add_group'])) {
        $groupId = intval($_POST['group_id']);
        $result = UserManager::addUserToGroup($userId, $groupId);
        $feedback = $result['message'];
    }
    if (isset($_POST['remove_group'])) {
        $groupId = intval($_POST['group_id']);
        $result = UserManager::removeUserFromGroup($userId, $groupId);
        $feedback = $result['message'];
    }
    // Refresh data
    $userGroups = UserManager::getUserGroups($userId);
    $userPerms = [];
    foreach ($userGroups as $g) {
        foreach (UserManager::getGroupPermissions($g['id']) as $p) {
            $userPerms[$p['name']] = $p['description'];
        }
    }
}
?><!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gebruiker beheren - <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <a href="admin.php?tab=users" class="btn btn-outline-primary mb-3"><i class="bi bi-arrow-left"></i> Terug naar gebruikers</a>
        <h2><i class="bi bi-person"></i> Gebruiker beheren: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
        <?php if ($feedback): ?>
            <div class="alert alert-info mt-3"> <?= htmlspecialchars($feedback) ?> </div>
        <?php endif; ?>
        <div class="row mt-4">
            <div class="col-md-6">
                <form method="POST" class="mb-4">
                    <h5>Profielgegevens</h5>
                    <div class="mb-2">
                        <label class="form-label">Gebruikersnaam</label>
                        <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Voornaam</label>
                        <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Achternaam</label>
                        <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Account actief</label>
                    </div>
                    <button type="submit" name="update_user" class="btn btn-primary">Opslaan</button>
                </form>
                <form method="POST" class="mb-4">
                    <h5>Wachtwoord resetten</h5>
                    <div class="mb-2">
                        <input type="password" class="form-control" name="new_password" placeholder="Nieuw wachtwoord" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-warning">Reset wachtwoord</button>
                </form>
            </div>
            <div class="col-md-6">
                <h5>Groepen</h5>
                <ul class="list-group mb-3">
                    <?php foreach ($userGroups as $g): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($g['name']) ?>
                            <form method="POST" class="mb-0">
                                <input type="hidden" name="group_id" value="<?= $g['id'] ?>">
                                <button type="submit" name="remove_group" class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <select class="form-select" name="group_id">
                            <?php foreach ($allGroups as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="add_group" class="btn btn-outline-primary">Toevoegen</button>
                    </div>
                </form>
                <h5>Rechten</h5>
                <ul class="list-group">
                    <?php foreach ($userPerms as $perm => $desc): ?>
                        <li class="list-group-item"><strong><?= htmlspecialchars($perm) ?></strong> <small class="text-muted">- <?= htmlspecialchars($desc) ?></small></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <h5 class="mt-5">Collectie van deze gebruiker (alleen-lezen)</h5>
        <div class="row">
            <?php if (empty($items)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">Geen items gevonden</div>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
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
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 