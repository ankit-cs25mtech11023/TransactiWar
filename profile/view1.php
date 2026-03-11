<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../includes/header.php';

$default_users = [

"Ankit" => [
    "email" => "cs25mtech11023@iith.ac.in",
    "bio" => "Computer Science enthusiast. Ready for the War Game.",
    "img" => "default.png"
],

"Ambarish" => [
    "email" => "ambarish@iith.ac.in",
    "bio" => "Defending my application against all attacks.",
    "img" => "default.png"
],

"Aayush" => [
    "email" => "aayush@iith.ac.in",
    "bio" => "Cybersecurity learner exploring vulnerabilities.",
    "img" => "default.png"
],

"Saurabh" => [
    "email" => "saurabh@iith.ac.in",
    "bio" => "Cybersecurity learner exploring vulnerabilities.",
    "img" => "default.png"
]

];

if (!isset($_SESSION['profiles'])) {
    $_SESSION['profiles'] = $default_users;
}

$username = $_GET['user'] ?? "Ankit";
$user = $_SESSION['profiles'][$username] ?? $default_users["Ankit"];

$email = $user['email'];
$bio = $user['bio'];
$img = $user['img'];
?>

<div style="display:flex;justify-content:center;margin-top:60px;">

<div style="width:700px;background:white;border-radius:12px;
box-shadow:0 8px 20px rgba(0,0,0,0.15);overflow:hidden;">

<div style="background:#34495e;color:white;padding:18px;text-align:center;
font-size:22px;font-weight:bold;">
User Profile: <?php echo htmlspecialchars($username); ?>
</div>

<div style="padding:40px;text-align:center;">

<img src="../assets/uploads/<?php echo htmlspecialchars($img); ?>"
style="width:160px;height:160px;border-radius:50%;
object-fit:cover;border:4px solid #ddd;margin-bottom:15px;">

<h2 style="margin-bottom:5px;"><?php echo htmlspecialchars($username); ?></h2>

<p style="color:#777;margin-bottom:25px;">
Joined March 2026
</p>

<div style="text-align:left;background:#f8f9fa;padding:25px;
border-radius:8px;border:1px solid #eee;">

<h4 style="margin-bottom:10px;border-bottom:1px solid #ddd;padding-bottom:5px;">
Biography
</h4>

<p style="white-space:pre-wrap;color:#444;">
<?php echo htmlspecialchars($bio); ?>
</p>

</div>

</div>

<div style="padding:20px;text-align:center;border-top:1px solid #eee;">

<a href="edit.php?user=<?php echo urlencode($username); ?>"
style="background:#3498db;color:white;padding:10px 22px;
border-radius:6px;text-decoration:none;margin-right:10px;font-weight:bold;">
Edit Profile
</a>

<a href="../transfer/index.php?to=<?php echo urlencode($username); ?>"
style="border:2px solid #27ae60;color:#27ae60;padding:10px 22px;
border-radius:6px;text-decoration:none;font-weight:bold;">
Send Money
</a>

</div>

</div>

</div>

<?php include '../includes/footer.php'; ?>