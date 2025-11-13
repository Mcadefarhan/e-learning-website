<?php
session_start();

// Destroy all session data
$_SESSION = [];

// Destroy the cookie if exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session completely
session_destroy();

// Redirect to homepage
header("Location: index.php");
exit;
?>
