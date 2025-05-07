<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear cookies if any
if (isset($_COOKIE['email'])) {
    setcookie("email", "", time() - 3600, "/");
}

// Redirect to login page
header("Location: signin.php");
exit;
?>