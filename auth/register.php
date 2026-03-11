<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Include the database connection
require_once '../config/db_connect.php';

$error = '';
$success = '';

// 2. Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize basic inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // 3. Hash the password securely using PHP's built-in bcrypt
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 4. Generate a unique User ID (e.g., WAR-5F4A9B2C)
        $user_id = 'WAR-' . strtoupper(substr(uniqid(), -8));

        // 5. Use Prepared Statements to prevent SQL Injection
        $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user_id, $username, $email, $hashed_password);

        try {
            if ($stmt->execute()) {
                // Get the database row ID of the newly created user
                $new_db_id = $conn->insert_id;

                // 6. Create an empty profile row linked to this new user
                $stmt_profile = $conn->prepare("INSERT INTO profiles (user_id) VALUES (?)");
                $stmt_profile->bind_param("i", $new_db_id);
                $stmt_profile->execute();

                // 7. Log the registration activity
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $webpage = "/auth/register.php";
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
                $log_stmt->bind_param("sss", $webpage, $username, $ip_address);
                $log_stmt->execute();

                $success = "Registration successful! You can now login.";
            }
        } catch (mysqli_sql_exception $e) {
            // MySQL error 1062 means "Duplicate entry" (Username or Email already exists)
            if ($e->getCode() == 1062) {
                $error = "That username or email is already taken. Please choose another.";
            } else {
                $error = "A system error occurred. Please try again later.";
            }
        }
    }
}

include '../includes/header.php'; 
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        
        <?php if ($error): ?>
            <div class="alert alert-danger fw-bold shadow-sm"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success fw-bold shadow-sm"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white text-center">
                <h4 class="mb-0">Register for TransactiWar</h4>
            </div>
            <div class="card-body p-4">
                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label text-muted fw-bold">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="form-text">Your username must be unique.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label text-muted fw-bold">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label text-muted fw-bold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Create Account</button>
                </form>
            </div>
            <div class="card-footer text-center bg-light">
                <small class="text-muted">Already enlisted? <a href="login.php" class="text-decoration-none">Login here</a></small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>