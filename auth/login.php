<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../includes/header.php'; 
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
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