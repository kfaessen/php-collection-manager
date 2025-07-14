<?php
/**
 * Language Switcher Endpoint
 * Handles language switching and user language preference updates
 */

// Include dependencies
require_once '../includes/functions.php';

// Check if setup is needed
if (Database::needsSetup()) {
    header('Location: setup.php');
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$language = $_GET['lang'] ?? $_POST['language'] ?? '';
$redirect = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'index.php';

try {
    
    switch ($action) {
        case 'switch':
            handleLanguageSwitch($language, $redirect);
            break;
            
        case 'get_languages':
            handleGetLanguages();
            break;
            
        case 'update_preference':
            handleUpdatePreference($language);
            break;
            
        default:
            // Default action is switch language
            if (!empty($language)) {
                handleLanguageSwitch($language, $redirect);
            } else {
                header('Location: ' . $redirect);
                exit;
            }
    }
    
} catch (Exception $e) {
    error_log('Language Error: ' . $e->getMessage());
    
    // For AJAX requests, return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    
    // For regular requests, redirect back with error
    header('Location: ' . $redirect . (strpos($redirect, '?') !== false ? '&' : '?') . 'error=' . urlencode($e->getMessage()));
    exit;
}

/**
 * Handle language switching
 */
function handleLanguageSwitch($language, $redirect) {
    if (empty($language)) {
        throw new Exception('Geen taal opgegeven');
    }
    
    if (!I18nHelper::isLanguageSupported($language)) {
        throw new Exception('Taal wordt niet ondersteund');
    }
    
    // Set language
    $success = I18nHelper::setLanguage($language);
    
    if (!$success) {
        throw new Exception('Kon taal niet wijzigen');
    }
    
    // Update user preference if logged in
    if (Authentication::isLoggedIn()) {
        $userId = Authentication::getCurrentUserId();
        I18nHelper::updateUserLanguagePreference($userId, $language);
    }
    
    // For AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'language' => $language,
            'message' => I18nHelper::t('language_changed_success', ['language' => I18nHelper::getLanguageInfo($language)['native_name'] ?? $language])
        ]);
        exit;
    }
    
    // Redirect back to referring page
    header('Location: ' . $redirect);
    exit;
}

/**
 * Handle getting available languages (AJAX)
 */
function handleGetLanguages() {
    header('Content-Type: application/json');
    
    $languages = I18nHelper::getAvailableLanguages();
    $currentLanguage = I18nHelper::getCurrentLanguage();
    
    $result = [
        'success' => true,
        'current_language' => $currentLanguage,
        'languages' => $languages,
        'is_rtl' => I18nHelper::isRTL($currentLanguage),
        'direction' => I18nHelper::getDirection($currentLanguage)
    ];
    
    echo json_encode($result);
    exit;
}

/**
 * Handle updating user language preference
 */
function handleUpdatePreference($language) {
    if (!Authentication::isLoggedIn()) {
        throw new Exception('Je moet ingelogd zijn om je taalvoorkeur bij te werken');
    }
    
    if (empty($language)) {
        throw new Exception('Geen taal opgegeven');
    }
    
    if (!I18nHelper::isLanguageSupported($language)) {
        throw new Exception('Taal wordt niet ondersteund');
    }
    
    $userId = Authentication::getCurrentUserId();
    $success = I18nHelper::updateUserLanguagePreference($userId, $language);
    
    if (!$success) {
        throw new Exception('Kon taalvoorkeur niet bijwerken');
    }
    
    // Also set current session language
    I18nHelper::setLanguage($language);
    
    // For AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => I18nHelper::t('language_preference_updated')
        ]);
        exit;
    }
    
    header('Location: profile.php?success=' . urlencode('Taalvoorkeur bijgewerkt'));
    exit;
}
?> 