<?php
/**
 * Health Check Endpoint
 * Validates application status for deployment pipelines
 */

// Disable output buffering for immediate responses
if (ob_get_level()) {
    ob_end_clean();
}

// Set headers for health check response
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$healthData = [
    'status' => 'unknown',
    'timestamp' => date('c'),
    'version' => '1.0.0',
    'environment' => 'unknown',
    'checks' => []
];

$allHealthy = true;

try {
    // Basic PHP check
    $healthData['checks']['php'] = [
        'status' => 'ok',
        'version' => PHP_VERSION,
        'message' => 'PHP is running'
    ];

    // Check if core files exist
    $coreFiles = [
        '../includes/functions.php',
        '../includes/Database.php',
        '../includes/Environment.php'
    ];

    foreach ($coreFiles as $file) {
        if (!file_exists($file)) {
            $healthData['checks']['core_files'] = [
                'status' => 'error',
                'message' => "Core file missing: $file"
            ];
            $allHealthy = false;
            break;
        }
    }

    if (!isset($healthData['checks']['core_files'])) {
        $healthData['checks']['core_files'] = [
            'status' => 'ok',
            'message' => 'All core files present'
        ];
    }

    // Try to load core functions
    if (file_exists('../includes/functions.php')) {
        require_once '../includes/functions.php';
        
        $healthData['checks']['functions_loaded'] = [
            'status' => 'ok',
            'message' => 'Core functions loaded'
        ];

        // Check environment
        if (class_exists('CollectionManager\Environment')) {
            try {
                Environment::init();
                $healthData['environment'] = Environment::get('APP_ENV', 'unknown');
                
                $healthData['checks']['environment'] = [
                    'status' => 'ok',
                    'message' => 'Environment configuration loaded'
                ];
            } catch (Exception $e) {
                $healthData['checks']['environment'] = [
                    'status' => 'warning',
                    'message' => 'Environment configuration issue: ' . $e->getMessage()
                ];
            }
        }

        // Database connectivity check
        if (class_exists('CollectionManager\Database')) {
            try {
                Database::init();
                
                // Try a simple query
                $stmt = Database::query("SELECT 1 as test");
                $result = $stmt->fetch();
                
                if ($result && $result['test'] == 1) {
                    $healthData['checks']['database'] = [
                        'status' => 'ok',
                        'message' => 'Database connection successful'
                    ];
                } else {
                    $healthData['checks']['database'] = [
                        'status' => 'error',
                        'message' => 'Database query failed'
                    ];
                    $allHealthy = false;
                }
            } catch (Exception $e) {
                $healthData['checks']['database'] = [
                    'status' => 'error',
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ];
                $allHealthy = false;
            }
        }

        // Check file permissions
        $permissionChecks = [
            'uploads' => '../uploads/',
            'uploads_covers' => '../uploads/covers/'
        ];

        $permissionErrors = [];
        foreach ($permissionChecks as $name => $path) {
            if (file_exists($path)) {
                if (!is_writable($path)) {
                    $permissionErrors[] = "$name directory not writable";
                }
            } else {
                // Try to create directory
                if (!@mkdir($path, 0755, true)) {
                    $permissionErrors[] = "$name directory missing and cannot be created";
                }
            }
        }

        if (empty($permissionErrors)) {
            $healthData['checks']['permissions'] = [
                'status' => 'ok',
                'message' => 'File permissions correct'
            ];
        } else {
            $healthData['checks']['permissions'] = [
                'status' => 'warning',
                'message' => 'Permission issues: ' . implode(', ', $permissionErrors)
            ];
        }

        // Check optional services
        $optionalChecks = [];

        // Check if MailHelper is available
        if (class_exists('MailHelper') && method_exists('MailHelper', 'isAvailable')) {
            $optionalChecks['email'] = MailHelper::isAvailable() ? 'available' : 'not configured';
        }

        // Check if OAuth is available
        if (class_exists('OAuthHelper') && method_exists('OAuthHelper', 'isEnabled')) {
            $optionalChecks['oauth'] = OAuthHelper::isEnabled() ? 'enabled' : 'disabled';
        }

        // Check if push notifications are available
        if (class_exists('NotificationHelper') && method_exists('NotificationHelper', 'isAvailable')) {
            $optionalChecks['push_notifications'] = NotificationHelper::isAvailable() ? 'available' : 'not configured';
        }

        if (!empty($optionalChecks)) {
            $healthData['checks']['optional_services'] = [
                'status' => 'info',
                'services' => $optionalChecks
            ];
        }

        // Version information
        if (class_exists('CollectionManager\Database') && method_exists('Database', 'getCurrentVersion')) {
            try {
                $dbVersion = Database::getCurrentVersion();
                $healthData['database_version'] = $dbVersion;
                
                $healthData['checks']['database_version'] = [
                    'status' => 'ok',
                    'version' => $dbVersion,
                    'message' => "Database schema version: $dbVersion"
                ];
            } catch (Exception $e) {
                $healthData['checks']['database_version'] = [
                    'status' => 'warning',
                    'message' => 'Could not determine database version'
                ];
            }
        }

    } else {
        $healthData['checks']['functions_loaded'] = [
            'status' => 'error',
            'message' => 'Could not load core functions'
        ];
        $allHealthy = false;
    }

} catch (Exception $e) {
    $healthData['checks']['general'] = [
        'status' => 'error',
        'message' => 'Health check failed: ' . $e->getMessage()
    ];
    $allHealthy = false;
} catch (Error $e) {
    $healthData['checks']['general'] = [
        'status' => 'error',
        'message' => 'Health check error: ' . $e->getMessage()
    ];
    $allHealthy = false;
}

// Set overall status
if ($allHealthy) {
    $healthData['status'] = 'healthy';
    http_response_code(200);
} else {
    $healthData['status'] = 'unhealthy';
    http_response_code(503); // Service Unavailable
}

// Add summary
$healthData['summary'] = [
    'total_checks' => count($healthData['checks']),
    'passed' => count(array_filter($healthData['checks'], function($check) {
        return isset($check['status']) && $check['status'] === 'ok';
    })),
    'warnings' => count(array_filter($healthData['checks'], function($check) {
        return isset($check['status']) && $check['status'] === 'warning';
    })),
    'errors' => count(array_filter($healthData['checks'], function($check) {
        return isset($check['status']) && $check['status'] === 'error';
    }))
];

// Output JSON response
echo json_encode($healthData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit; 