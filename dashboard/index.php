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
require_once '../includes/logger.php';
log_activity($conn, $_SERVER['REQUEST_URI'], $username, $_SERVER['REMOTE_ADDR']);

// 2. Fetch the user's real, current balance from the database
$stmt = $conn->prepare("SELECT balance, user_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_db_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
// Format the balance to always show 2 decimal places
$current_balance = number_format($user_data['balance'], 2);
$user_identifier = $user_data['user_id'];


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

// 4. Prepare Recent Contacts from transactions
$recent_contacts = [];
$transactions_copy = [];
while($tx = $transactions->fetch_assoc()){
    $transactions_copy[] = $tx;
    $is_sender = ($tx['sender_id'] == $user_db_id);
    $counterparty = $is_sender ? $tx['receiver_name'] : $tx['sender_name'];
    if (!isset($recent_contacts[$counterparty]) && $counterparty) {
        $recent_contacts[$counterparty] = [
            'name' => $counterparty,
            'photo' => 'https://ui-avatars.com/api/?name=' . urlencode($counterparty) . '&background=random'
        ];
    }
}
$transactions->data_seek(0);


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
    .welcome-card {
        background: #1c1c1c !important;
        font-family: 'Cinzel', serif;
        border: 1px solid rgba(170,145,100,0.22);
    }
    .welcome-card h1, .welcome-card h5 {
        color: #f0e8d8;
        text-shadow: 0 2px 15px rgba(0,0,0,0.3);
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
    .table {
        font-family: 'Inter', sans-serif;
    }
    .table thead {
        background-color: rgba(232, 223, 201, 0.4);
    }
    .hover-bg-light:hover {
        background-color: rgba(232, 223, 201, 0.4);
        cursor: pointer;
    }
    .transition {
        transition: background-color 0.2s ease;
    }
</style>

<div class="battle-page">
  <?php include '../includes/bg_scene.php'; ?>
    <div class="container content-wrapper mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white shadow-lg border-0 welcome-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h5 class="text-uppercase opacity-75 mb-1">Welcome back,</h5>
                                <h1 class="display-5 fw-bold mb-0"><?php echo htmlspecialchars($username); ?></h1>
                                <p class="mt-2 mb-0 opacity-75"><small>User ID: <?php echo htmlspecialchars($user_identifier); ?></small></p>
                            </div>
                            <div class="text-end mt-3 mt-md-0">
                                <h5 class="text-uppercase opacity-75 mb-1">War Chest Balance</h5>
                                <h1 class="display-4 fw-bold mb-0">Rs. <?php echo htmlspecialchars($current_balance); ?></h1>
                                <a href="../transfer/index.php" class="btn btn-light fw-bold mt-3 shadow-sm rounded-pill px-4 text-dark">
                                    Initiate Transfer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Main Column: Activity -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold text-dark">Recent Operations</h4>
                        <a href="../transfer/history.php" class="btn btn-outline-dark btn-sm rounded-pill">View All</a>
                    </div>
                    <div class="card-body px-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Date</th>
                                        <th>Type</th>
                                        <th>Entity</th>
                                        <th class="text-end pe-4">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($transactions_copy) == 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-5">
                                                <div class="py-4">
                                                    <h5 class="fw-normal text-secondary">No recent transactions.</h5>
                                                    <p class="small text-muted mb-0">The battlefield is quiet.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions_copy as $t): 
                                            $is_sender = ($t['sender_id'] == $user_db_id);
                                            $tx_type = $is_sender ? 'Sent' : 'Received';
                                            $badge_class = $is_sender ? 'bg-danger' : 'bg-success';
                                            $amount_class = $is_sender ? 'text-danger' : 'text-success';
                                            $sign = $is_sender ? '-' : '+';
                                            $counterparty = $is_sender ? $t['receiver_name'] : $t['sender_name'];
                                            $date = date('M j, Y g:i A', strtotime($t['created_at']));
                                        ?>
                                            <tr>
                                                <td class="ps-4"><?php echo $date; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $tx_type; ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($counterparty); ?></td>
                                                <td class="text-end pe-4 fw-bold <?php echo $amount_class; ?>">
                                                    <?php echo $sign; ?> Rs. <?php echo htmlspecialchars(number_format($t['amount'], 2)); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column: Search & Contacts -->
            <div class="col-lg-4">
                <!-- Search Section -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2">
                        <h5 class="mb-0 text-dark fw-bold font-family-sans-serif">Find Operatives (Users)</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="../profile/search.php" method="GET">
                            <div class="input-group">
                                <input type="text" name="query" class="form-control bg-light" placeholder="Username or ID..." required>
                                <button type="submit" class="btn btn-dark">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Contacts -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2">
                        <h5 class="mb-0 text-dark fw-bold font-family-sans-serif">Recent Users</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-column gap-3" style="max-height: 300px; overflow-y: auto;">
                            <?php if (empty($recent_contacts)): ?>
                                <p class="text-muted small">No recent contacts to show.</p>
                            <?php else: ?>
                                <?php foreach ($recent_contacts as $contact): ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded hover-bg-light transition">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $contact['photo']; ?>" alt="<?php echo htmlspecialchars($contact['name']); ?>" class="rounded-circle me-3" width="45" height="45">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($contact['name']); ?></h6>
                                                <small class="text-muted">Operative</small>
                                            </div>
                                        </div>
                                        <a href="../profile/view.php?user=<?php echo urlencode($contact['name']); ?>" class="btn btn-sm btn-light rounded-circle" title="View Profile">
                                            &rarr;
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>