<?php

require_once __DIR__ . '/../config/db_connect.php';

echo "Starting user population script...
";

$csvFile = __DIR__ . '/../Phase2.csv';


if (!file_exists($csvFile)) {
    die("Error: CSV file not found at " . $csvFile . "
");
}

// 4. Open and read the CSV file
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    // Skip the header row
    fgetcsv($handle);

    $rowCount = 1;
    // 5. Loop through each row of the CSV
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowCount++;
        // CSV columns: username, email, Name, password
        if (count($data) < 4) {
            echo "Warning: Skipping malformed row #{$rowCount}. Expected 4 columns, got " . count($data) . "
";
            continue;
        }
        
        $username = trim($data[0]);
        $email    = trim($data[1]);
        // Ignore Name column ($data[2])
        $password = trim($data[3]);

        // Basic validation in case of empty values from CSV
        if (empty($username) || empty($email) || empty($password)) {
            echo "Warning: Skipping row #{$rowCount} due to empty fields.
";
            continue;
        }

        // 6. Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 7. Generate a unique User ID
        $user_id = 'WAR-' . strtoupper(substr(uniqid(), -8));

        // 8. Prepare the INSERT statement for the 'users' table
        $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, password_hash) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            echo "Error preparing statement for users table: " . $conn->error . "
";
            continue; 
        }
        $stmt->bind_param("ssss", $user_id, $username, $email, $hashed_password);

        try {
            // 9. Execute the statement
            if ($stmt->execute()) {
                $new_db_id = $conn->insert_id; // Get the auto-incremented ID of the new user

                // 10. Create an empty profile row linked to this new user
                $stmt_profile = $conn->prepare("INSERT INTO profiles (user_id) VALUES (?)");
                if (!$stmt_profile) {
                    echo "Error preparing statement for profiles table: " . $conn->error . "
";
                    // Consider what to do here. Maybe delete the user you just created? For now, just log.
                    continue;
                }
                $stmt_profile->bind_param("i", $new_db_id);
                $stmt_profile->execute();
                $stmt_profile->close();

                echo "Successfully registered: {$username} ({$email})
";

            }
        } catch (mysqli_sql_exception $e) {
            // 11. Handle potential errors, like duplicate entries
            if ($e->getCode() == 1062) { // 1062 is the MySQL error code for duplicate entry
                $errMsg = $e->getMessage();
                if (stripos($errMsg, 'username') !== false) {
                    echo "Warning: Skipping duplicate username -> {$username}
";
                } elseif (stripos($errMsg, 'email') !== false) {
                    echo "Warning: Skipping duplicate email -> {$email}
";
                } else {
                    echo "Warning: Skipping duplicate entry for row #{$rowCount} -> {$username}
";
                }
            } else {
                echo "Error on row #{$rowCount} for user {$username}: " . $e->getMessage() . "
";
            }
        }
        $stmt->close();
    }
    fclose($handle);
    echo "
User population script finished.
";
} else {
    echo "Error: Could not open the CSV file.
";
}

// 12. Close the database connection
$conn->close();
