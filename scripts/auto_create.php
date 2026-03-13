<?php
require_once '/var/www/html/config/db_connect.php';

for ($i = 1; $i <= 100; $i++) {
    $username = 'testuser' . $i;
    $email = 'testuser' . $i . '@iith.ac.in';
    $password = 'Password@' . $i;

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

            echo "Successfully created user: " . $username . "
";
        }
    } catch (mysqli_sql_exception $e) {
        echo "Failed to create user: " . $username . ". Error: " . $e->getMessage() . "
";
    }
}

echo "Bulk user creation finished.
";
?>