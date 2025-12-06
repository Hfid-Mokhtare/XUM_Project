<?php
// 1. Start the session so we can access it.
session_start();

// 2. Unset all of the session variables.
$_SESSION = array();

// 3. Destroy the session.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Redirect to the login page.
header("Location: login.php");
exit;
?>