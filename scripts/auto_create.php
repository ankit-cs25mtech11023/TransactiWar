<?php
require_once '/var/www/html/config/db_connect.php';

$users_to_create = 100;
$users_created = 0;

while ($users_created < $users_to_create) {
    $username = 'testuser_' . bin2hex(random_bytes(4));
    $email = $username . '@iith.ac.in';
    $password = bin2hex(random_bytes(8)); // Creates a 16-character random password

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $user_id = 'WAR-' . strtoupper(substr(uniqid(), -8));

    $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, password_hash) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user_id, $username, $email, $hashed_password);

    try {
        if ($stmt->execute()) {
            $new_db_id = $conn->insert_id;

            $stmt_profile = $conn->prepare("INSERT INTO profiles (user_id) VALUES (?)");
            $stmt_profile->bind_param("i", $new_db_id);
            $stmt_profile->execute();

            echo "Successfully created user: " . $username . " with password: " . $password . "
";
            $users_created++;
        }
    } catch (mysqli_sql_exception $e) {
        // Error 1062: Duplicate entry for a unique key
        if ($e->getCode() == 1062) {
            echo "Username '" . $username . "' already exists. Generating a new one.
";
        } else {
            // For other errors, you might want to log them and stop the script
            echo "Failed to create user. Error: " . $e->getMessage() . "
";
            // Optionally break the loop for other critical errors
            // break;
        }
    }
}

echo "Bulk user creation finished. " . $users_created . " users created.
";
?>