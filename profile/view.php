<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK
if (!isset($_SESSION['db_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

$viewer_username = $_SESSION['username'];

// 1. Mandatory Logging
$ip_address = $_SERVER['REMOTE_ADDR'];
$webpage = "/profile/view.php";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("sss", $webpage, $viewer_username, $ip_address);
$log_stmt->execute();

$error = '';
$profile_data = null;

// 2. Fetch Target User Data based on the URL parameter ?user_id=WAR-XXXX
if (isset($_GET['user']) && !empty(trim($_GET['user']))) {
    $search_param = trim($_GET['user']);
    $search_like = "%" . $search_param . "%";

    $query = "SELECT u.username, u.user_id, u.created_at, p.biography, p.profile_image_path 
              FROM users u 
              LEFT JOIN profiles p ON u.id = p.user_id 
              WHERE u.user_id = ? OR u.username LIKE ?
              LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $search_param, $search_like);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $profile_data = $result->fetch_assoc();
    } else {
        $error = "Target user not found on the battlefield.";
    }
} else {
    $error = "No user specified.";
}

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
        <div class="row justify-content-center">
            <div class="col-lg-8">

<?php if ($error): ?>

<div class="card p-4 text-center">

<h3 style="color:#e67e22;margin-bottom:15px;">
<?php echo htmlspecialchars($error); ?>
</h3>

<a href="../transfer/index.php"
class="btn btn-dark">
Return to Search
</a>

</div>

<?php else: ?>

<?php 
$img_path = !empty($profile_data['profile_image_path']) 
? $profile_data['profile_image_path'] 
: '../assets/uploads/default.png'; 
?>

<div class="card" style="overflow:hidden;">

<div class="card-header">
User Profile: <?php echo htmlspecialchars($profile_data['username']); ?>
</div>

<div class="card-body p-4 text-center">

<img src="<?php echo htmlspecialchars($img_path); ?>"
style="width:160px;height:160px;border-radius:50%;
object-fit:cover;border:4px solid #ddd;margin-bottom:15px;">

<h2 style="margin-bottom:5px;">
<?php echo htmlspecialchars($profile_data['username']); ?>
</h2>

<p style="color:#777;margin-bottom:10px;">
Joined <?php echo date('F j, Y', strtotime($profile_data['created_at'])); ?>
</p>

<p style="color:#777;margin-bottom:25px;font-size:14px;">
ID: 
<span style="background:#eee;padding:3px 8px;border-radius:4px;border:1px solid #ccc;">
<?php echo htmlspecialchars($profile_data['user_id']); ?>
</span>
</p>

<div style="text-align:left;background:#f8f9fa;padding:25px;
border-radius:8px;border:1px solid #eee;">

<h4 style="margin-bottom:10px;border-bottom:1px solid #ddd;padding-bottom:5px;">
Biography
</h4>

<p style="white-space:pre-wrap;color:#444;">

<?php
if (!empty($profile_data['biography'])) {
    echo htmlspecialchars($profile_data['biography']);
} else {
    echo '<span style="color:#888;font-style:italic;">This user prefers to keep their strategies secret.</span>';
}
?>

</p>

</div>

</div>

<div class="card-footer p-3 text-center">

<?php if ($profile_data['username'] === $_SESSION['username']): ?>

<a href="edit.php"
class="btn btn-primary me-2">
Edit Profile
</a>

<?php endif; ?>

<a href="../transfer/index.php?search_query=<?php echo urlencode($profile_data['user_id']); ?>"
class="btn btn-success">
Send Money
</a>

</div>

</div>

<?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>