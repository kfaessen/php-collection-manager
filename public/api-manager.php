<?php
/**
 * API Manager Interface
 * Test and manage API integrations for metadata enrichment
 */

// Include dependencies
require_once '../includes/functions.php';

// Check authentication
if (!Authentication::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check permissions
if (!Authentication::hasPermission('system_admin')) {
    Utils::errorResponse('Geen toegang tot API beheer');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$success_message = '';
$error_message = '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    header('Content-Type: application/json');
    
    try {
        switch ($action) {
            case 'test_connection':
                echo json_encode(handleTestConnection($_POST));
                break;
                
            case 'enrich_item':
                echo json_encode(handleEnrichItem($_POST));
                break;
                
            case 'get_providers':
                echo json_encode(handleGetProviders($_POST));
                break;
                
            case 'test_barcode':
                echo json_encode(handleTestBarcode($_POST));
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Onbekende actie']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get API status
$apiStatus = getApiStatus();
$providers = APIManager::getAvailableProviders();

/**
 * Handle connection testing
 */
function handleTestConnection($data) {
    $provider = $data['provider'] ?? '';
    
    switch ($provider) {
        case 'IGDB':
            return testIGDBConnection();
        case 'OMDb':
            return testOMDbConnection();
        case 'TMDb':
            return testTMDbConnection();
        default:
            return ['success' => false, 'message' => 'Onbekende provider'];
    }
}

/**
 * Handle item enrichment
 */
function handleEnrichItem($data) {
    $itemId = intval($data['item_id'] ?? 0);
    
    if (!$itemId) {
        return ['success' => false, 'message' => 'Geen item ID opgegeven'];
    }
    
    return APIManager::enrichItemMetadata($itemId);
}

/**
 * Handle getting providers
 */
function handleGetProviders($data) {
    $type = $data['type'] ?? null;
    $providers = APIManager::getAvailableProviders($type);
    
    return [
        'success' => true,
        'providers' => $providers
    ];
}

/**
 * Handle barcode testing
 */
function handleTestBarcode($data) {
    $barcode = trim($data['barcode'] ?? '');
    
    if (empty($barcode)) {
        return ['success' => false, 'message' => 'Geen barcode opgegeven'];
    }
    
    $metadata = APIManager::getMetadataByBarcode($barcode);
    
    if ($metadata) {
        return [
            'success' => true,
            'metadata' => $metadata,
            'message' => 'Metadata gevonden via ' . ($metadata['source'] ?? 'onbekende bron')
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Geen metadata gevonden voor barcode: ' . $barcode
        ];
    }
}

/**
 * Test IGDB connection
 */
function testIGDBConnection() {
    if (!Environment::get('IGDB_ENABLED', true)) {
        return ['success' => false, 'message' => 'IGDB is uitgeschakeld'];
    }
    
    $clientId = Environment::get('IGDB_CLIENT_ID');
    $clientSecret = Environment::get('IGDB_CLIENT_SECRET');
    
    if (!$clientId || !$clientSecret) {
        return ['success' => false, 'message' => 'IGDB Client ID of Secret niet geconfigureerd'];
    }
    
    // Try to get access token
    $url = "https://id.twitch.tv/oauth2/token?client_id=$clientId&client_secret=$clientSecret&grant_type=client_credentials";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            return ['success' => true, 'message' => 'IGDB verbinding succesvol'];
        }
    }
    
    return ['success' => false, 'message' => 'IGDB verbinding mislukt. HTTP code: ' . $httpCode];
}

/**
 * Test OMDb connection
 */
function testOMDbConnection() {
    if (!Environment::get('OMDB_ENABLED', true)) {
        return ['success' => false, 'message' => 'OMDb is uitgeschakeld'];
    }
    
    $apiKey = Environment::get('OMDB_API_KEY');
    if (!$apiKey) {
        return ['success' => false, 'message' => 'OMDb API key niet geconfigureerd'];
    }
    
    // Test with a known movie
    $url = "http://www.omdbapi.com/?apikey=$apiKey&t=Inception&plot=short";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && $data['Response'] === 'True') {
            return ['success' => true, 'message' => 'OMDb verbinding succesvol'];
        } elseif ($data && isset($data['Error'])) {
            return ['success' => false, 'message' => 'OMDb fout: ' . $data['Error']];
        }
    }
    
    return ['success' => false, 'message' => 'OMDb verbinding mislukt. HTTP code: ' . $httpCode];
}

