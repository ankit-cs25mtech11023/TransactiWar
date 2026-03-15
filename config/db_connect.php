<?php
// Database configuration matching your compose.yaml file
date_default_timezone_set('Asia/Kolkata');
$host = 'db'; // This MUST be 'db' because that is the service name in Docker
$dbname = 'transactiwar_db';
$user = 'root'; 
$pass = 'asj*8@9#3$74fhj';

// Enable mysqli error reporting for easier debugging during development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Establish the connection using the MySQLi extension
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    // Set the charset to utf8mb4 for full Unicode support (including emojis in comments)
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+05:30'");
    
} catch (mysqli_sql_exception $e) {
    // If the connection fails, stop the script and show a generic error
    // In a real production environment, you would log this error instead of showing it
    die("Database connection failed: " . $e->getMessage());
}
?>