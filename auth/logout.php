<?php
// 1. Safely resume the session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Unset all of the session variables
session_unset();

// 3. Completely destroy the session on the server
session_destroy();

// 4. Redirect the user back to the login page
header("Location: /auth/login.php");

// 5. Always call exit() after a header redirect
exit();
?>