/**
 * Test TMDb connection
 */
function testTMDbConnection() {
    if (!Environment::get('TMDB_ENABLED', false)) {
        return ['success' => false, 'message' => 'TMDb is uitgeschakeld'];
    }
    
    $apiKey = Environment::get('TMDB_API_KEY');
    if (!$apiKey) {
        return ['success' => false, 'message' => 'TMDb API key niet geconfigureerd'];
    }
    
    // Test configuration endpoint
    $url = "https://api.themoviedb.org/3/configuration?api_key=$apiKey";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['images'])) {
            return ['success' => true, 'message' => 'TMDb verbinding succesvol'];
        }
    }
    
    return ['success' => false, 'message' => 'TMDb verbinding mislukt. HTTP code: ' . $httpCode];
}

/**
 * Get overall API status
 */
function getApiStatus() {
    return [
        'enabled' => Environment::get('API_ENABLED', true),
        'cache_enabled' => Environment::get('API_CACHE_ENABLED', true),
        'auto_cover_download' => Environment::get('AUTO_COVER_DOWNLOAD', true),
        'cover_quality' => Environment::get('COVER_DOWNLOAD_QUALITY', 'medium'),
        'max_file_size' => Environment::get('MAX_COVER_FILE_SIZE', 5 * 1024 * 1024),
        'request_timeout' => Environment::get('API_REQUEST_TIMEOUT', 30)
    ];
}

/**
 * Get recent API activities (placeholder)
 */
