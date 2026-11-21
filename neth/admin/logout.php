<?php
// Session එක ආරම්භ කරන්න
session_start();

// සියලුම Session විචල්‍යයන් හිස් කරන්න
$_SESSION = array();

// Session Cookie එක විනාශ කරන්න (ඇත්නම්)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session එක සම්පූර්ණයෙන්ම විනාශ කරන්න
session_destroy();

// Login පිටුවට යොමු කරන්න
header("Location: login.php");
exit;
?>