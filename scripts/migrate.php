#!/usr/bin/env php
<?php
/**
 * Database Migration Management Script
 * Usage: php scripts/migrate.php [command] [options]
 */

// Load dependencies
require_once __DIR__ . '/../includes/Environment.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/MigrationHelper.php';

use CollectionManager\Environment;
use CollectionManager\Database;
use CollectionManager\MigrationHelper;

// Initialize environment
Environment::init();

// Parse command line arguments
$command = $argv[1] ?? 'help';
$options = array_slice($argv, 2);

echo "🔄 Database Migration Manager\n";
echo "=============================\n\n";

switch ($command) {
    case 'status':
        showStatus();
        break;
        
    case 'migrate':
        runMigrations();
        break;
        
    case 'rollback':
        $version = $options[0] ?? null;
        if ($version === null) {
            echo "❌ Error: Please specify version to rollback to\n";
            echo "Usage: php scripts/migrate.php rollback [version]\n";
            exit(1);
        }
        rollbackTo((int)$version);
        break;
        
    case 'generate':
        $name = $options[0] ?? null;
        $description = $options[1] ?? '';
        if ($name === null) {
            echo "❌ Error: Please specify migration name\n";
            echo "Usage: php scripts/migrate.php generate [name] [description]\n";
            exit(1);
        }
        generateMigration($name, $description);
        break;
        
    case 'help':
    default:
        showHelp();
        break;
}

/**
 * Show migration status
 */
function showStatus() {
    try {
        $status = MigrationHelper::getStatus();
        
        echo "📊 Migration Status:\n";
        echo "Current Version: {$status['current_version']}\n";
        echo "Target Version: {$status['target_version']}\n";
        echo "Needs Migration: " . ($status['needs_migration'] ? 'Yes' : 'No') . "\n\n";
        
        if (!empty($status['executed_migrations'])) {
            echo "✅ Executed Migrations:\n";
            foreach ($status['executed_migrations'] as $migration) {
                echo "  - v{$migration['version']}: {$migration['migration_name']} ({$migration['executed_at']})\n";
            }
            echo "\n";
        }
        
        if (!empty($status['pending_migrations'])) {
            echo "⏳ Pending Migrations:\n";
            foreach ($status['pending_migrations'] as $migration) {
                echo "  - v{$migration['version']}: {$migration['status']}\n";
            }
            echo "\n";
        }
        
        if (!$status['needs_migration']) {
            echo "✅ Database is up to date!\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error getting migration status: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Run pending migrations
 */
function runMigrations() {
    try {
        echo "🔄 Running pending migrations...\n\n";
        
        $result = MigrationHelper::runPendingMigrations();
        
        if ($result['status'] === 'success') {
            echo "✅ " . $result['message'] . "\n";
            
            if (!empty($result['executed_migrations'])) {
                echo "\nExecuted migrations:\n";
                foreach ($result['executed_migrations'] as $migration) {
                    echo "  - v{$migration['version']}: {$migration['name']} ({$migration['executed_at']})\n";
                }
            }
        } else {
            echo "❌ " . $result['message'] . "\n";
            if (isset($result['failed_migration'])) {
                echo "Failed migration: {$result['failed_migration']}\n";
            }
            exit(1);
        }
        
    } catch (Exception $e) {
        echo "❌ Error running migrations: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Rollback to specific version
 */
function rollbackTo($version) {
    try {
        echo "🔄 Rolling back to version $version...\n\n";
        
        $result = MigrationHelper::rollback($version);
        
        if ($result['status'] === 'success') {
            echo "✅ " . $result['message'] . "\n";
            
            if (!empty($result['rolled_back'])) {
                echo "\nRolled back migrations:\n";
                foreach ($result['rolled_back'] as $migration) {
                    echo "  - v{$migration['version']}: {$migration['name']} ({$migration['rolled_back_at']})\n";
                }
            }
        } else {
            echo "❌ " . $result['message'] . "\n";
            if (isset($result['failed_version'])) {
                echo "Failed at version: {$result['failed_version']}\n";
            }
            exit(1);
        }
        
    } catch (Exception $e) {
        echo "❌ Error during rollback: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Generate new migration
 */
function generateMigration($name, $description) {
    try {
        echo "📝 Generating migration: $name\n\n";
        
        $result = MigrationHelper::generateMigration($name, $description);
        
        echo "✅ Migration generated successfully!\n";
        echo "Version: {$result['version']}\n";
        echo "File: {$result['filename']}\n";
        echo "Class: {$result['class']}\n";
        echo "Path: {$result['filepath']}\n\n";
        
        echo "📝 Next steps:\n";
        echo "1. Edit the migration file: {$result['filename']}\n";
        echo "2. Add your SQL statements to the up() method\n";
        echo "3. Optionally add rollback SQL to the down() method\n";
        echo "4. Update the \$currentVersion in Database.php to {$result['version']}\n";
        echo "5. Run: php scripts/migrate.php migrate\n";
        
    } catch (Exception $e) {
        echo "❌ Error generating migration: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Show help
 */
function showHelp() {
    echo "Database Migration Commands:\n\n";
    echo "📊 status                    - Show current migration status\n";
    echo "🔄 migrate                   - Run all pending migrations\n";
    echo "↩️  rollback [version]        - Rollback to specific version\n";
    echo "📝 generate [name] [desc]    - Generate new migration file\n";
    echo "❓ help                      - Show this help message\n\n";
    
    echo "Examples:\n";
    echo "  php scripts/migrate.php status\n";
    echo "  php scripts/migrate.php migrate\n";
    echo "  php scripts/migrate.php generate add_user_preferences\n";
    echo "  php scripts/migrate.php generate add_notifications_table \"Add push notification support\"\n";
    echo "  php scripts/migrate.php rollback 1\n\n";
    
    echo "Migration File Structure:\n";
    echo "  migrations/\n";
    echo "    ├── migration_1_20241220120000_initial_setup.php\n";
    echo "    ├── migration_2_20241220130000_add_notifications.php\n";
    echo "    └── migration_3_20241220140000_add_user_preferences.php\n\n";
    
    echo "Database Prefix: " . Environment::get('DB_PREFIX', 'none') . "\n";
    echo "Database Name: " . Environment::get('DB_NAME', 'collection_manager') . "\n";
} 