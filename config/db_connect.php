<?php
// Database configuration matching your compose.yaml file
date_default_timezone_set('Asia/Kolkata');

// SECURE SECRET RETRIEVAL
$host = getenv('DB_HOST'); 
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER'); 
$pass = getenv('DB_PASS');

// Enable mysqli error reporting for easier debugging during development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Establish the connection using the MySQLi extension
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    // Set the charset to utf8mb4 for full Unicode support (including emojis in comments)
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+05:30'");
    
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>