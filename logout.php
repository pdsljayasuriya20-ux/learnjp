<?php
require_once 'config.php';

// Log the logout activity
if (isLoggedIn()) {
    logActivity('logout', 'User logged out');
}

// Destroy session
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php?logged_out=1');
exit;
?>