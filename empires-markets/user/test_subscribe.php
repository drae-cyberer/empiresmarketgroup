<?php
// test_subscribe.php - Debug version
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "Starting debug...<br>";

// Test 1: Basic PHP is working
echo "PHP is working<br>";

// Test 2: Try to include config file
echo "Including config...<br>";
try {
    require_once '../includes/config.php';
    echo "Config loaded successfully<br>";
} catch (Exception $e) {
    die("Config error: " . $e->getMessage());
}

// Test 3: Test database connection
echo "Testing database connection...<br>";
try {
    if (isset($pdo)) {
        echo "PDO object exists<br>";
        $stmt = $pdo->query("SELECT 1");
        echo "Database connection works<br>";
    } else {
        die("PDO object not found");
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Test 4: Try to include auth file
echo "Including auth...<br>";
try {
    require_once '../includes/auth.php';
    echo "Auth loaded successfully<br>";
} catch (Exception $e) {
    die("Auth error: " . $e->getMessage());
}

// Test 5: Try to include functions file
echo "Including functions...<br>";
try {
    require_once '../includes/functions.php';
    echo "Functions loaded successfully<br>";
} catch (Exception $e) {
    die("Functions error: " . $e->getMessage());
}

// Test 6: Check if functions exist
echo "Testing required functions...<br>";
if (function_exists('require_login')) {
    echo "require_login() exists<br>";
} else {
    echo "require_login() is missing<br>";
}

if (function_exists('get_user_by_id')) {
    echo "get_user_by_id() exists<br>";
} else {
    echo "get_user_by_id() is missing<br>";
}

if (function_exists('format_currency')) {
    echo "format_currency() exists<br>";
} else {
    echo "format_currency() is missing<br>";
}

if (function_exists('generate_csrf_token')) {
    echo "generate_csrf_token() exists<br>";
} else {
    echo "generate_csrf_token() is missing<br>";
}

// Test 7: Test session
session_start();
echo "Session started<br>";

// Test 8: Check if logged in
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "No user session found<br>";
}

// Test 9: Test plan query
echo "Testing plan query...<br>";
try {
    $plan_id = 1; // Test with plan ID 1
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = :plan_id");
    $stmt->execute([':plan_id' => $plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plan) {
        echo "Plan found: " . $plan['name'] . "<br>";
    } else {
        echo "No plan found with ID 1<br>";
    }
} catch (Exception $e) {
    echo "Plan query error: " . $e->getMessage() . "<br>";
}

echo "All tests completed successfully!<br>";
?>