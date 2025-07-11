<?php
/**
 * Debug version of profile.php to identify errors
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting debug...\n";

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

// Test 8: Try to get current user
try {
    $currentUser = Authentication::getCurrentUser();
    echo "✓ Current user retrieved\n";
} catch (Exception $e) {
    die("ERROR getting current user: " . $e->getMessage());
}

// Test 9: Try to get user groups
try {
    if ($currentUser) {
        $userGroups = UserManager::getUserGroups($currentUser['id']);
        echo "✓ User groups retrieved\n";
    } else {
        echo "⚠ No current user (not logged in)\n";
    }
} catch (Exception $e) {
    die("ERROR getting user groups: " . $e->getMessage());
}

echo "All tests passed!\n";
?>