<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once '../config/db_connect.php';

// If the user is already logged in, redirect them immediately to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // 1. Fetch the user's data using a Prepared Statement to prevent SQL Injection
        $stmt = $conn->prepare("SELECT id, user_id, username, password_hash FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // 2. Check if a user with that username actually exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // 3. Verify the password against the stored hash
            if (password_verify($password, $user['password_hash'])) {
                
                // 4. Password is correct! Set up the session variables
                // We store 'db_id' (the numeric primary key) for database relationships
                // We store 'user_id' (the WAR-XXXX string) for the frontend display
                $_SESSION['db_id'] = $user['id']; 
                $_SESSION['user_id'] = $user['user_id']; 
                $_SESSION['username'] = $user['username'];

                // 5. Mandatory Requirement: Log the successful login 
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $webpage = "/auth/login.php";
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
                $log_stmt->bind_param("sss", $webpage, $user['username'], $ip_address);
                $log_stmt->execute();

                // 6. Send them to the Command Center
                header("Location: ../dashboard/index.php");
                exit();
            } else {
                // To prevent user enumeration attacks, keep error messages generic
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}

include '../includes/header.php'; 
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        
        <?php if ($error): ?>
            <div class="alert alert-danger fw-bold shadow-sm text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white text-center">
                <h4 class="mb-0">Login to Battle</h4>
            </div>
            <div class="card-body p-4">
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label text-muted fw-bold">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label text-muted fw-bold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">Login</button>
                </form>
            </div>
            <div class="card-footer text-center bg-light">
                <small class="text-muted">Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>