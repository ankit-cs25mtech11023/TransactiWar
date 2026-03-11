<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// The Traffic Cop Logic: If logged in, go straight to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard/index.php");
    exit();
}

include 'includes/header.php'; 
?>

<div class="row justify-content-center mt-5 text-center">
    <div class="col-md-8">
        <h1 class="display-4 fw-bold">TransactiWar</h1>
        [cite_start]<p class="lead text-muted">Battle for Security, Compete for Supremacy. [cite: 1, 2]</p>
        <hr class="my-4">
        <p>Enlist now to receive your starting balance of Rs. [cite_start]100 [cite: 23] and prepare your defenses.</p>
        <div class="mt-4">
            <a href="/auth/register.php" class="btn btn-primary btn-lg me-2">Register Now</a>
            <a href="/auth/login.php" class="btn btn-outline-secondary btn-lg">Login</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>