<?php
/**
 * Debug version of admin.php to identify errors
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting admin debug...\n";

// Test 1: Check if includes directory exists
if (!file_exists('../includes/functions.php')) {
    die("ERROR: ../includes/functions.php not found");
}
echo "✓ includes/functions.php exists\n";

// Test 2: Try to include functions.php
try {
    require_once '../includes/functions.php';
    echo "✓ functions.php loaded successfully\n";
} catch (Exception $e) {
    die("ERROR loading functions.php: " . $e->getMessage());
}

// Test 3: Check if Authentication class exists
if (!class_exists('Authentication')) {
    die("ERROR: Authentication class not found");
}
echo "✓ Authentication class exists\n";

// Test 4: Try to initialize Authentication
try {
    Authentication::init();
    echo "✓ Authentication initialized\n";
} catch (Exception $e) {
    die("ERROR initializing Authentication: " . $e->getMessage());
}

// Test 5: Check if Database class exists
if (!class_exists('Database')) {
    die("ERROR: Database class not found");
}
echo "✓ Database class exists\n";

// Test 6: Try to initialize Database
try {
    Database::init();
    echo "✓ Database initialized\n";
} catch (Exception $e) {
    die("ERROR initializing Database: " . $e->getMessage());
}

// Test 7: Check if UserManager class exists
if (!class_exists('UserManager')) {
    die("ERROR: UserManager class not found");
}
echo "✓ UserManager class exists\n";

// Test 8: Check if current user has admin permission
try {
    $hasPermission = Authentication::hasPermission('system_admin');
    echo "✓ Permission check completed\n";
} catch (Exception $e) {
    die("ERROR checking permissions: " . $e->getMessage());
}

// Test 9: Try to get user stats
try {
    $userStats = UserManager::getUserStats();
    echo "✓ User stats retrieved\n";
} catch (Exception $e) {
    die("ERROR getting user stats: " . $e->getMessage());
}

// Test 10: Try to get all groups
try {
    $allGroups = UserManager::getAllGroups();
    echo "✓ All groups retrieved\n";
} catch (Exception $e) {
    die("ERROR getting all groups: " . $e->getMessage());
}

// Test 11: Try to get all permissions
try {
    $allPermissions = UserManager::getAllPermissions();
    echo "✓ All permissions retrieved\n";
} catch (Exception $e) {
    die("ERROR getting all permissions: " . $e->getMessage());
}

// Test 12: Try to get all users
try {
    $users = UserManager::getAllUsers(100, 0);
    echo "✓ All users retrieved\n";
} catch (Exception $e) {
    die("ERROR getting all users: " . $e->getMessage());
}

echo "All admin tests passed!\n";
?>