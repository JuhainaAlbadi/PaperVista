<?php
/**
 * Admin Authentication Test Script
 * Run this to diagnose admin access issues
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "================================\n";
echo "PaperVista Admin Access Test\n";
echo "================================\n\n";

// Test 1: Check admin user exists
echo "1. Checking for admin user in database...\n";
$result = $conn->query("SELECT id, email, role FROM users WHERE email = 'admin@university.edu'");
if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "   ✓ Admin user found\n";
    echo "   - ID: " . $admin['id'] . "\n";
    echo "   - Email: " . $admin['email'] . "\n";
    echo "   - Role: " . $admin['role'] . "\n";
} else {
    echo "   ✗ Admin user NOT found\n";
    echo "   - Please create admin user or check database\n";
}

// Test 2: Check session support
echo "\n2. Checking session support...\n";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "   ✓ Session is active\n";
} else if (session_status() == PHP_SESSION_NONE) {
    echo "   ⚠ Session not started (will be started on login)\n";
} else {
    echo "   ✗ Session disabled\n";
}

// Test 3: Check authentication functions
echo "\n3. Testing authentication functions...\n";
echo "   - isLoggedIn() function: " . (function_exists('isLoggedIn') ? "✓" : "✗") . "\n";
echo "   - isAdmin() function: " . (function_exists('isAdmin') ? "✓" : "✗") . "\n";
echo "   - requireAdmin() function: " . (function_exists('requireAdmin') ? "✓" : "✗") . "\n";

// Test 4: Check password hashing
echo "\n4. Testing password hashing...\n";
$test_password = "TestPassword123!";
$hashed = password_hash($test_password, PASSWORD_DEFAULT);
if (password_verify($test_password, $hashed)) {
    echo "   ✓ Password hashing works correctly\n";
} else {
    echo "   ✗ Password hashing issue\n";
}

// Test 5: Check admin files exist
echo "\n5. Checking admin files...\n";
$admin_files = [
    'admin/index.php',
    'admin/papers.php',
    'admin/users.php',
    'login.php',
    'logout.php'
];

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        echo "   ✓ " . $file . "\n";
    } else {
        echo "   ✗ " . $file . " NOT FOUND\n";
    }
}

// Test 6: Simulate login test
echo "\n6. Testing login simulation...\n";
$test_email = "admin@university.edu";
$test_password = "admin123"; // Change to your actual password

$sql = "SELECT id, email, role, password FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    echo "   ✓ User found in database\n";
    
    // Test password verification
    if (password_verify($test_password, $user['password'])) {
        echo "   ✓ Password verification successful\n";
        echo "   ✓ User role: " . $user['role'] . "\n";
        if ($user['role'] === 'admin') {
            echo "   ✓ User is admin!\n";
        } else {
            echo "   ✗ User is not admin (role: " . $user['role'] . ")\n";
        }
    } else {
        echo "   ✗ Password verification failed\n";
        echo "   - Make sure you're using the correct password\n";
        echo "   - Test email: " . $test_email . "\n";
        echo "   - Test password: " . $test_password . " (change this to your actual password)\n";
    }
} else {
    echo "   ✗ Admin user not found with email: " . $test_email . "\n";
}

// Test 7: Check database connection
echo "\n7. Testing database connection...\n";
if ($conn && !$conn->connect_error) {
    echo "   ✓ Database connection successful\n";
    echo "   - Database: " . $conn->get_server_info() . "\n";
} else {
    echo "   ✗ Database connection failed\n";
    if ($conn) {
        echo "   - Error: " . $conn->connect_error . "\n";
    }
}

echo "\n================================\n";
echo "Test Summary\n";
echo "================================\n";
echo "\nHow to access admin dashboard:\n";
echo "1. Go to: http://localhost/php_summarizer/login.php\n";
echo "2. Enter: admin@university.edu\n";
echo "3. Enter your password\n";
echo "4. Click Sign In\n";
echo "5. Navigate to: /admin/index.php\n";
echo "\nIf tests show errors, review the messages above.\n";
?>
