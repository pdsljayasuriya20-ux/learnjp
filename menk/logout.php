<?php
// Session එක ආරම්භ කිරීම
session_start();

// සියලුම session variables ඉවත් කිරීම
$_SESSION = array();

// Session Cookie එක ඇත්නම් එය මැකීම (Browser එකේ session cookie clear කිරීමට)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session එක සම්පූර්ණයෙන්ම විනාශ කිරීම (Destroy)
session_destroy();

// පරිශීලකයා Login පිටුවට Redirect කිරීම
header("Location: login.php");
exit;
?>