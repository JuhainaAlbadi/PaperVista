<?php
/**
 * Create new admin user
 */
require_once 'config.php';

if ($argc < 5) {
    echo "Error: Missing parameters\n";
    exit(1);
}

$email = $argv[1];
$first_name = $argv[2];
$last_name = $argv[3];
$password = $argv[4];

if (strlen($password) < 6) {
    echo "Error: Password must be at least 6 characters\n";
    exit(1);
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO users (email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, "admin")');
$stmt->bind_param('ssss', $email, $hashed, $first_name, $last_name);

if ($stmt->execute()) {
    echo "✓ Admin user created!\n";
    echo "Email: " . $email . "\n";
    echo "Name: " . $first_name . " " . $last_name . "\n";
} else {
    echo "Error: " . $stmt->error . "\n";
    exit(1);
}
?>
