<?php
/**
 * user-logout.php  -  logs a public user out (the admin logout.php is separate)
 */
require_once __DIR__ . '/includes/auth.php';

// remove only the public-user keys, so an admin session in the same browser is not touched
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_photo'], $_SESSION['la_csrf']);

// if nothing else is left in the session, destroy it completely + clear the cookie
if (empty($_SESSION)) {
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
} else {
    session_regenerate_id(true);
}

header('Location: user-login.php?logged_out=1');
exit;
