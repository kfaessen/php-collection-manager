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

// Create aliases for easier usage (zonder namespace)
use CollectionManager\Environment;
use CollectionManager\Database;
use CollectionManager\Utils;
use CollectionManager\APIManager;
use CollectionManager\CollectionManager;

// Class aliases (optioneel - voor backward compatibility)
class_alias('CollectionManager\Utils', 'Utils');
class_alias('CollectionManager\APIManager', 'APIManager');
class_alias('CollectionManager\CollectionManager', 'CollectionManager');
class_alias('CollectionManager\Database', 'Database');
class_alias('CollectionManager\Environment', 'Environment');

// Initialize database connection
Database::init(); 