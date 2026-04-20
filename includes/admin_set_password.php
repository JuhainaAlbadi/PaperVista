<?php
/**
 * Set admin password
 */
require_once 'config.php';

if ($argc < 2) {
    echo "Error: Password not provided\n";
    exit(1);
}

$password = $argv[1];
$email = 'admin@university.edu';

if (strlen($password) < 6) {
    echo "Error: Password must be at least 6 characters\n";
    exit(1);
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
$stmt->bind_param('ss', $hashed, $email);

if ($stmt->execute()) {
    echo "✓ Password updated successfully!\n";
    echo "Email: " . $email . "\n";
    echo "Password: " . $password . "\n";
} else {
    echo "Error: " . $stmt->error . "\n";
    exit(1);
}
?>
