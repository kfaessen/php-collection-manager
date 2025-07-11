<?php
/**
 * Collection Manager - Main Application
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include dependencies
require_once '../includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    handleAjaxRequest();
    exit;
}

// Get current page and filters
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = 12;
$search = Utils::sanitize($_GET['search'] ?? '');
$typeFilter = Utils::sanitize($_GET['type'] ?? '');

// Get items
$offset = ($page - 1) * $itemsPerPage;
$items = CollectionManager::getItems($typeFilter, $search, $itemsPerPage, $offset);
$totalItems = CollectionManager::countItems($typeFilter, $search);

/**
 * Handle AJAX requests
 */
function handleAjaxRequest() 
{
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'scan_barcode':
                $barcode = Utils::sanitize($_POST['barcode']);
                if (!Utils::validateBarcode($barcode)) {
                    Utils::errorResponse('Ongeldige barcode');
                }
                
                // Check if item already exists
                $existing = CollectionManager::getItemByBarcode($barcode);
                if ($existing) {
                    Utils::errorResponse('Item met deze barcode bestaat al in uw collectie');
                }
                
                // Get metadata
                $metadata = APIManager::getMetadataByBarcode($barcode);
                if (!$metadata) {
                    Utils::errorResponse('Geen metadata gevonden voor deze barcode');
                }
                
                Utils::successResponse($metadata, 'Metadata opgehaald');
                break;
                
            case 'add_item':
                $data = [
                    'title' => Utils::sanitize($_POST['title']),
                    'type' => Utils::sanitize($_POST['type']),
                    'barcode' => Utils::sanitize($_POST['barcode']),
                    'platform' => Utils::sanitize($_POST['platform'] ?? ''),
                    'director' => Utils::sanitize($_POST['director'] ?? ''),
                    'publisher' => Utils::sanitize($_POST['publisher'] ?? ''),
                    'description' => Utils::sanitize($_POST['description'] ?? ''),
                    'cover_image' => Utils::sanitize($_POST['cover_image'] ?? ''),
                    'metadata' => json_decode($_POST['metadata'] ?? '{}', true)
                ];
                
                if (!$data['title'] || !$data['type']) {
                    Utils::errorResponse('Titel en type zijn verplicht');
                }
                
                $id = CollectionManager::addItem($data);
                Utils::successResponse(['id' => $id], 'Item toegevoegd aan collectie');
                break;
                
            case 'delete_item':
                $id = intval($_POST['id']);
                $deleted = CollectionManager::deleteItem($id);
                if ($deleted) {
                    Utils::successResponse(null, 'Item verwijderd');
                } else {
                    Utils::errorResponse('Item niet gevonden');
                }
                break;
                
            default:
                Utils::errorResponse('Onbekende actie');
        }
    } catch (Exception $e) {
        Utils::errorResponse($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collectiebeheer - Games, Films & Series</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- html5-qrcode voor barcode scanning -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-collection"></i> Collectiebeheer
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Overzicht</a>
                    </li>
                </ul>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="bi bi-plus-lg"></i> Item Toevoegen
                </button>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-md-8">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Zoeken in collectie..." value="<?= htmlspecialchars($search) ?>">
                    <select name="type" class="form-select me-2" style="width: auto;">
                        <option value="">Alle types</option>
                        <option value="game" <?= $typeFilter === 'game' ? 'selected' : '' ?>>Games</option>
                        <option value="film" <?= $typeFilter === 'film' ? 'selected' : '' ?>>Films</option>
                        <option value="serie" <?= $typeFilter === 'serie' ? 'selected' : '' ?>>Series</option>
                    </select>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <small class="text-muted"><?= $totalItems ?> items in collectie</small>
            </div>
        </div>

        <!-- Items Grid -->
        <div class="row">
            <?php if (empty($items)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h4>Geen items gevonden</h4>
                        <p>Voeg uw eerste item toe aan de collectie!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="bi bi-plus-lg"></i> Item Toevoegen
                        </button>
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
                                    <div class="btn-group w-100 mt-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewItem(<?= $item['id'] ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(<?= $item['id'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalItems > $itemsPerPage): ?>
            <div class="row">
                <div class="col-12">
                    <?php
                    $baseUrl = "index.php?search=" . urlencode($search) . "&type=" . urlencode($typeFilter);
                    echo Utils::generatePagination($page, $totalItems, $itemsPerPage, $baseUrl);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Item Toevoegen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Scanner Tab -->
                    <ul class="nav nav-tabs" id="addItemTabs">
                        <li class="nav-item">
                            <button class="nav-link active" id="scanner-tab" data-bs-toggle="tab" data-bs-target="#scanner" type="button">
                                <i class="bi bi-camera"></i> Barcode Scannen
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button">
                                <i class="bi bi-pencil"></i> Handmatig Invoeren
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3">
                        <!-- Scanner Tab Content -->
                        <div class="tab-pane fade show active" id="scanner">
                            <div class="text-center">
                                <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                                <button id="start-scan" class="btn btn-primary mt-3">
                                    <i class="bi bi-camera"></i> Start Camera
                                </button>
                                <button id="stop-scan" class="btn btn-secondary mt-3" style="display: none;">
                                    <i class="bi bi-stop"></i> Stop Camera
                                </button>
                                
                                <div class="mt-3">
                                    <label for="manual-barcode" class="form-label">Of voer barcode handmatig in:</label>
                                    <div class="input-group">
                                        <input type="text" id="manual-barcode" class="form-control" placeholder="Voer barcode in...">
                                        <button class="btn btn-outline-primary" onclick="lookupBarcode()">Zoeken</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Manual Tab Content -->
                        <div class="tab-pane fade" id="manual">
                            <form id="manual-form">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="manual-title" class="form-label">Titel *</label>
                                            <input type="text" class="form-control" id="manual-title" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="manual-type" class="form-label">Type *</label>
                                            <select class="form-select" id="manual-type" required>
                                                <option value="">Selecteer type...</option>
                                                <option value="game">Game</option>
                                                <option value="film">Film</option>
                                                <option value="serie">Serie</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="manual-platform" class="form-label">Platform/Regisseur</label>
                                            <input type="text" class="form-control" id="manual-platform">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="manual-publisher" class="form-label">Uitgever</label>
                                            <input type="text" class="form-control" id="manual-publisher">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="manual-description" class="form-label">Beschrijving</label>
                                    <textarea class="form-control" id="manual-description" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="manual-cover" class="form-label">Cover URL</label>
                                    <input type="url" class="form-control" id="manual-cover">
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Metadata Preview -->
                    <div id="metadata-preview" style="display: none;" class="mt-4">
                        <h6>Gevonden metadata:</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <img id="preview-cover" class="img-fluid" style="max-height: 200px;">
                                    </div>
                                    <div class="col-md-9">
                                        <h5 id="preview-title"></h5>
                                        <p><strong>Type:</strong> <span id="preview-type"></span></p>
                                        <p><strong>Platform/Regisseur:</strong> <span id="preview-platform"></span></p>
                                        <p><strong>Beschrijving:</strong> <span id="preview-description"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                    <button type="button" class="btn btn-primary" id="save-item" onclick="saveItem()" disabled>
                        <i class="bi bi-save"></i> Opslaan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="position-fixed top-50 start-50 translate-middle" style="display: none; z-index: 9999;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Laden...</span>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/app.js"></script>
</body>
</html> 