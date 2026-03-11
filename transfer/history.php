<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK
if (!isset($_SESSION['db_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

$user_db_id = $_SESSION['db_id'];
$username   = $_SESSION['username'];

// 1. Mandatory Logging
$ip_address = $_SERVER['REMOTE_ADDR'];
$webpage    = "/transfer/history.php";
$log_stmt   = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("sss", $webpage, $username, $ip_address);
$log_stmt->execute();

// 2. Filters
$filter = $_GET['filter'] ?? 'all';   // all | sent | received
$search = trim($_GET['search'] ?? '');

// 3. Build WHERE clause
$conditions = ["(t.sender_id = ? OR t.receiver_id = ?)"];
$params     = [$user_db_id, $user_db_id];
$types      = "ii";

if ($filter === 'sent') {
    $conditions = ["t.sender_id = ?"];
    $params     = [$user_db_id];
    $types      = "i";
} elseif ($filter === 'received') {
    $conditions = ["t.receiver_id = ?"];
    $params     = [$user_db_id];
    $types      = "i";
}

if ($search !== '') {
    $like         = "%" . $search . "%";
    $conditions[] = "(s.username LIKE ? OR r.username LIKE ? OR t.comment LIKE ?)";
    $params[]     = $like;
    $params[]     = $like;
    $params[]     = $like;
    $types       .= "sss";
}

$whereSQL = "WHERE " . implode(" AND ", $conditions);

// 4. Fetch Transaction Ledger with JOINs
$query = "
    SELECT t.amount, t.comment, t.created_at, t.sender_id, t.receiver_id,
           s.username AS sender_name,   s.user_id AS sender_public_id,
           r.username AS receiver_name, r.user_id AS receiver_public_id
    FROM transactions t
    JOIN users s ON t.sender_id   = s.id
    JOIN users r ON t.receiver_id = r.id
    $whereSQL
    ORDER BY t.created_at DESC
";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result();

// 5. Summary stats
$stats_stmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN sender_id   = ? THEN amount ELSE 0 END) AS total_sent,
        SUM(CASE WHEN receiver_id = ? THEN amount ELSE 0 END) AS total_received
    FROM transactions
    WHERE sender_id = ? OR receiver_id = ?
");
$stats_stmt->bind_param("iiii", $user_db_id, $user_db_id, $user_db_id, $user_db_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

function pageUrl(string $filter, string $search): string {
    return "history.php?" . http_build_query(['filter' => $filter, 'search' => $search]);
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
        padding-top: 56px; /* Push content down to avoid navbar overlap */
    }
    .navbar {
        position: relative;
        z-index: 1030; /* Ensure navbar is on top */
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
    }
    .btn-dark {
        background: #1c1c1c;
        color: #f0e8d8;
        font-family: 'Cinzel', serif;
    }
    .btn-dark:hover {
        background: #8b2500;
    }
</style>
<div class="battle-page">
    <?php include '../includes/bg_scene.php'; ?>
    <div class="container content-wrapper">
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
                    <h2 class="fw-bold mb-0">Transaction Ledger</h2>
                    <a href="index.php" class="btn btn-sm btn-light">+ New Transfer</a>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="text-muted small fw-bold text-uppercase">Total Transactions</div>
                    <div class="fs-4 fw-bold text-primary"><?php echo number_format($transactions->num_rows); ?></div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="text-muted small fw-bold text-uppercase">Total Sent</div>
                    <div class="fs-4 fw-bold text-danger">Rs. <?php echo number_format($stats['total_sent'] ?? 0, 2); ?></div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm text-center py-3">
                    <div class="text-muted small fw-bold text-uppercase">Total Received</div>
                    <div class="fs-4 fw-bold text-success">Rs. <?php echo number_format($stats['total_received'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <div class="btn-group" role="group">
                    <a href="<?php echo pageUrl('all', $search); ?>"
                       class="btn btn-sm <?php echo $filter === 'all'      ? 'btn-dark'    : 'btn-outline-dark'; ?>">All</a>
                    <a href="<?php echo pageUrl('sent', $search); ?>"
                       class="btn btn-sm <?php echo $filter === 'sent'     ? 'btn-dark'  : 'btn-outline-dark'; ?>">Sent</a>
                    <a href="<?php echo pageUrl('received', $search); ?>"
                       class="btn btn-sm <?php echo $filter === 'received' ? 'btn-dark' : 'btn-outline-dark'; ?>">Received</a>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <form method="GET" action="history.php">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search"
                               placeholder="Search by user or note…"
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-dark" type="submit">Search</button>
                        <?php if ($search): ?>
                            <a href="<?php echo pageUrl($filter, ''); ?>" class="btn btn-outline-danger">✕</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-dark sticky-top">
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
                                        <?php while ($tx = $transactions->fetch_assoc()):
                                            $is_sender        = ($tx['sender_id'] == $user_db_id);
                                            $tx_type          = $is_sender ? 'Sent' : 'Received';
                                            $badge_class      = $is_sender ? 'bg-danger' : 'bg-success';
                                            $amount_class     = $is_sender ? 'text-danger' : 'text-success';
                                            $sign             = $is_sender ? '−' : '+';
                                            $counterparty_name = $is_sender ? $tx['receiver_name'] : $tx['sender_name'];
                                            $counterparty_id  = $is_sender ? $tx['receiver_public_id'] : $tx['sender_public_id'];
                                            $date             = date('M j, Y H:i', strtotime($tx['created_at']));
                                            $comment          = !empty($tx['comment'])
                                                ? '"' . htmlspecialchars($tx['comment']) . '"'
                                                : '<span class="text-muted small">No comment</span>';
                                        ?>
                                        <tr>
                                            <td class="align-middle"><?php echo $date; ?></td>
                                            <td class="align-middle">
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo $tx_type; ?></span>
                                            </td>
                                            <td class="align-middle fw-bold">
                                                <?php echo htmlspecialchars($counterparty_name); ?>
                                                <small class="text-muted d-block">(<?php echo htmlspecialchars($counterparty_id); ?>)</small>
                                            </td>
                                            <td class="align-middle fw-bold <?php echo $amount_class; ?>">
                                                <?php echo $sign; ?> Rs. <?php echo number_format($tx['amount'], 2); ?>
                                            </td>
                                            <td class="align-middle fst-italic text-muted"><?php echo $comment; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <p class="mb-0">No transactions found<?php echo $search ? " for \"" . htmlspecialchars($search) . "\"" : ''; ?>.</p>
                                                <?php if (!$search): ?><small>Your ledger is completely empty.</small><?php endif; ?>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>