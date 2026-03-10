<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Notice the ../ to step out of the auth/ folder and into includes/
include '../includes/header.php'; 
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
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