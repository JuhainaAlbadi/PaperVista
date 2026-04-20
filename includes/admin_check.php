<?php
/**
 * Check current admin status
 */
require_once 'config.php';

$result = $conn->query('SELECT email, first_name, last_name FROM users WHERE role = "admin" LIMIT 1');
if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo 'Admin: ' . $admin['email'] . ' (' . $admin['first_name'] . ' ' . $admin['last_name'] . ')';
} else {
    echo 'No admin found';
}
?>
