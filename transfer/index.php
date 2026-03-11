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

// Check for messages from redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $success = "Transaction was successful!";
    } elseif ($_GET['status'] === 'insufficient_funds') {
        $error = "Insufficient funds. You cannot transfer more than your current balance.";
    } elseif ($_GET['status'] === 'self_transfer') {
        $error = "You cannot send money to yourself.";
    } elseif ($_GET['status'] === 'invalid_receiver') {
        $error = "Receiver User ID not found.";
    } elseif ($_GET['status'] === 'invalid_amount') {
        $error = "Transfer amount must be greater than zero.";
    } else {
        $error = "An unknown error occurred during the transaction.";
    }
}

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
    $comment = trim($_POST['comment'] ?? '');

    if ($amount <= 0) {
        header("Location: index.php?status=invalid_amount");
        exit();
    } elseif ($receiver_public_id === $user_public_id) {
        header("Location: index.php?status=self_transfer");
        exit();
    } elseif ($amount > $current_balance) {
        header("Location: index.php?status=insufficient_funds");
        exit();
    } else {
        $rec_stmt = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
        $rec_stmt->bind_param("s", $receiver_public_id);
        $rec_stmt->execute();
        $rec_result = $rec_stmt->get_result();

        if ($rec_result->num_rows === 0) {
            header("Location: index.php?status=invalid_receiver");
            exit();
        } else {
            $receiver_db_id = $rec_result->fetch_assoc()['id'];

            $conn->begin_transaction();

            try {
                $deduct_stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $deduct_stmt->bind_param("di", $amount, $user_db_id);
                $deduct_stmt->execute();

                $add_stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $add_stmt->bind_param("di", $amount, $receiver_db_id);
                $add_stmt->execute();

                $tx_stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, comment) VALUES (?, ?, ?, ?)");
                $tx_stmt->bind_param("iids", $user_db_id, $receiver_db_id, $amount, $comment);
                $tx_stmt->execute();

                $conn->commit();

                header("Location: index.php?status=success");
                exit();

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                header("Location: index.php?status=error");
                exit();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-md-12 mb-3">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
            <div>
                <h2 class="fw-bold mb-0">Money Transfer Operations</h2>
                <p class="text-muted mt-1 mb-0">
                    Current Available Balance:
                    <strong class="text-success fs-5">Rs. <?php echo number_format($current_balance, 2); ?></strong>
                </p>
            </div>
            <a href="history.php" class="btn btn-dark">
                View Transaction History
            </a>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger fw-bold shadow-sm mt-3"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success fw-bold shadow-sm mt-3"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Search Users</h5>
            </div>
            <div class="card-body bg-light">
                <form action="index.php" method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search_query"
                               placeholder="Search by Username..."
                               value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>"
                               required>
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
                                        <button class="btn btn-sm btn-primary"
                                                onclick="document.getElementById('receiver_id').value = '<?php echo htmlspecialchars($res['user_id']); ?>';">
                                            Select
                                        </button>
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
                        <input type="text" class="form-control" id="receiver_id" name="receiver_id"
                               placeholder="e.g., WAR-1234ABCD" required>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label fw-bold text-muted">Amount (Rs.)</label>
                        <input type="number" class="form-control" id="amount" name="amount"
                               min="1" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="mb-4">
                        <label for="comment" class="form-label fw-bold text-muted">Comment (Optional)</label>
                        <textarea class="form-control" id="comment" name="comment" rows="2"
                                  placeholder="Add a message for the receiver..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold">Confirm Transfer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>