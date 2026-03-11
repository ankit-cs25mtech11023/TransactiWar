<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK
if (!isset($_SESSION['db_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

$username = $_SESSION['username'];
$user_db_id = $_SESSION['db_id'];

// 1. Mandatory Logging
$ip_address = $_SERVER['REMOTE_ADDR'];
$webpage = "/profile/search.php";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("sss", $webpage, $username, $ip_address);
$log_stmt->execute();

$search_results = [];
$search_term = '';

// 2. Database Lookup for Partial Matches (Excluding the logged-in user)
if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $search_term = trim($_GET['query']);
    $search_like = "%" . $search_term . "%";
    
    $query = "SELECT u.username, u.user_id, p.profile_image_path 
              FROM users u 
              LEFT JOIN profiles p ON u.id = p.user_id 
              WHERE (u.username LIKE ? OR u.user_id = ?) AND u.id != ? 
              LIMIT 20";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $search_like, $search_term, $user_db_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $search_results[] = $row;
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
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1), 0 20px 50px rgba(0,0,0,0.15);
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
    h2, .cinzel-font {
        font-family: 'Cinzel', serif;
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
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .btn-dark:hover {
        background: #8b2500;
        color: #fff;
    }
    .btn-outline-dark {
        font-family: 'Cinzel', serif;
        text-transform: uppercase;
        letter-spacing: 0.05em;
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

    <div class="container content-wrapper">
        <div class="row mt-4 mb-4">
            <!-- <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                    <h2 class="fw-bold mb-0">Operative Directory</h2>
                    <a href="../transfer/index.php" class="btn btn-sm btn-light">War Chest</a>
                </div>
            </div> -->
        </div>

        <div class="row mb-5 justify-content-center">
            <div class="col-md-8">
                <form action="search.php" method="GET">
                    <div class="input-group input-group-lg shadow-sm" style="border: 1px solid rgba(170,145,100,0.5); border-radius: 6px; overflow: hidden;">
                        <input type="text" name="query" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by Username or ID..." required style="background: rgba(255,253,248,0.9) !important;">
                        <button type="submit" class="btn btn-dark px-4 fw-bold">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($search_term)): ?>
                <?php if (count($search_results) > 0): ?>
                    <?php foreach ($search_results as $user): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 text-center p-4">
                                <?php $img_src = "avatar.php?user_id=" . urlencode($user['user_id']); ?>
                                <img src="<?php echo $img_src; ?>" alt="Profile" class="rounded-circle mx-auto mb-3 shadow-sm" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #e8dfc9;">
                                
                                <h5 class="fw-bold mb-1 text-dark cinzel-font"><?php echo htmlspecialchars($user['username']); ?></h5>
                                <p class="text-muted small mb-3 border-bottom pb-3"><?php echo htmlspecialchars($user['user_id']); ?></p>
                                
                                <a href="view.php?user=<?php echo urlencode($user['user_id']); ?>" class="btn btn-outline-dark btn-sm w-100 mt-auto rounded-pill">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="card d-inline-block px-5 py-4">
                            <h4 class="fw-normal cinzel-font mb-2">No operatives found matching "<?php echo htmlspecialchars($search_term); ?>"</h4>
                            <p class="text-muted mb-0">The battlefield yields no intelligence on this target.</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>