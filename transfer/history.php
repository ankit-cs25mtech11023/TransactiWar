<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK
if (!isset($_SESSION['db_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

$user_db_id = $_SESSION['db_id'];
$username = $_SESSION['username'];

// 1. Mandatory Logging
$ip_address = $_SERVER['REMOTE_ADDR'];
$webpage = "/transfer/history.php";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("sss", $webpage, $username, $ip_address);
$log_stmt->execute();

// 2. Fetch the Transaction Ledger with JOINs
$query = "
    SELECT t.amount, t.comment, t.created_at, t.sender_id, t.receiver_id,
           s.username AS sender_name, s.user_id AS sender_public_id,
           r.username AS receiver_name, r.user_id AS receiver_public_id
    FROM transactions t
    JOIN users s ON t.sender_id = s.id
    JOIN users r ON t.receiver_id = r.id
    WHERE t.sender_id = ? OR t.receiver_id = ?
    ORDER BY t.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_db_id, $user_db_id);
$stmt->execute();
$transactions = $stmt->get_result();

include '../includes/header.php'; 
?>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <h2 class="fw-bold mb-0">Transaction Ledger</h2>
            <a href="index.php" class="btn btn-sm btn-primary">New Transfer</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Counterparty (ID)</th>
                                <th>Amount</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions->num_rows > 0): ?>
                                <?php while($tx = $transactions->fetch_assoc()): ?>
                                    <?php 
                                        // Logic to determine if money was sent or received
                                        $is_sender = ($tx['sender_id'] == $user_db_id);
                                        
                                        $tx_type = $is_sender ? 'Sent' : 'Received';
                                        $badge_class = $is_sender ? 'bg-danger' : 'bg-success';
                                        $amount_class = $is_sender ? 'text-danger' : 'text-success';
                                        $sign = $is_sender ? '-' : '+';
                                        
                                        $counterparty_name = $is_sender ? $tx['receiver_name'] : $tx['sender_name'];
                                        $counterparty_id = $is_sender ? $tx['receiver_public_id'] : $tx['sender_public_id'];
                                        
                                        $date = date('M j, Y H:i', strtotime($tx['created_at']));
                                        $comment = !empty($tx['comment']) ? '"' . htmlspecialchars($tx['comment']) . '"' : '<span class="text-muted small">No comment</span>';
                                    ?>
                                    <tr>
                                        <td class="align-middle"><?php echo $date; ?></td>
                                        <td class="align-middle"><span class="badge <?php echo $badge_class; ?>"><?php echo $tx_type; ?></span></td>
                                        <td class="align-middle fw-bold">
                                            <?php echo htmlspecialchars($counterparty_name); ?> 
                                            <small class="text-muted d-block">(<?php echo htmlspecialchars($counterparty_id); ?>)</small>
                                        </td>
                                        <td class="align-middle fw-bold <?php echo $amount_class; ?>"><?php echo $sign; ?> Rs. <?php echo number_format($tx['amount'], 2); ?></td>
                                        <td class="align-middle fst-italic text-muted"><?php echo $comment; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <p class="mb-0">No transactions found.</p>
                                        <small>Your ledger is completely empty.</small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>