<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session memory on server
session_destroy();

// Redirect back to login page
header("Location: login.html");
exit;
?>