function getRecentActivities() {
    // In a full implementation, this would query api_cache or api_rate_limits tables
    return [
        ['time' => '2 minuten geleden', 'action' => 'IGDB lookup voor "Super Mario Bros"', 'status' => 'success'],
        ['time' => '5 minuten geleden', 'action' => 'OMDb lookup voor "Inception"', 'status' => 'success'],
        ['time' => '10 minuten geleden', 'action' => 'Cover download voor item #123', 'status' => 'success']
    ];
}
?>
<!DOCTYPE html>
<html lang="<?= I18nHelper::getCurrentLanguage() ?>" dir="<?= I18nHelper::getDirection() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= I18nHelper::t('api_manager', [], 'admin') ?> - Collectiebeheer</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
            <div class="d-flex">
                <a href="admin.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-arrow-left"></i> Terug naar beheer
                </a>
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Uitloggen
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="bi bi-cloud-download"></i> API Beheer</h2>
                <p class="text-muted">Beheer en test API integraties voor automatische metadata en covers</p>
            </div>
        </div>

        <!-- API Status Overview -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> API Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <strong>API Enabled:</strong>
                            </div>
                            <div class="col-6">
                                <span class="badge bg-<?= $apiStatus['enabled'] ? 'success' : 'danger' ?>">
                                    <?= $apiStatus['enabled'] ? 'Ingeschakeld' : 'Uitgeschakeld' ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Auto Cover Download:</strong>
                            </div>
                            <div class="col-6">
                                <span class="badge bg-<?= $apiStatus['auto_cover_download'] ? 'success' : 'secondary' ?>">
                                    <?= $apiStatus['auto_cover_download'] ? 'Ingeschakeld' : 'Uitgeschakeld' ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Cover Quality:</strong>
                            </div>
                            <div class="col-6">
                                <?= ucfirst($apiStatus['cover_quality']) ?>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Request Timeout:</strong>
                            </div>
                            <div class="col-6">
                                <?= $apiStatus['request_timeout'] ?>s
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-activity"></i> Recente Activiteiten</h5>
                    </div>
                    <div class="card-body">
                        <?php $activities = getRecentActivities(); ?>
                        <?php if (empty($activities)): ?>
                            <p class="text-muted">Nog geen API activiteiten</p>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small><?= htmlspecialchars($activity['action']) ?></small>
                                    <div>
                                        <span class="badge bg-<?= $activity['status'] === 'success' ? 'success' : 'danger' ?> me-2">
                                            <?= $activity['status'] ?>
                                        </span>
                                        <small class="text-muted"><?= $activity['time'] ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Providers -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-puzzle"></i> API Providers</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($providers as $providerName => $provider): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <?= htmlspecialchars($provider['name']) ?>
                                                <span class="badge bg-<?= $provider['enabled'] ? 'success' : 'secondary' ?> ms-2">
                                                    <?= $provider['enabled'] ? 'Actief' : 'Inactief' ?>
                                                </span>
                                            </h6>
                                            <p class="card-text small"><?= htmlspecialchars($provider['description']) ?></p>
                                            <p class="small mb-2">
                                                <strong>Types:</strong> <?= implode(', ', $provider['types']) ?>
                                            </p>
                                            <button class="btn btn-sm btn-outline-primary" onclick="testConnection('<?= $providerName ?>')">
                                                <i class="bi bi-wifi"></i> Test Verbinding
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Tools -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-search"></i> Test Barcode Lookup</h5>
                    </div>
                    <div class="card-body">
                        <form id="barcodeTestForm">
                            <div class="mb-3">
                                <label for="test_barcode" class="form-label">Barcode</label>
                                <input type="text" class="form-control" id="test_barcode" placeholder="Voer barcode in..." required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Test Lookup
                            </button>
                        </form>
                        <div id="barcodeResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Item Enrichment</h5>
                    </div>
                    <div class="card-body">
                        <form id="enrichmentForm">
                            <div class="mb-3">
                                <label for="item_id" class="form-label">Item ID</label>
                                <input type="number" class="form-control" id="item_id" placeholder="Voer item ID in..." required>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-cloud-download"></i> Enrich Item
                            </button>
                        </form>
                        <div id="enrichmentResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Test connection to API provider
        function testConnection(provider) {
            fetch('api-manager.php?action=test_connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test_connection&provider=' + encodeURIComponent(provider)
            })
            .then(response => response.json())
            .then(data => {
                const alertClass = data.success ? 'alert-success' : 'alert-danger';
                showAlert(data.message, alertClass);
            })
            .catch(error => {
                showAlert('Fout bij testen verbinding: ' + error.message, 'alert-danger');
            });
        }
        
        // Test barcode lookup
        document.getElementById('barcodeTestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const barcode = document.getElementById('test_barcode').value;
            const resultsDiv = document.getElementById('barcodeResults');
            
            resultsDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Zoeken...';
            
            fetch('api-manager.php?action=test_barcode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test_barcode&barcode=' + encodeURIComponent(barcode)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <strong>Gevonden!</strong> ${data.message}<br>
                            <small>Title: ${data.metadata.title || 'N/A'}</small>
                        </div>
                        <pre class="bg-light p-2 small">${JSON.stringify(data.metadata, null, 2)}</pre>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `<div class="alert alert-danger">Fout: ${error.message}</div>`;
            });
        });
        
        // Test item enrichment
        document.getElementById('enrichmentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const itemId = document.getElementById('item_id').value;
            const resultsDiv = document.getElementById('enrichmentResults');
            
            resultsDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Enriching...';
            
            fetch('api-manager.php?action=enrich_item', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=enrich_item&item_id=' + encodeURIComponent(itemId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <strong>Success!</strong> Item enriched with data from: ${data.providers_used.join(', ')}<br>
                            <small>Covers downloaded: ${data.covers ? data.covers.length : 0}</small>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `<div class="alert alert-danger">Fout: ${error.message}</div>`;
            });
        });
        
        // Show alert message
        function showAlert(message, alertClass = 'alert-info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
    </script>
</body>
</html> 