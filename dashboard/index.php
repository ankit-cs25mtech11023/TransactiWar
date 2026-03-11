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
    <svg class="bg-scene" viewBox="0 0 1440 600" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
    <ellipse cx="720" cy="580" rx="900" ry="260" fill="#7a3e00" opacity="0.4"/>
    <polygon points="0,430 170,210 340,430" fill="#5a3010"/>
    <polygon points="220,430 430,165 640,430" fill="#4a2808"/>
    <polygon points="490,430 700,185 910,430" fill="#5a3010"/>
    <polygon points="800,430 1020,155 1240,430" fill="#4a2808"/>
    <polygon points="1100,430 1300,210 1440,380 1440,430" fill="#5a3010"/>
    <rect x="0" y="430" width="1440" height="170" fill="#2e1800"/>
    <g fill="#1e1000">
      <rect x="585" y="155" width="270" height="275"/>
      <rect x="580" y="143" width="21" height="21"/><rect x="607" y="143" width="21" height="21"/>
      <rect x="634" y="143" width="21" height="21"/><rect x="661" y="143" width="21" height="21"/>
      <rect x="688" y="143" width="21" height="21"/><rect x="715" y="143" width="21" height="21"/>
      <rect x="742" y="143" width="21" height="21"/><rect x="769" y="143" width="21" height="21"/>
      <rect x="796" y="143" width="21" height="21"/>
      <rect x="525" y="200" width="78" height="230"/>
      <rect x="520" y="188" width="17" height="17"/><rect x="541" y="188" width="17" height="17"/>
      <rect x="562" y="188" width="17" height="17"/><rect x="583" y="188" width="17" height="17"/>
      <rect x="837" y="200" width="78" height="230"/>
      <rect x="832" y="188" width="17" height="17"/><rect x="853" y="188" width="17" height="17"/>
      <rect x="874" y="188" width="17" height="17"/><rect x="895" y="188" width="17" height="17"/>
      <rect x="685" y="310" width="70" height="120"/>
      <ellipse cx="720" cy="310" rx="35" ry="25" fill="#100800"/>
      <rect x="559" y="143" width="3" height="48"/>
      <polygon points="562,143 592,155 562,167" fill="#8b0000"/>
      <rect x="874" y="143" width="3" height="48"/>
      <polygon points="877,143 907,155 877,167" fill="#8b0000"/>
      <rect x="718" y="105" width="3" height="48"/>
      <polygon points="721,105 754,118 721,131" fill="#8b0000"/>
      <rect x="615" y="195" width="22" height="32"/><rect x="662" y="195" width="22" height="32"/>
      <rect x="756" y="195" width="22" height="32"/><rect x="803" y="195" width="22" height="32"/>
      <rect x="635" y="268" width="18" height="26"/><rect x="787" y="268" width="18" height="26"/>
    </g>
    <g fill="#180e00">
      <rect x="295" y="345" width="250" height="85"/>
      <rect x="290" y="334" width="17" height="17"/><rect x="311" y="334" width="17" height="17"/>
      <rect x="332" y="334" width="17" height="17"/><rect x="353" y="334" width="17" height="17"/>
      <rect x="374" y="334" width="17" height="17"/><rect x="395" y="334" width="17" height="17"/>
      <rect x="416" y="334" width="17" height="17"/><rect x="437" y="334" width="17" height="17"/>
      <rect x="458" y="334" width="17" height="17"/><rect x="479" y="334" width="17" height="17"/>
      <rect x="500" y="334" width="17" height="17"/>
      <rect x="268" y="295" width="55" height="135"/>
      <rect x="263" y="284" width="15" height="15"/><rect x="280" y="284" width="15" height="15"/>
      <rect x="297" y="284" width="15" height="15"/><rect x="314" y="284" width="15" height="15"/>
    </g>
    <g fill="#180e00">
      <rect x="895" y="345" width="250" height="85"/>
      <rect x="890" y="334" width="17" height="17"/><rect x="911" y="334" width="17" height="17"/>
      <rect x="932" y="334" width="17" height="17"/><rect x="953" y="334" width="17" height="17"/>
      <rect x="974" y="334" width="17" height="17"/><rect x="995" y="334" width="17" height="17"/>
      <rect x="1016" y="334" width="17" height="17"/><rect x="1037" y="334" width="17" height="17"/>
      <rect x="1058" y="334" width="17" height="17"/><rect x="1079" y="334" width="17" height="17"/>
      <rect x="1100" y="334" width="17" height="17"/>
      <rect x="1117" y="295" width="55" height="135"/>
      <rect x="1112" y="284" width="15" height="15"/><rect x="1129" y="284" width="15" height="15"/>
      <rect x="1146" y="284" width="15" height="15"/><rect x="1163" y="284" width="15" height="15"/>
    </g>
    <ellipse cx="720" cy="432" rx="210" ry="16" fill="#110a00" opacity="0.5"/>
    <g fill="#120800" opacity="0.5">
      <rect x="55" y="385" width="10" height="48"/><circle cx="60" cy="380" r="7"/><rect x="63" y="350" width="2" height="32"/>
      <rect x="84" y="381" width="10" height="52"/><circle cx="89" cy="376" r="7"/><rect x="92" y="346" width="2" height="32"/>
      <rect x="113" y="387" width="10" height="46"/><circle cx="118" cy="382" r="7"/><rect x="121" y="352" width="2" height="32"/>
      <rect x="142" y="383" width="10" height="50"/><circle cx="147" cy="378" r="7"/><rect x="150" y="348" width="2" height="32"/>
      <rect x="171" y="385" width="10" height="48"/><circle cx="176" cy="380" r="7"/><rect x="179" y="350" width="2" height="32"/>
    </g>
    <g fill="#120800" opacity="0.5">
      <rect x="1225" y="385" width="10" height="48"/><circle cx="1230" cy="380" r="7"/><rect x="1233" y="350" width="2" height="32"/>
      <rect x="1254" y="381" width="10" height="52"/><circle cx="1259" cy="376" r="7"/><rect x="1262" y="346" width="2" height="32"/>
      <rect x="1283" y="387" width="10" height="46"/><circle cx="1288" cy="382" r="7"/><rect x="1291" y="352" width="2" height="32"/>
      <rect x="1312" y="383" width="10" height="50"/><circle cx="1317" cy="378" r="7"/><rect x="1320" y="348" width="2" height="32"/>
      <rect x="1341" y="385" width="10" height="48"/><circle cx="1346" cy="380" r="7"/><rect x="1349" y="350" width="2" height="32"/>
    </g>
  </svg>
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
                        <h5 class="mb-0 text-dark fw-bold font-family-sans-serif">Find Operatives</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="../profile/view.php" method="GET">
                            <div class="input-group">
                                <input type="text" name="user" class="form-control bg-light" placeholder="Username or ID..." required>
                                <button type="submit" class="btn btn-dark">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Contacts -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-2">
                        <h5 class="mb-0 text-dark fw-bold font-family-sans-serif">Recent Contacts</h5>
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