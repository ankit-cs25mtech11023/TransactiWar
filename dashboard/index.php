<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK: If the user is NOT logged in, kick them out instantly
if (!isset($_SESSION['user_id'])) {
    // We will uncomment this once the database and login logic are connected
    // header("Location: /auth/login.php");
    // exit();
}

include '../includes/header.php'; 

// Temporary mock data to visualize the dashboard
$mock_balance = 100; // Starting balance [cite: 23]
$mock_username = "Ankit";
?>

<div class="row mt-4">
    <div class="col-md-12">
        <h2 class="fw-bold border-bottom pb-2">Command Center</h2>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm border-0 h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="card-title text-uppercase fw-bold opacity-75">Current Balance</h5>
                <h1 class="display-3 fw-bold">Rs. <?php echo htmlspecialchars($mock_balance); ?></h1>
            </div>
            <div class="card-footer bg-transparent border-0 text-center pb-3">
                <a href="../transfer/index.php" class="btn btn-light fw-bold w-75">Send Money</a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>User</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No recent transactions. The battlefield is quiet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end bg-light">
                <a href="../transfer/history.php" class="btn btn-sm btn-outline-secondary">View Full History</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>