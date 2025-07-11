<?php
require_once '../includes/functions.php';

$token = $_GET['token'] ?? '';

if (!$token || !CollectionManager::isShareLinkValid($token)) {
    http_response_code(404);
    echo '<h2>Deze gedeelde link is niet (meer) geldig.</h2>';
    exit;
}

$userId = CollectionManager::getUserIdByToken($token);
if (!$userId) {
    http_response_code(404);
    echo '<h2>Deze gedeelde link is niet (meer) geldig.</h2>';
    exit;
}

// Haal gebruiker info op
$user = UserManager::getUserById($userId);
if (!$user) {
    http_response_code(404);
    echo '<h2>Gebruiker niet gevonden.</h2>';
    exit;
}

// Haal collectie op
$items = CollectionManager::getItems('', '', 1000, 0, $userId);
$totalItems = count($items);

?><!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gedeelde Collectie van <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .public-header { margin-top: 40px; margin-bottom: 30px; text-align: center; }
        .public-header h2 { color: #0d6efd; }
        .public-header .badge { font-size: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="public-header">
            <h2><i class="bi bi-share"></i> Gedeelde Collectie</h2>
            <p class="lead mb-1">van <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong></p>
            <span class="badge bg-primary">Publiek gedeeld</span>
            <p class="text-muted mt-2 mb-0">Deze pagina is alleen-lezen en verloopt automatisch.</p>
        </div>
        <div class="mb-4 text-center">
            <span class="text-muted"><?= $totalItems ?> items in collectie</span>
        </div>
        <div class="row">
            <?php if (empty($items)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h4>Geen items gevonden</h4>
                        <p>Deze collectie is leeg.</p>
                    </div>
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
        <div class="text-center mt-4 mb-5">
            <a href="https://<?= $_SERVER['HTTP_HOST'] ?>" class="btn btn-outline-primary"><i class="bi bi-house"></i> Naar Collectiebeheer</a>
        </div>
    </div>
</body>
</html> 