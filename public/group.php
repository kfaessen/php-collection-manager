<?php
require_once '../includes/functions.php';
Authentication::requirePermission('manage_groups');

$groupId = intval($_GET['id'] ?? 0);
$group = UserManager::getGroupById($groupId);
if (!$group) {
    http_response_code(404);
    echo '<h2>Groep niet gevonden.</h2>';
    exit;
}

$allUsers = UserManager::getAllUsers(1000, 0);
$groupMembers = UserManager::getGroupUsers($groupId);
$allPermissions = UserManager::getAllPermissions();
$groupPerms = UserManager::getGroupPermissions($groupId);

// Formulierverwerking (bewerken, leden, rechten)
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_group'])) {
        $name = Utils::sanitize($_POST['name']);
        $description = Utils::sanitize($_POST['description']);
        $result = UserManager::updateGroup($groupId, $name, $description);
        $feedback = $result['message'];
        $group = UserManager::getGroupById($groupId);
    }
    if (isset($_POST['delete_group'])) {
        $result = UserManager::deleteGroup($groupId);
        if ($result['success']) {
            header('Location: admin.php?tab=groups&deleted=1');
            exit;
        } else {
            $feedback = $result['message'];
        }
    }
    if (isset($_POST['add_user'])) {
        $userId = intval($_POST['user_id']);
        $result = UserManager::addUserToGroup($userId, $groupId);
        $feedback = $result['message'];
    }
    if (isset($_POST['remove_user'])) {
        $userId = intval($_POST['user_id']);
        $result = UserManager::removeUserFromGroup($userId, $groupId);
        $feedback = $result['message'];
    }
    if (isset($_POST['add_permission'])) {
        $permissionId = intval($_POST['permission_id']);
        $result = UserManager::addPermissionToGroup($groupId, $permissionId);
        $feedback = $result['message'];
    }
    if (isset($_POST['remove_permission'])) {
        $permissionId = intval($_POST['permission_id']);
        $result = UserManager::removePermissionFromGroup($groupId, $permissionId);
        $feedback = $result['message'];
    }
    // Refresh data
    $groupMembers = UserManager::getGroupUsers($groupId);
    $groupPerms = UserManager::getGroupPermissions($groupId);
}
?><!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groep beheren - <?= htmlspecialchars($group['name']) ?></title>
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
                <a href="admin.php?tab=groups" class="btn btn-outline-light me-2"><i class="bi bi-arrow-left"></i> Terug naar groepen</a>
                <a href="logout.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-right"></i> Uitloggen</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="bi bi-shield-lock"></i> Groep beheren: <?= htmlspecialchars($group['name']) ?></h2>
        <?php if ($feedback): ?>
            <div class="alert alert-info mt-3"> <?= htmlspecialchars($feedback) ?> </div>
        <?php endif; ?>
        <div class="row mt-4">
            <div class="col-md-6">
                <form method="POST" class="mb-4">
                    <h5>Groepsgegevens</h5>
                    <div class="mb-2">
                        <label class="form-label">Naam</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($group['name']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Beschrijving</label>
                        <input type="text" class="form-control" name="description" value="<?= htmlspecialchars($group['description']) ?>">
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">
                            Aangemaakt: <?= date('d-m-Y H:i', strtotime($group['created_at'])) ?><br>
                            Laatst gewijzigd: <?= date('d-m-Y H:i', strtotime($group['updated_at'])) ?>
                        </small>
                    </div>
                    <button type="submit" name="update_group" class="btn btn-primary">Opslaan</button>
                    <?php if (!in_array($group['name'], ['admin','user','moderator'])): ?>
                        <button type="submit" name="delete_group" class="btn btn-danger ms-2" onclick="return confirm('Weet je zeker dat je deze groep wilt verwijderen?')">Verwijderen</button>
                    <?php endif; ?>
                </form>
                <h5>Leden (<?= count($groupMembers) ?>)</h5>
                <ul class="list-group mb-3">
                    <?php if (empty($groupMembers)): ?>
                        <li class="list-group-item text-muted">Geen leden in deze groep</li>
                    <?php else: ?>
                        <?php foreach ($groupMembers as $member): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong><br>
                                    <small class="text-muted">@<?= htmlspecialchars($member['username']) ?> • <?= htmlspecialchars($member['email']) ?></small>
                                </div>
                                <form method="POST" class="mb-0">
                                    <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                    <button type="submit" name="remove_user" class="btn btn-sm btn-outline-danger" title="Verwijderen uit groep"><i class="bi bi-x"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <select class="form-select" name="user_id">
                            <option value="">Selecteer gebruiker...</option>
                            <?php foreach ($allUsers as $u): ?>
                                <?php if (!in_array($u['id'], array_column($groupMembers, 'id'))): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (@' . $u['username'] . ')') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="add_user" class="btn btn-outline-primary">Toevoegen</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <h5>Rechten (<?= count($groupPerms) ?>)</h5>
                <ul class="list-group mb-3">
                    <?php if (empty($groupPerms)): ?>
                        <li class="list-group-item text-muted">Geen rechten toegewezen aan deze groep</li>
                    <?php else: ?>
                        <?php foreach ($groupPerms as $perm): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($perm['name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($perm['description']) ?></small>
                                </div>
                                <form method="POST" class="mb-0">
                                    <input type="hidden" name="permission_id" value="<?= $perm['id'] ?>">
                                    <button type="submit" name="remove_permission" class="btn btn-sm btn-outline-danger" title="Recht intrekken"><i class="bi bi-x"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <select class="form-select" name="permission_id">
                            <option value="">Selecteer recht...</option>
                            <?php foreach ($allPermissions as $p): ?>
                                <?php if (!in_array($p['id'], array_column($groupPerms, 'id'))): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> - <?= htmlspecialchars($p['description']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="add_permission" class="btn btn-outline-primary">Toevoegen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html> 