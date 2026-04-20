<?php
/**
 * Check database for admin users
 */
require_once 'config.php';

echo "Admin users in database:\n";
$result = $conn->query('SELECT id, email, first_name, last_name, created_at FROM users WHERE role = "admin"');

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- ID: " . $row['id'] . "\n";
        echo "  Email: " . $row['email'] . "\n";
        echo "  Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        echo "  Created: " . $row['created_at'] . "\n";
        echo "\n";
    }
} else {
    echo "No admin users found\n";
}
?>
