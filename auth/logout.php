<?php
// 1. Safely resume the session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- LOGOUT ACTIVITY ---
// We must log the activity *before* we destroy the session data.
require_once '../config/db_connect.php';
require_once '../includes/logger.php';

// Log the event, using the username from the session if it exists.
if (isset($_SESSION['username'])) {
    log_activity($conn, $_SERVER['REQUEST_URI'], $_SESSION['username'], $_SERVER['REMOTE_ADDR']);
}

// 2. Unset all of the session variables in memory
$_SESSION = array();

// 3. Poison and destroy the session cookie in the user's browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    // Set the cookie expiration date to the past to force the browser to delete it
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Completely destroy the session file on the server
session_destroy();

// 5. Redirect the user back to the login perimeter
header("Location: login.php");
exit();
?>