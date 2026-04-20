<?php
// Logout script
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Redirect to home page with success message
header("Location: index.php?logout=success");
exit();
?>
