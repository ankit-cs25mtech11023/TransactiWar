<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ── DUMMY DATA (no DB) ────────────────────────────────────────────────────────
$mock_balance        = 4250.00;
$mock_search_results = [
    ['id' => 2, 'username' => 'ambarishsarkar'],
    ['id' => 3, 'username' => 'priyankajoshi'],
    ['id' => 4, 'username' => 'rahulverma99'],
];

$success    = '';
$error      = '';
$confirming = false;
$transferData = [];

// ── Simulate form flow ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $receiverId = trim($_POST['receiver_id'] ?? '');
    $amount     = round((float)($_POST['amount'] ?? 0), 2);
    $comment    = trim($_POST['comment'] ?? '');

    if ($action === 'preview') {
        if (!$receiverId || $amount <= 0) {
            $error = 'Please enter a valid Receiver User ID and amount.';
        } elseif ($amount > $mock_balance + 0.001) {
            $error = 'Insufficient balance for this transfer.';
        } else {
            $confirming   = true;
            $transferData = [
                'receiver_db_id'    => 2,
                'receiver_username' => $receiverId,
                'amount'            => $amount,
                'comment'           => $comment,
            ];
        }
    } elseif ($action === 'confirm') {
        $amount       = round((float)$_POST['amount'], 2);
        $mock_balance = round($mock_balance - $amount, 2);
        $success      = "Rs. " . number_format($amount, 2) . " transferred to @" . htmlspecialchars($_POST['receiver_username'] ?? 'user') . " successfully!";
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-md-12 mb-3">
        <h2 class="fw-bold border-bottom pb-2">Money Transfer Operations</h2>
        <p class="text-muted">Current Available Balance: <strong class="text-success">Rs. <?php echo number_format($mock_balance, 2); ?></strong></p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>✓ Transfer Successful!</strong> <?php echo htmlspecialchars($success); ?>
    &nbsp;<a href="transfer.php" class="alert-link">View History →</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>✕ Error:</strong> <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($confirming): ?>
<!-- CONFIRMATION SCREEN -->
<div class="row">
    <div class="col-md-6 offset-md-3 mb-4">
        <div class="card shadow-sm border-warning border-2">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">⚠ Confirm Transfer</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-4">
                    <tbody>
                        <tr>
                            <td class="text-muted fw-bold">To</td>
                            <td class="fw-bold">@<?php echo htmlspecialchars($transferData['receiver_username']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Amount</td>
                            <td class="text-success fw-bold fs-5">Rs. <?php echo number_format($transferData['amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Balance After</td>
                            <td class="text-danger fw-bold">Rs. <?php echo number_format(max(0, $mock_balance - $transferData['amount']), 2); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Comment</td>
                            <td><?php echo $transferData['comment'] ? htmlspecialchars($transferData['comment']) : '<span class="text-muted">—</span>'; ?></td>
                        </tr>
                    </tbody>
                </table>
                <form action="index.php" method="POST">
                    <input type="hidden" name="action"            value="confirm">
                    <input type="hidden" name="receiver_username" value="<?php echo htmlspecialchars($transferData['receiver_username']); ?>">
                    <input type="hidden" name="amount"            value="<?php echo $transferData['amount']; ?>">
                    <input type="hidden" name="comment"           value="<?php echo htmlspecialchars($transferData['comment']); ?>">
                    <div class="d-flex gap-2">
                        <a href="index.php" class="btn btn-outline-secondary w-50">← Edit</a>
                        <button type="submit" class="btn btn-success w-50 fw-bold">✓ Confirm & Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- MAIN FORM -->
<div class="row">

    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Search Users</h5>
            </div>
            <div class="card-body bg-light">
                <form action="index.php" method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search_query"
                               placeholder="Enter Username or User ID..."
                               value="<?php echo htmlspecialchars($_GET['search_query'] ?? ''); ?>"
                               required>
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </form>
                <div class="list-group">
                    <?php foreach ($mock_search_results as $result): ?>
                    <div class="list-group-item list-group-item-action py-3">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($result['username']); ?></h6>
                                <small class="text-muted">ID: <?php echo $result['id']; ?></small>
                            </div>
                            <button class="btn btn-sm btn-primary"
                                    onclick="fillReceiver('<?php echo htmlspecialchars($result['username']); ?>')">
                                Select
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                    <input type="hidden" name="action" value="preview">
                    <div class="mb-3">
                        <label for="receiver_id" class="form-label fw-bold text-muted">Receiver User ID</label>
                        <input type="text" class="form-control" id="receiver_id" name="receiver_id"
                               placeholder="e.g., CS25MTECH12345"
                               value="<?php echo htmlspecialchars($_POST['receiver_id'] ?? ''); ?>"
                               required>
                        <div class="form-text text-danger">Transfers must be made using the exact User ID.</div>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label fw-bold text-muted">Amount (Rs.)</label>
                        <input type="number" class="form-control" id="amount" name="amount"
                               min="0.01" step="0.01" placeholder="0.00"
                               max="<?php echo $mock_balance; ?>"
                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                               required>
                        <div class="form-text text-muted">
                            Max transferable: <strong>Rs. <?php echo number_format($mock_balance, 2); ?></strong>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="comment" class="form-label fw-bold text-muted">Comment (Optional)</label>
                        <textarea class="form-control" id="comment" name="comment" rows="2"
                                  placeholder="Add a message for the receiver..."><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">Preview Transfer →</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="transfer.php" class="text-muted small">View transfer history →</a>
                </div>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
function fillReceiver(username) {
    const field = document.getElementById('receiver_id');
    if (field) {
        field.value = username;
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        field.focus();
    }
}
</script>