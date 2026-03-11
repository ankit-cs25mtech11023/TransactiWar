<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

$username = $_POST['username'];
$email = $_POST['email'];
$bio = $_POST['biography'];

/* limit biography length */
if(strlen($bio) > 1000){
die("Biography too long (max 1000 characters)");
}

$_SESSION['profiles'][$username]['email'] = $email;
$_SESSION['profiles'][$username]['bio'] = $bio;

if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === 0) {

$upload_dir = "../assets/uploads/";

/* size limit */
if ($_FILES['profile_img']['size'] > 2 * 1024 * 1024) {
die("File too large (max 2MB)");
}

/* verify image */
$check = getimagesize($_FILES["profile_img"]["tmp_name"]);
if ($check === false) {
die("File is not an image.");
}

$ext = strtolower(pathinfo($_FILES["profile_img"]["name"], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif'];

if (!in_array($ext,$allowed)){
die("Invalid file type");
}

/* delete old image */
$old_img = $_SESSION['profiles'][$username]['img'];
if($old_img != "default.png"){
$old_path = $upload_dir . $old_img;
if(file_exists($old_path)){
unlink($old_path);
}
}

/* secure filename */
$secure_name = bin2hex(random_bytes(16)) . "." . $ext;
$target_file = $upload_dir . $secure_name;

move_uploaded_file($_FILES["profile_img"]["tmp_name"], $target_file);

$_SESSION['profiles'][$username]['img'] = $secure_name;
}

header("Location: view.php?user=" . urlencode($username));
exit();
}

include '../includes/header.php';

$username = $_GET['user'] ?? "Ankit";
$user = $_SESSION['profiles'][$username] ?? $default_users["Ankit"];

$email = $user['email'];
$bio = $user['bio'];
$img = $user['img'];
?>

<div style="display:flex;justify-content:center;margin-top:60px;">

<div style="width:750px;background:white;border-radius:12px;
box-shadow:0 8px 20px rgba(0,0,0,0.15);overflow:hidden;">

<div style="background:#34495e;color:white;padding:18px;
text-align:center;font-size:22px;font-weight:bold;">
Edit Profile
</div>

<div style="padding:40px;">

<form action="edit.php" method="POST" enctype="multipart/form-data">

<div style="display:flex;gap:30px;align-items:center;">

<div style="text-align:center;">

<img id="preview"
src="../assets/uploads/<?php echo htmlspecialchars($img); ?>"
style="width:150px;height:150px;border-radius:50%;
object-fit:cover;border:4px solid #ddd;margin-bottom:10px;">

<p style="font-size:13px;color:#777;margin-bottom:6px;">
Upload New Image
</p>

<input type="file" name="profile_img" accept="image/*"
onchange="previewImage(event)">

</div>

<div style="flex:1;">

<label style="font-weight:bold;">Username</label>

<input type="text" name="username"
value="<?php echo htmlspecialchars($username); ?>"
readonly
style="width:100%;padding:10px;margin-top:5px;margin-bottom:15px;
border-radius:6px;border:1px solid #ccc;background:#f3f3f3;">

<label style="font-weight:bold;">Email</label>

<input type="email" name="email"
value="<?php echo htmlspecialchars($email); ?>"
style="width:100%;padding:10px;margin-top:5px;
border-radius:6px;border:1px solid #ccc;">

</div>

</div>

<div style="margin-top:25px;">

<label style="font-weight:bold;">Biography</label>

<textarea id="bio"
name="biography"
maxlength="1000"
onkeyup="updateCounter()"
style="width:100%;height:120px;padding:10px;margin-top:5px;
border-radius:6px;border:1px solid #ccc;"><?php echo htmlspecialchars($bio); ?></textarea>

<p style="font-size:13px;color:#666;margin-top:5px;">
Characters remaining:
<span id="counter">1000</span>
</p>

</div>

<button type="submit"
style="margin-top:25px;width:100%;background:#3498db;color:white;
padding:12px;border:none;border-radius:6px;font-size:16px;font-weight:bold;
cursor:pointer;">
Save Changes
</button>

</form>

</div>

</div>

</div>

<script>

/* image preview */
function previewImage(event){
const reader = new FileReader();
reader.onload = function(){
document.getElementById('preview').src = reader.result;
};
reader.readAsDataURL(event.target.files[0]);
}

/* character counter */
function updateCounter(){
let max = 1000;
let current = document.getElementById("bio").value.length;
document.getElementById("counter").innerText = max-current;
}

updateCounter();

</script>

<?php include '../includes/footer.php'; ?>