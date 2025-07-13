<?php
/**
 * Database Status Checker
 * This script checks the current database status and version
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Database Status Checker\n";
echo "======================\n\n";

try {
    // Include the functions file
    require_once 'includes/functions.php';
    echo "✓ Functions loaded successfully\n";
    
    // Get version information
    $currentVersion = Database::getCurrentVersion();
    $installedVersion = Database::getInstalledVersion();
    
    echo "\nVersion Information:\n";
    echo "- Current version: $currentVersion\n";
    echo "- Installed version: $installedVersion\n";
    
    if ($installedVersion >= $currentVersion) {
        echo "✓ Database is up to date\n";
    } else {
        echo "⚠ Database needs migration (run run_migrations.php)\n";
    }
    
    // Check tables
    echo "\nTable Status:\n";
    $tables = [
        'users' => 'Gebruikers',
        'groups' => 'Groepen', 
        'permissions' => 'Rechten',
        'user_groups' => 'Gebruiker-Groepen',
        'group_permissions' => 'Groep-Rechten',
        'collection_items' => 'Collectie Items',
        'sessions' => 'Sessies',
        'shared_links' => 'Gedeelde Links',
        'database_migrations' => 'Database Migraties'
    ];
    
    foreach ($tables as $table => $description) {
        $tableName = Environment::getTableName($table);
        $sql = "SHOW TABLES LIKE '$tableName'";
        $stmt = Database::query($sql);
        if ($stmt->rowCount() > 0) {
            echo "✓ $description table exists\n";
        } else {
            echo "❌ $description table missing\n";
        }
    }
    
    // Check data
    echo "\nData Status:\n";
    
    // Check users
    $usersTable = Environment::getTableName('users');
    $sql = "SELECT COUNT(*) as count FROM `$usersTable`";
    $stmt = Database::query($sql);
    $userCount = $stmt->fetch()['count'];
    echo "- Users: $userCount\n";
    
    // Check groups
    $groupsTable = Environment::getTableName('groups');
    $sql = "SELECT COUNT(*) as count FROM `$groupsTable`";
    $stmt = Database::query($sql);
    $groupCount = $stmt->fetch()['count'];
    echo "- Groups: $groupCount\n";
    
    // Check permissions
    $permissionsTable = Environment::getTableName('permissions');
    $sql = "SELECT COUNT(*) as count FROM `$permissionsTable`";
    $stmt = Database::query($sql);
    $permissionCount = $stmt->fetch()['count'];
    echo "- Permissions: $permissionCount\n";
    
    // Check collection items
    $collectionItemsTable = Environment::getTableName('collection_items');
    $sql = "SELECT COUNT(*) as count FROM `$collectionItemsTable`";
    $stmt = Database::query($sql);
    $itemCount = $stmt->fetch()['count'];
    echo "- Collection items: $itemCount\n";
    
    // Check migrations
    $sql = "SELECT COUNT(*) as count FROM database_migrations";
    $stmt = Database::query($sql);
    $migrationCount = $stmt->fetch()['count'];
    echo "- Migrations executed: $migrationCount\n";
    
    // Show migration history
    if ($migrationCount > 0) {
        echo "\nMigration History:\n";
        $sql = "SELECT version, migration_name, executed_at FROM database_migrations ORDER BY version";
        $stmt = Database::query($sql);
        $migrations = $stmt->fetchAll();
        
        foreach ($migrations as $migration) {
            echo "- v{$migration['version']}: {$migration['migration_name']} ({$migration['executed_at']})\n";
        }
    }
    
    // Recommendations
    echo "\nRecommendations:\n";
    if ($installedVersion < $currentVersion) {
        echo "⚠ Run 'php run_migrations.php' to update database\n";
    }
    
    if ($userCount == 0) {
        echo "⚠ No users found - run 'php run_migrations.php' to create default admin user\n";
    }
    
    if ($groupCount == 0) {
        echo "⚠ No groups found - run 'php run_migrations.php' to create default groups\n";
    }
    
    if ($permissionCount == 0) {
        echo "⚠ No permissions found - run 'php run_migrations.php' to create default permissions\n";
    }
    
    if ($installedVersion >= $currentVersion && $userCount > 0 && $groupCount > 0 && $permissionCount > 0) {
        echo "✓ Database is ready for use\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error checking database status:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nPlease check your database configuration.\n";
    exit(1);
}
?> 