<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ── DUMMY DATA (no DB) ────────────────────────────────────────────────────────
$userId = 1;

$stats = [
    'total'          => 8,
    'total_sent'     => 1350.00,
    'total_received' => 5600.00,
];

$all_transfers = [
    [
        'sender_id'     => 1,
        'receiver_id'   => 2,
        'sender_name'   => 'johndoe',
        'receiver_name' => 'ambarishsarkar',
        'amount'        => 500.00,
        'note'          => 'Thanks for the help with the LLVM pass!',
        'created_at'    => '2026-03-11 14:30:00',
    ],
    [
        'sender_id'     => 3,
        'receiver_id'   => 1,
        'sender_name'   => 'priyankajoshi',
        'receiver_name' => 'johndoe',
        'amount'        => 1200.00,
        'note'          => 'Reimbursement for project materials',
        'created_at'    => '2026-03-10 09:15:00',
    ],
    [
        'sender_id'     => 1,
        'receiver_id'   => 4,
        'sender_name'   => 'johndoe',
        'receiver_name' => 'rahulverma99',
        'amount'        => 250.00,
        'note'          => 'Pizza night 🍕',
        'created_at'    => '2026-03-09 20:45:00',
    ],
    [
        'sender_id'     => 4,
        'receiver_id'   => 1,
        'sender_name'   => 'rahulverma99',
        'receiver_name' => 'johndoe',
        'amount'        => 800.00,
        'note'          => 'Shared Uber fare',
        'created_at'    => '2026-03-08 18:00:00',
    ],
    [
        'sender_id'     => 1,
        'receiver_id'   => 3,
        'sender_name'   => 'johndoe',
        'receiver_name' => 'priyankajoshi',
        'amount'        => 600.00,
        'note'          => 'Concert tickets',
        'created_at'    => '2026-03-07 12:10:00',
    ],
    [
        'sender_id'     => 5,
        'receiver_id'   => 1,
        'sender_name'   => 'nehasingh',
        'receiver_name' => 'johndoe',
        'amount'        => 2500.00,
        'note'          => 'Freelance payment',
        'created_at'    => '2026-03-05 10:00:00',
    ],
    [
        'sender_id'     => 1,
        'receiver_id'   => 5,
        'sender_name'   => 'johndoe',
        'receiver_name' => 'nehasingh',
        'amount'        => 100.00,
        'note'          => '',
        'created_at'    => '2026-03-03 16:30:00',
    ],
    [
        'sender_id'     => 6,
        'receiver_id'   => 1,
        'sender_name'   => 'vikrammalhotra',
        'receiver_name' => 'johndoe',
        'amount'        => 1100.00,
        'note'          => 'Study group lunch split',
        'created_at'    => '2026-03-01 13:20:00',
    ],
];

// ── Filter ────────────────────────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$filtered = array_filter($all_transfers, function($tx) use ($userId, $filter, $search) {
    if ($filter === 'sent'     && $tx['sender_id']   !== $userId) return false;
    if ($filter === 'received' && $tx['receiver_id'] !== $userId) return false;
    if ($search) {
        $hay = strtolower($tx['sender_name'] . $tx['receiver_name'] . $tx['note']);
        if (strpos($hay, strtolower($search)) === false) return false;
    }
    return true;
});

// ── Pagination ────────────────────────────────────────────────────────────────
$perPage    = 10;
$page       = max(1, (int)($_GET['page'] ?? 1));
$totalRows  = count($filtered);
$totalPages = max(1, ceil($totalRows / $perPage));
$transfers  = array_slice(array_values($filtered), ($page - 1) * $perPage, $perPage);

function pageUrl(int $p, string $filter, string $search): string {
    return "transfer.php?" . http_build_query(['filter' => $filter, 'search' => $search, 'page' => $p]);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <h2 class="fw-bold mb-0">Transaction Ledger</h2>
            <a href="index.php" class="btn btn-sm btn-primary">+ New Transfer</a>
        </div>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small fw-bold text-uppercase">Total Transactions</div>
            <div class="fs-4 fw-bold text-primary"><?php echo $stats['total']; ?></div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small fw-bold text-uppercase">Total Sent</div>
            <div class="fs-4 fw-bold text-danger">Rs. <?php echo number_format($stats['total_sent'], 2); ?></div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="text-muted small fw-bold text-uppercase">Total Received</div>
            <div class="fs-4 fw-bold text-success">Rs. <?php echo number_format($stats['total_received'], 2); ?></div>
        </div>
    </div>
</div>

<!-- Filters & Search -->
<div class="row mb-3">
    <div class="col-md-6 mb-2">
        <div class="btn-group" role="group">
            <a href="<?php echo pageUrl($page, 'all', $search); ?>"
               class="btn btn-sm <?php echo $filter === 'all'      ? 'btn-dark'    : 'btn-outline-secondary'; ?>">All</a>
            <a href="<?php echo pageUrl($page, 'sent', $search); ?>"
               class="btn btn-sm <?php echo $filter === 'sent'     ? 'btn-danger'  : 'btn-outline-secondary'; ?>">Sent</a>
            <a href="<?php echo pageUrl($page, 'received', $search); ?>"
               class="btn btn-sm <?php echo $filter === 'received' ? 'btn-success' : 'btn-outline-secondary'; ?>">Received</a>
        </div>
    </div>
    <div class="col-md-6 mb-2">
        <form method="GET" action="transfer.php">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="hidden" name="page"   value="1">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" name="search"
                       placeholder="Search by user or note…"
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
                <?php if ($search): ?>
                    <a href="<?php echo pageUrl(1, $filter, ''); ?>" class="btn btn-outline-danger">✕</a>
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
                        <?php if (empty($transfers)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No transactions found<?php echo $search ? " for \"" . htmlspecialchars($search) . "\"" : ''; ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transfers as $tx):
                                $isSent      = $tx['sender_id'] == $userId;
                                $counterpart = $isSent ? $tx['receiver_name'] : $tx['sender_name'];
                                $date        = date('M d, Y H:i', strtotime($tx['created_at']));
                            ?>
                            <tr>
                                <td><?php echo $date; ?></td>
                                <td>
                                    <?php if ($isSent): ?>
                                        <span class="badge bg-danger">Sent</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Received</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($counterpart); ?></td>
                                <td class="fw-bold <?php echo $isSent ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $isSent ? '− ' : '+ '; ?>Rs. <?php echo number_format($tx['amount'], 2); ?>
                                </td>
                                <td class="text-muted fst-italic">
                                    <?php echo $tx['note'] ? '"' . htmlspecialchars($tx['note']) . '"' : '—'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    &nbsp;(<?php echo $totalRows; ?> records)
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo pageUrl($page - 1, $filter, $search); ?>">‹</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo pageUrl($p, $filter, $search); ?>"><?php echo $p; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo pageUrl($page + 1, $filter, $search); ?>">›</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>