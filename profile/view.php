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
if (isset($_GET['user_id']) && !empty(trim($_GET['user_id']))) {

    $target_user_id = trim($_GET['user_id']);

    $query = "SELECT u.username, u.user_id, u.created_at, p.biography, p.profile_image_path 
              FROM users u 
              LEFT JOIN profiles p ON u.id = p.user_id 
              WHERE u.user_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $profile_data = $result->fetch_assoc();
    } else {
        $error = "Target user not found on the battlefield.";
    }

} else {
    $error = "No user ID specified.";
}

include '../includes/header.php'; 
?>

<div style="display:flex;justify-content:center;margin-top:60px;">

<?php if ($error): ?>

<div style="width:700px;background:white;border-radius:12px;
box-shadow:0 8px 20px rgba(0,0,0,0.15);padding:40px;text-align:center;">

<h3 style="color:#e67e22;margin-bottom:15px;">
<?php echo htmlspecialchars($error); ?>
</h3>

<a href="../transfer/index.php"
style="background:#34495e;color:white;padding:10px 20px;
border-radius:6px;text-decoration:none;font-weight:bold;">
Return to Search
</a>

</div>

<?php else: ?>

<?php 
$img_path = !empty($profile_data['profile_image_path']) 
? $profile_data['profile_image_path'] 
: '../assets/uploads/default.png'; 
?>

<div style="width:700px;background:white;border-radius:12px;
box-shadow:0 8px 20px rgba(0,0,0,0.15);overflow:hidden;">

<div style="background:#34495e;color:white;padding:18px;text-align:center;
font-size:22px;font-weight:bold;">
User Profile: <?php echo htmlspecialchars($profile_data['username']); ?>
</div>

<div style="padding:40px;text-align:center;">

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

<div style="padding:20px;text-align:center;border-top:1px solid #eee;">

<?php if ($profile_data['username'] === $_SESSION['username']): ?>

<a href="edit.php"
style="background:#3498db;color:white;padding:10px 22px;
border-radius:6px;text-decoration:none;margin-right:10px;font-weight:bold;">
Edit Profile
</a>

<?php endif; ?>

<a href="../transfer/index.php?search_query=<?php echo urlencode($profile_data['user_id']); ?>"
style="border:2px solid #27ae60;color:#27ae60;padding:10px 22px;
border-radius:6px;text-decoration:none;font-weight:bold;">
Send Money
</a>

</div>

</div>

<?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>