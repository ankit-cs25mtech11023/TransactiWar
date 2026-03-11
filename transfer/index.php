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
$user_public_id = $_SESSION['user_id'];

// 1. Mandatory Logging
$ip_address = $_SERVER['REMOTE_ADDR'];
$webpage = "/transfer/index.php";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("sss", $webpage, $username, $ip_address);
$log_stmt->execute();

$error = '';
$success = '';
$search_results = [];

// 2. Fetch User's Current Balance for the UI
$bal_stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$bal_stmt->bind_param("i", $user_db_id);
$bal_stmt->execute();
$bal_result = $bal_stmt->get_result();
$current_balance = $bal_result->fetch_assoc()['balance'];

// 3. Handle User Search (GET Request)
if (isset($_GET['search_query']) && !empty(trim($_GET['search_query']))) {
    $search_term = trim($_GET['search_query']);
    $search_like = "%" . $search_term . "%";
    
    // Search by username or user ID, but exclude the logged-in user from results
    $search_stmt = $conn->prepare("SELECT user_id, username FROM users WHERE (username LIKE ? OR user_id = ?) AND id != ? LIMIT 10");
    $search_stmt->bind_param("ssi", $search_like, $search_term, $user_db_id);
    $search_stmt->execute();
    $res = $search_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $search_results[] = $row;
    }
}

// 4. Handle Money Transfer (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['amount'])) {
    $receiver_public_id = trim($_POST['receiver_id']);
    $amount = floatval($_POST['amount']);
    $comment = trim($_POST['comment'] ?? ''); // Optional comment

    // Basic Validation
    if ($amount <= 0) {
        $error = "Transfer amount must be greater than zero.";
    } elseif ($receiver_public_id === $user_public_id) {
        $error = "You cannot send money to yourself.";
    } elseif ($amount > $current_balance) {
        // Prevent negative balance transactions
        $error = "Insufficient funds. You cannot transfer more than your current balance.";
    } else {
        // Check if receiver exists and get their internal DB ID
        $rec_stmt = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
        $rec_stmt->bind_param("s", $receiver_public_id);
        $rec_stmt->execute();
        $rec_result = $rec_stmt->get_result();

        if ($rec_result->num_rows === 0) {
            $error = "Receiver User ID not found.";
        } else {
            $receiver_db_id = $rec_result->fetch_assoc()['id'];

            // ==========================================
            // SECURE TRANSACTION BLOCK START
            // ==========================================
            $conn->begin_transaction();

            try {
                // Deduct from Sender
                $deduct_stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $deduct_stmt->bind_param("di", $amount, $user_db_id);
                $deduct_stmt->execute();

                // Add to Receiver
                $add_stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $add_stmt->bind_param("di", $amount, $receiver_db_id);
                $add_stmt->execute();

                // Record the Transaction Ledger
                $tx_stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, comment) VALUES (?, ?, ?, ?)");
                $tx_stmt->bind_param("iids", $user_db_id, $receiver_db_id, $amount, $comment);
                $tx_stmt->execute();

                // Commit the transaction (Make it permanent)
                $conn->commit();

                $success = "Successfully transferred Rs. " . number_format($amount, 2) . " to User ID: $receiver_public_id.";
                // Update local balance variable so the UI reflects the new amount immediately
                $current_balance -= $amount; 
                
            } catch (mysqli_sql_exception $exception) {
                // If anything goes wrong, rollback to prevent lost money
                $conn->rollback();
                $error = "Transaction failed due to a system error. No money was moved.";
            }
            // ==========================================
            // SECURE TRANSACTION BLOCK END
            // ==========================================
        }
    }
}

include '../includes/header.php'; 
?>

<div class="row mt-4">
    <div class="col-md-12 mb-3">
        <h2 class="fw-bold border-bottom pb-2">Money Transfer Operations</h2>
        <p class="text-muted">Current Available Balance: <strong class="text-success fs-5">Rs. <?php echo number_format($current_balance, 2); ?></strong></p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger fw-bold shadow-sm"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success fw-bold shadow-sm"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Search Users</h5>
            </div>
            <div class="card-body bg-light">
                <form action="index.php" method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search_query" placeholder="Search by Username..." value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>" required>
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </form>
                
                <div class="list-group">
                    <?php if (isset($_GET['search_query'])): ?>
                        <?php if (count($search_results) > 0): ?>
                            <?php foreach ($search_results as $res): ?>
                                <div class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($res['username']); ?></h6>
                                            <small class="text-muted">ID: <?php echo htmlspecialchars($res['user_id']); ?></small>
                                        </div>
                                        <button class="btn btn-sm btn-primary" onclick="document.getElementById('receiver_id').value = '<?php echo htmlspecialchars($res['user_id']); ?>';">Select</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted mt-3">No users found.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Initiate Transfer</h5>
            </div>
            <div class="card-body p-4">
                <form action="index.php" method="POST">
                    <div class="mb-3">
                        <label for="receiver_id" class="form-label fw-bold text-muted">Receiver User ID</label>
                        <input type="text" class="form-control" id="receiver_id" name="receiver_id" placeholder="e.g., WAR-1234ABCD" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label fw-bold text-muted">Amount (Rs.)</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="mb-4">
                        <label for="comment" class="form-label fw-bold text-muted">Comment (Optional)</label>
                        <textarea class="form-control" id="comment" name="comment" rows="2" placeholder="Add a message for the receiver..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold">Confirm Transfer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>