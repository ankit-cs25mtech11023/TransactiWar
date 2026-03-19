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


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. Mandatory Logging
require_once '../includes/logger.php';
log_activity($conn, $_SERVER['REQUEST_URI'], $username, $_SERVER['REMOTE_ADDR']);

$error = '';
$success = '';

// ==========================================
// SECURITY PATCH: Server-Side Flash Messages
// ==========================================
// We completely ignore the URL. We only read from the server's secure session memory.
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']); // Destroy immediately after reading
}

if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']); // Destroy immediately after reading
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
    
    // SECURITY PATCH applied earlier: Escape wildcards just in case
    $escaped_term = addcslashes($search_term, '%_\\');
    $search_like = "%" . $escaped_term . "%";

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
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("CSRF Attack Blocked for user ID: " . $_SESSION['db_id']);
        die("Security Violation: Invalid CSRF Token. Transfer aborted.");
    }

    unset($_SESSION['csrf_token']);

    $receiver_public_id = trim($_POST['receiver_id']);
    $amount = floatval($_POST['amount']);

    $amount = round($amount, 2);
    
    $comment = trim($_POST['comment'] ?? '');

    // ==========================================
    // SECURITY PATCH: Enforce Flash Variables on all Errors
    // ==========================================
    if ($amount < 0.01) {
        $_SESSION['flash_error'] = "Transfer amount must be greater than zero.";
        header("Location: index.php");
        exit();
    } elseif ($receiver_public_id === $user_public_id) {
        $_SESSION['flash_error'] = "You cannot send money to yourself.";
        header("Location: index.php");
        exit();
    } elseif ($amount > $current_balance) {
        $_SESSION['flash_error'] = "Insufficient funds. You cannot transfer more than your current balance.";
        header("Location: index.php");
        exit();
    } else {
        $rec_stmt = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
        $rec_stmt->bind_param("s", $receiver_public_id);
        $rec_stmt->execute();
        $rec_result = $rec_stmt->get_result();

        if ($rec_result->num_rows === 0) {
            $_SESSION['flash_error'] = "Receiver User ID not found.";
            header("Location: index.php");
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

                log_activity($conn, '/transfer/success', $username, $_SERVER['REMOTE_ADDR']);

                $conn->commit();

                // Success flash variable is properly set here
                $_SESSION['flash_success'] = "Transaction was successful!";
                header("Location: index.php");
                exit();

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $_SESSION['flash_error'] = "An unknown error occurred during the transaction.";
                header("Location: index.php");
                exit();
            }
        }
    }
}

include '../includes/header.php';
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap');

    body {
        background: linear-gradient(160deg, #f0ebe0 0%, #e8dfc9 50%, #ddd0b5 100%);
        font-family: 'Inter', sans-serif;
    }
    .battle-page {
        position: fixed;
        inset: 0;
        overflow-y: auto;
        padding-top: 56px; 
    }
    .navbar {
        position: relative;
        z-index: 1030; 
    }
    .bg-scene {
        position: absolute;
        inset: 0;
        width: 100%; height: 100%;
        z-index: 1;
        pointer-events: none;
        opacity: 0.1;
    }
    .battle-page::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 50% 60%, transparent 30%, rgba(80,55,25,0.22) 100%);
        z-index: 2;
        pointer-events: none;
    }
    .content-wrapper {
        position: relative;
        z-index: 10;
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .card {
        background: rgba(255,253,248,0.94);
        border: 1px solid rgba(170,145,100,0.22);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05), 0 14px 44px rgba(0,0,0,0.12);
    }
    .card-header {
        background: #1c1c1c;
        color: #f0e8d8;
        font-family: 'Cinzel', serif;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    .btn-light {
        background: #f0e8d8;
        color: #1c1c1c;
        font-family: 'Cinzel', serif;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        border: none;
        transition: background 0.2s, transform 0.1s;
    }
    .btn-light:hover {
        background: #fff;
        transform: translateY(-1px);
        font-family: 'Cinzel', serif;
    }
    .btn-dark {
        background: #1c1c1c;
        color: #f0e8d8;
    }
    .btn-dark:hover {
        background: #8b2500;
    }
</style>
<div class="battle-page">
    <?php include '../includes/bg_scene.php'; ?>
    <div class="container content-wrapper">
        <div class="row mt-4">
            <div class="col-md-12 mb-3">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                    <div>
                        <h2 class="fw-bold mb-0">Money Transfer Operations</h2>
                        <p class="text-black mt-1 mb-0">
                            Current Available Balance:
                            <strong class="fs-5" style="color: #0f8100;">Rs. <?php echo number_format($current_balance, 2); ?></strong>
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
                    <div class="card-header">
                        <h5 class="mb-0">Search Users</h5>
                    </div>
                    <div class="card-body">
                        <form action="index.php" method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search_query"
                                       placeholder="Search by Username..."
                                       value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>"
                                       required>
                                <button class="btn btn-dark" type="submit">Search</button>
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
                                                <button class="btn btn-sm btn-light"
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
                    <div class="card-header">
                        <h5 class="mb-0">Initiate Transfer</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="index.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

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

                            <button type="submit" class="btn btn-dark w-100 fw-bold">Confirm Transfer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>