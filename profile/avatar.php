<?php
// profile/avatar.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Block unauthenticated access
if (!isset($_SESSION['db_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

require_once '../config/db_connect.php';

// Get the user_id we want to view (defaults to the logged-in user)
$target_user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

// 1. Ask the database what this user's filename is
$stmt = $conn->prepare("SELECT p.profile_image_path FROM profiles p JOIN users u ON p.user_id = u.id WHERE u.user_id = ?");
$stmt->bind_param("s", $target_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $filename = $row['profile_image_path'];
    
    // 2. Build the secure path
    $secure_path = '/var/www/uploads/' . basename($filename);

    // 3. If the file exists, stream it to the browser
    if (!empty($filename) && file_exists($secure_path)) {
        $mime_type = mime_content_type($secure_path);
        header("Content-Type: $mime_type");
        header("Content-Length: " . filesize($secure_path));
        readfile($secure_path);
        exit();
    }
}

// 4. Fallback: If no image exists, stream a default SVG or PNG
header("Content-Type: image/svg+xml");
echo '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 24 24" fill="#ccc"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>';
exit();
?>