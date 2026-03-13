<?php
/**
 * Logs user activity to the database.
 *
 * @param mysqli $conn The database connection object.
 * @param string $webpage The URL of the page being accessed.
 * @param string $username The username of the user performing the action.
 * @param string $ip_address The IP address of the user.
 */
function log_activity($conn, $webpage, $username, $ip_address) {
    // Basic sanitization, though prepared statements are the primary defense
    $webpage = htmlspecialchars(strip_tags($webpage));
    $username = htmlspecialchars(strip_tags($username));
    // IP address can be further validated if needed
    
    // The `timestamp` is set by the database automatically (DEFAULT CURRENT_TIMESTAMP)
    $stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("sss", $webpage, $username, $ip_address);
        
        // Execute the statement and handle potential errors
        if (!$stmt->execute()) {
            // In a production environment, you would log this to a file
            error_log("Failed to execute activity log statement: " . $stmt->error);
        }
        
        $stmt->close();
    } else {
        // Handle error in preparing the statement
        error_log("Failed to prepare activity log statement: " . $conn->error);
    }
}

/*
 * =================================================================
 *                        HOW TO USE THIS LOGGER
 * =================================================================
 *
 * 1. Include this file at the top of any PHP script where you want to log activity:
 *    require_once '../includes/logger.php';
 *
 * 2. Make sure you have an active database connection ($conn).
 *
 * 3. Start the session to get the username.
 *
 * 4. Call the log_activity() function with the required parameters.
 *
 * -----------------------------------------------------------------
 *                          EXAMPLE:
 * -----------------------------------------------------------------
 *
 * <?php
 * if (session_status() === PHP_SESSION_NONE) {
 *     session_start();
 * }
 *
 * // Include database connection and logger
 * require_once '../config/db_connect.php';
 * require_once '../includes/logger.php';
 *
 * // Determine the current user and environment details
 * $currentUser = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
 * $currentPage = $_SERVER['REQUEST_URI'];
 * $clientIp    = $_SERVER['REMOTE_ADDR'];
 *
 * // Log the activity
 * log_activity($conn, $currentPage, $currentUser, $clientIp);
 *
 * // ... rest of your page logic ...
 * ?>
 *
 */
?>