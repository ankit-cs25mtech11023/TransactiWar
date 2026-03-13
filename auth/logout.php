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
// --- END LOGGING ---

// 2. Unset all of the session variables
session_unset();

// 3. Completely destroy the session on the server
session_destroy();

// 4. Redirect the user back to the login page
// Note: Using a relative path is okay, but an absolute path is slightly more robust.
header("Location: login.php");

// 5. Always call exit() after a header redirect
exit();
?>