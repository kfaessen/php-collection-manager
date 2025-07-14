<?php
/**
 * Functions and class loader
 * Loads all classes and creates aliases for easy usage
 */

// Load all required classes
require_once __DIR__ . '/Environment.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/APIManager.php';
require_once __DIR__ . '/CollectionManager.php';
require_once __DIR__ . '/Authentication.php';
require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/MailHelper.php';
require_once __DIR__ . '/TOTPHelper.php';
require_once __DIR__ . '/OAuthHelper.php';
require_once __DIR__ . '/I18nHelper.php';
require_once __DIR__ . '/MetadataEnricher.php';
require_once __DIR__ . '/NotificationHelper.php';
require_once __DIR__ . '/EmailVerificationHelper.php';

// Create aliases for easier usage (zonder namespace)
use CollectionManager\Environment;
use CollectionManager\Database;
use CollectionManager\Utils;
use CollectionManager\APIManager;
use CollectionManager\CollectionManager;
use CollectionManager\Authentication;
use CollectionManager\UserManager;
use CollectionManager\MailHelper;
use CollectionManager\TOTPHelper;
use CollectionManager\OAuthHelper;
use CollectionManager\I18nHelper;
use CollectionManager\MetadataEnricher;

// Class aliases (optioneel - voor backward compatibility)
class_alias('CollectionManager\Utils', 'Utils');
class_alias('CollectionManager\APIManager', 'APIManager');
class_alias('CollectionManager\CollectionManager', 'CollectionManager');
class_alias('CollectionManager\Database', 'Database');
class_alias('CollectionManager\Environment', 'Environment');
class_alias('CollectionManager\Authentication', 'Authentication');
class_alias('CollectionManager\UserManager', 'UserManager');
class_alias('CollectionManager\\MailHelper', 'MailHelper');
class_alias('CollectionManager\TOTPHelper', 'TOTPHelper');
class_alias('CollectionManager\OAuthHelper', 'OAuthHelper');
class_alias('CollectionManager\I18nHelper', 'I18nHelper');
class_alias('CollectionManager\MetadataEnricher', 'MetadataEnricher');

// Initialize database connection
Database::init();

// Initialize authentication system
Authentication::init();

// Initialize internationalization system
I18nHelper::init();

// Initialize metadata enrichment system
MetadataEnricher::init(); 