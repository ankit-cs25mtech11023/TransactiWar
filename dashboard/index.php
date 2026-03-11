<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK: If the user is NOT logged in, kick them out instantly
if (!isset($_SESSION['db_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

$user_db_id = $_SESSION['db_id'];
$username = $_SESSION['username'];

// 1. Mandatory Logging: Record the visit to the dashboard 
$ip_address = $_SERVER['REMOTE_ADDR'];
$webpage = "/dashboard/index.php";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("sss", $webpage, $username, $ip_address);
$log_stmt->execute();

// 2. Fetch the user's real, current balance from the database
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_db_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
// Format the balance to always show 2 decimal places
$current_balance = number_format($user_data['balance'], 2); 

// 3. Fetch the 5 most recent transactions for this user
$tx_query = "
    SELECT t.amount, t.created_at, t.sender_id, t.receiver_id,
           s.username AS sender_name, 
           r.username AS receiver_name
    FROM transactions t
    LEFT JOIN users s ON t.sender_id = s.id
    LEFT JOIN users r ON t.receiver_id = r.id
    WHERE t.sender_id = ? OR t.receiver_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
";
$tx_stmt = $conn->prepare($tx_query);
$tx_stmt->bind_param("ii", $user_db_id, $user_db_id);
$tx_stmt->execute();
$transactions = $tx_stmt->get_result();

include '../includes/header.php'; 
?>

<div class="row mt-4">
    <div class="col-md-12">
        <h2 class="fw-bold border-bottom pb-2">Command Center</h2>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white shadow-sm border-0 h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <h5 class="card-title text-uppercase fw-bold opacity-75">Current Balance</h5>
                <h1 class="display-3 fw-bold">Rs. <?php echo htmlspecialchars($current_balance); ?></h1>
            </div>
            <div class="card-footer bg-transparent border-0 text-center pb-3">
                <a href="../transfer/index.php" class="btn btn-light fw-bold w-75">Send Money</a>
            </div>
        </div>
    </div>

    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>User</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions->num_rows > 0): ?>
                                <?php while($tx = $transactions->fetch_assoc()): ?>
                                    <?php 
                                        // Determine if money was sent or received to style it properly
                                        $is_sender = ($tx['sender_id'] == $user_db_id);
                                        $tx_type = $is_sender ? 'Sent' : 'Received';
                                        $badge_class = $is_sender ? 'bg-danger' : 'bg-success';
                                        $amount_class = $is_sender ? 'text-danger' : 'text-success';
                                        $sign = $is_sender ? '-' : '+';
                                        $counterparty = $is_sender ? $tx['receiver_name'] : $tx['sender_name'];
                                        $date = date('M j, Y g:i A', strtotime($tx['created_at']));
                                    ?>
                                    <tr>
                                        <td class="align-middle text-muted small"><?php echo $date; ?></td>
                                        <td class="align-middle"><span class="badge <?php echo $badge_class; ?>"><?php echo $tx_type; ?></span></td>
                                        <td class="align-middle fw-bold"><?php echo htmlspecialchars($counterparty); ?></td>
                                        <td class="align-middle fw-bold <?php echo $amount_class; ?>"><?php echo $sign; ?> Rs. <?php echo number_format($tx['amount'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No recent transactions. The battlefield is quiet.</td>
                                </tr>
                            <?php endif; ?>
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