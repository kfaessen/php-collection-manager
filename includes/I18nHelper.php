<?php
namespace CollectionManager;

use CollectionManager\Environment;
use CollectionManager\Database;
use CollectionManager\Utils;

class I18nHelper 
{
    private static $currentLanguage = null;
    private static $translations = [];
    private static $loadedCategories = [];
    private static $languages = null;
    private static $sessionStarted = false;
    
    /**
     * Initialize i18n system
     */
    public static function init() 
    {
        if (!self::$sessionStarted) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            self::$sessionStarted = true;
        }
        
        // Detect and set language
        self::detectLanguage();
        
        // Load core translations
        self::loadTranslations('core');
    }
    
    /**
     * Check if i18n is enabled
     */
    public static function isEnabled() 
    {
        return Environment::get('I18N_ENABLED', true);
    }
    
    /**
     * Get current language
     */
    public static function getCurrentLanguage() 
    {
        if (self::$currentLanguage === null) {
            self::detectLanguage();
        }
        return self::$currentLanguage;
    }
    
    /**
     * Set current language
     */
    public static function setLanguage($languageCode) 
    {
        if (self::isLanguageSupported($languageCode)) {
            self::$currentLanguage = $languageCode;
            $_SESSION['user_language'] = $languageCode;
            
            // Clear loaded translations when language changes
            self::$translations = [];
            self::$loadedCategories = [];
            
            return true;
        }
        return false;
    }
    
    /**
     * Detect user's preferred language
     */
    private static function detectLanguage() 
    {
        $language = null;
        
        // 1. Check URL parameter
        if (isset($_GET['lang']) && self::isLanguageSupported($_GET['lang'])) {
            $language = $_GET['lang'];
        }
        
        // 2. Check session
        elseif (isset($_SESSION['user_language']) && self::isLanguageSupported($_SESSION['user_language'])) {
            $language = $_SESSION['user_language'];
        }
        
        // 3. Check user preference if logged in
        elseif (class_exists('Authentication') && Authentication::isLoggedIn()) {
            $user = Authentication::getCurrentUser();
            if (isset($user['preferred_language']) && self::isLanguageSupported($user['preferred_language'])) {
                $language = $user['preferred_language'];
            }
        }
        
        // 4. Auto-detect from browser if enabled
        elseif (Environment::get('AUTO_DETECT_LANGUAGE', true)) {
            $language = self::detectBrowserLanguage();
        }
        
        // 5. Fall back to default
        if (!$language) {
            $language = Environment::get('DEFAULT_LANGUAGE', 'nl');
        }
        
        self::$currentLanguage = $language;
        $_SESSION['user_language'] = $language;
    }
    
    /**
     * Detect language from browser Accept-Language header
     */
    private static function detectBrowserLanguage() 
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }
        
        $supportedLanguages = self::getSupportedLanguages();
        $acceptLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        
        // Parse Accept-Language header
        preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/i', $acceptLanguages, $matches);
        
        $languages = [];
        foreach ($matches[1] as $index => $lang) {
            $quality = isset($matches[2][$index]) && $matches[2][$index] !== '' ? (float)$matches[2][$index] : 1.0;
            $langCode = substr(strtolower($lang), 0, 2); // Get language code without region
            $languages[$langCode] = $quality;
        }
        
        // Sort by quality
        arsort($languages);
        
        // Find first supported language
        foreach ($languages as $langCode => $quality) {
            if (in_array($langCode, $supportedLanguages)) {
                return $langCode;
            }
        }
        
        return null;
    }
    
    /**
     * Check if language is supported
     */
    public static function isLanguageSupported($languageCode) 
    {
        $supportedLanguages = self::getSupportedLanguages();
        return in_array($languageCode, $supportedLanguages);
    }
    
    /**
     * Get list of supported languages
     */
    public static function getSupportedLanguages() 
    {
        return Environment::get('SUPPORTED_LANGUAGES', ['nl', 'en']);
    }
    
    /**
     * Get all available languages
     */
    public static function getAvailableLanguages() 
    {
        if (self::$languages === null) {
            try {
                $languagesTable = Environment::getTableName('languages');
                $sql = "SELECT * FROM `$languagesTable` WHERE is_active = 1 ORDER BY sort_order, name";
                $stmt = Database::query($sql);
                self::$languages = $stmt->fetchAll();
            } catch (\Exception $e) {
                // Fallback if database not available
                self::$languages = [
                    ['code' => 'nl', 'name' => 'Dutch', 'native_name' => 'Nederlands', 'is_rtl' => 0],
                    ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_rtl' => 0]
                ];
            }
        }
        
        return self::$languages;
    }
    
    /**
     * Get language info by code
     */
    public static function getLanguageInfo($languageCode) 
    {
        $languages = self::getAvailableLanguages();
        foreach ($languages as $language) {
            if ($language['code'] === $languageCode) {
                return $language;
            }
        }
        return null;
    }
    
    /**
     * Check if current language is RTL
     */
    public static function isRTL($languageCode = null) 
    {
        $languageCode = $languageCode ?? self::getCurrentLanguage();
        $languageInfo = self::getLanguageInfo($languageCode);
        return $languageInfo ? (bool)$languageInfo['is_rtl'] : false;
    }
    
    /**
     * Translate a string
     */
    public static function translate($key, $replacements = [], $category = 'general', $languageCode = null) 
    {
        if (!self::isEnabled()) {
            return $key;
        }
        
        $languageCode = $languageCode ?? self::getCurrentLanguage();
        
        // Load translations for category if not loaded
        if (!in_array($category, self::$loadedCategories)) {
            self::loadTranslations($category, $languageCode);
        }
        
        // Get translation
        $cacheKey = $languageCode . '.' . $category . '.' . $key;
        $translation = self::$translations[$cacheKey] ?? null;
        
        // Fallback to fallback language
        if ($translation === null && $languageCode !== Environment::get('FALLBACK_LANGUAGE', 'en')) {
            $fallbackKey = Environment::get('FALLBACK_LANGUAGE', 'en') . '.' . $category . '.' . $key;
            $translation = self::$translations[$fallbackKey] ?? null;
        }
        
        // Fallback to key
        if ($translation === null) {
            $translation = $key;
            
            // Log missing translation
            error_log("Missing translation: $key (category: $category, language: $languageCode)");
        }
        
        // Apply replacements
        if (!empty($replacements)) {
            foreach ($replacements as $placeholder => $value) {
                $translation = str_replace(':' . $placeholder, $value, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * Short alias for translate
     */
    public static function t($key, $replacements = [], $category = 'general', $languageCode = null) 
    {
        return self::translate($key, $replacements, $category, $languageCode);
    }
    
    /**
     * Pluralization helper
     */
    public static function pluralize($key, $count, $replacements = [], $category = 'general', $languageCode = null) 
    {
        $languageCode = $languageCode ?? self::getCurrentLanguage();
        
        // Simple pluralization rules (can be extended)
        $pluralKey = $count === 1 ? $key . '_singular' : $key . '_plural';
        
        $replacements['count'] = $count;
        
        return self::translate($pluralKey, $replacements, $category, $languageCode);
    }
    
    /**
     * Load translations from database
     */
    private static function loadTranslations($category, $languageCode = null) 
    {
        $languageCode = $languageCode ?? self::getCurrentLanguage();
        
        if (in_array($category, self::$loadedCategories)) {
            return;
        }
        
        try {
            $translationKeysTable = Environment::getTableName('translation_keys');
            $translationsTable = Environment::getTableName('translations');
            
            $sql = "SELECT tk.key_name, t.translation, t.language_code
                    FROM `$translationKeysTable` tk
                    LEFT JOIN `$translationsTable` t ON tk.id = t.key_id
                    WHERE tk.category = ? AND (t.language_code = ? OR t.language_code = ?)";
            
            $stmt = Database::query($sql, [
                $category, 
                $languageCode, 
                Environment::get('FALLBACK_LANGUAGE', 'en')
            ]);
            
            while ($row = $stmt->fetch()) {
                if ($row['translation'] !== null) {
                    $cacheKey = $row['language_code'] . '.' . $category . '.' . $row['key_name'];
                    self::$translations[$cacheKey] = $row['translation'];
                }
            }
            
            self::$loadedCategories[] = $category;
            
        } catch (\Exception $e) {
            // Fallback to default translations if database not available
            self::loadDefaultTranslations($category, $languageCode);
            self::$loadedCategories[] = $category;
        }
    }
    
    /**
     * Load default translations (fallback when database not available)
     */
    private static function loadDefaultTranslations($category, $languageCode) 
    {
        $translations = self::getDefaultTranslations();
        
        if (isset($translations[$category][$languageCode])) {
            foreach ($translations[$category][$languageCode] as $key => $translation) {
                $cacheKey = $languageCode . '.' . $category . '.' . $key;
                self::$translations[$cacheKey] = $translation;
            }
        }
    }
    
    /**
     * Get default translations (hardcoded fallback)
     */
    private static function getDefaultTranslations() 
    {
        return [
            'core' => [
                'nl' => [
                    'login' => 'Inloggen',
                    'logout' => 'Uitloggen',
                    'username' => 'Gebruikersnaam',
                    'password' => 'Wachtwoord',
                    'email' => 'E-mail',
                    'save' => 'Opslaan',
                    'cancel' => 'Annuleren',
                    'delete' => 'Verwijderen',
                    'edit' => 'Bewerken',
                    'add' => 'Toevoegen',
                    'back' => 'Terug',
                    'next' => 'Volgende',
                    'previous' => 'Vorige',
                    'search' => 'Zoeken',
                    'home' => 'Home',
                    'profile' => 'Profiel',
                    'settings' => 'Instellingen',
                    'language' => 'Taal'
                ],
                'en' => [
                    'login' => 'Login',
                    'logout' => 'Logout',
                    'username' => 'Username',
                    'password' => 'Password',
                    'email' => 'Email',
                    'save' => 'Save',
                    'cancel' => 'Cancel',
                    'delete' => 'Delete',
                    'edit' => 'Edit',
                    'add' => 'Add',
                    'back' => 'Back',
                    'next' => 'Next',
                    'previous' => 'Previous',
                    'search' => 'Search',
                    'home' => 'Home',
                    'profile' => 'Profile',
                    'settings' => 'Settings',
                    'language' => 'Language'
                ]
            ],
            'navigation' => [
                'nl' => [
                    'overview' => 'Overzicht',
                    'administration' => 'Beheer',
                    'my_collection' => 'Mijn collectie',
                    'share_collection' => 'Deel je collectie'
                ],
                'en' => [
                    'overview' => 'Overview',
                    'administration' => 'Administration',
                    'my_collection' => 'My collection',
                    'share_collection' => 'Share your collection'
                ]
            ]
        ];
    }
    
    /**
     * Add or update translation
     */
    public static function addTranslation($key, $translation, $languageCode, $category = 'general', $description = '') 
    {
        try {
            $translationKeysTable = Environment::getTableName('translation_keys');
            $translationsTable = Environment::getTableName('translations');
            
            // Insert or update translation key
            $sql = "INSERT INTO `$translationKeysTable` (key_name, category, description) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE category = VALUES(category), description = VALUES(description)";
            Database::query($sql, [$key, $category, $description]);
            
            // Get key ID
            $sql = "SELECT id FROM `$translationKeysTable` WHERE key_name = ?";
            $stmt = Database::query($sql, [$key]);
            $keyId = $stmt->fetch()['id'];
            
            // Insert or update translation
            $sql = "INSERT INTO `$translationsTable` (key_id, language_code, translation) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE translation = VALUES(translation)";
            Database::query($sql, [$keyId, $languageCode, $translation]);
            
            // Update cache
            $cacheKey = $languageCode . '.' . $category . '.' . $key;
            self::$translations[$cacheKey] = $translation;
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error adding translation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Format date according to current language
     */
    public static function formatDate($date, $format = null, $languageCode = null) 
    {
        if (!$date) return '';
        
        $languageCode = $languageCode ?? self::getCurrentLanguage();
        
        // Default formats per language
        $dateFormats = [
            'nl' => 'd-m-Y H:i',
            'en' => 'm/d/Y H:i',
            'de' => 'd.m.Y H:i',
            'fr' => 'd/m/Y H:i'
        ];
        
        $format = $format ?? ($dateFormats[$languageCode] ?? 'd-m-Y H:i');
        
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        
        return $date->format($format);
    }
    
    /**
     * Format number according to current language
     */
    public static function formatNumber($number, $decimals = 0, $languageCode = null) 
    {
        $languageCode = $languageCode ?? self::getCurrentLanguage();
        
        // Number formatting per language
        $numberFormats = [
            'nl' => ['dec_point' => ',', 'thousands_sep' => '.'],
            'en' => ['dec_point' => '.', 'thousands_sep' => ','],
            'de' => ['dec_point' => ',', 'thousands_sep' => '.'],
            'fr' => ['dec_point' => ',', 'thousands_sep' => ' ']
        ];
        
        $format = $numberFormats[$languageCode] ?? $numberFormats['en'];
        
        return number_format($number, $decimals, $format['dec_point'], $format['thousands_sep']);
    }
    
    /**
     * Update user's language preference
     */
    public static function updateUserLanguagePreference($userId, $languageCode) 
    {
        try {
            $usersTable = Environment::getTableName('users');
            $sql = "UPDATE `$usersTable` SET preferred_language = ? WHERE id = ?";
            Database::query($sql, [$languageCode, $userId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get language direction (ltr/rtl)
     */
    public static function getDirection($languageCode = null) 
    {
        return self::isRTL($languageCode) ? 'rtl' : 'ltr';
    }
    
    /**
     * Get available translations for a key
     */
    public static function getTranslationsForKey($key, $category = 'general') 
    {
        try {
            $translationKeysTable = Environment::getTableName('translation_keys');
            $translationsTable = Environment::getTableName('translations');
            
            $sql = "SELECT t.language_code, t.translation
                    FROM `$translationKeysTable` tk
                    JOIN `$translationsTable` t ON tk.id = t.key_id
                    WHERE tk.key_name = ? AND tk.category = ?";
            
            $stmt = Database::query($sql, [$key, $category]);
            $result = [];
            
            while ($row = $stmt->fetch()) {
                $result[$row['language_code']] = $row['translation'];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Clear translation cache
     */
    public static function clearCache() 
    {
        self::$translations = [];
        self::$loadedCategories = [];
        self::$languages = null;
    }
} 