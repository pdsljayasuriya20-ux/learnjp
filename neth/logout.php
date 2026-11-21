<?php
session_start();

// 1. සියලු Session Variables ඉවත් කිරීම
$_SESSION = array();

// 2. Server එකේ Session එක සම්පූර්ණයෙන්ම විනාශ කිරීම
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Session එක අවසන් කිරීම
session_destroy();

// 4. Client ව නැවත Login පිටුවට යොමු කිරීම
header("Location: login.php?logout=success");
exit();
?>