<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK
if (!isset($_SESSION['db_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

$user_db_id = $_SESSION['db_id'];
$username = $_SESSION['username'];
$user_public_id = $_SESSION['user_id'];

$error = '';
$success = '';

// 1. Mandatory Logging
$ip_address = $_SERVER['REMOTE_ADDR'];
$webpage = "/profile/edit.php";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("sss", $webpage, $username, $ip_address);
$log_stmt->execute();

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $new_email = trim($_POST['email']);
    $new_bio = trim($_POST['biography']);

    // Update Email
    $update_user = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $update_user->bind_param("si", $new_email, $user_db_id);
    $update_user->execute();

    // Update Biography
    $update_bio = $conn->prepare("UPDATE profiles SET biography = ? WHERE user_id = ?");
    $update_bio->bind_param("si", $new_bio, $user_db_id);
    $update_bio->execute();

    // SECURE IMAGE UPLOAD
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {

        $file_tmp_path = $_FILES['profile_img']['tmp_name'];
        $file_name = $_FILES['profile_img']['name'];
        $file_size = $_FILES['profile_img']['size'];

        $upload_dir = '../assets/uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_mime_types = ['image/jpeg','image/png','image/gif'];
        $file_mime_type = mime_content_type($file_tmp_path);

        $allowed_extensions = ['jpg','jpeg','png','gif'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_mime_type,$allowed_mime_types) || !in_array($file_extension,$allowed_extensions)) {

            $error = "Security Alert: Invalid file format.";

        } elseif ($file_size > 2000000) {

            $error = "File too large (Max 2MB).";

        } else {

            $new_file_name = $user_public_id . '_' . time() . '.' . $file_extension;
            
            // THE SECURE FIX: Point to the isolated directory outside the web root
            $secure_upload_dir = '/var/www/uploads/';
            $destination_path = $secure_upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                // Only save the filename to the database, NOT the path
                $update_img = $conn->prepare("UPDATE profiles SET profile_image_path = ? WHERE user_id = ?");
                $update_img->bind_param("si", $new_file_name, $user_db_id);
                $update_img->execute();
            } else {
                $error = "Error saving file to secure storage.";
            }
        }
    }

    if (empty($error)) {
        $success = "Profile updated successfully!";
    }
}

// Fetch current user data
$query = "SELECT u.username, u.email, p.biography, p.profile_image_path 
          FROM users u 
          LEFT JOIN profiles p ON u.id = p.user_id 
          WHERE u.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_db_id);
$stmt->execute();
$current_data = $stmt->get_result()->fetch_assoc();

include '../includes/header.php'; 
?>

<div style="display:flex;justify-content:center;margin-top:60px;">

<div style="width:750px;background:white;border-radius:12px;
box-shadow:0 8px 20px rgba(0,0,0,0.15);overflow:hidden;">

<div style="background:#34495e;color:white;padding:18px;
text-align:center;font-size:22px;font-weight:bold;">
Edit Profile
</div>

<div style="padding:40px;">

<?php if ($error): ?>
<div class="alert alert-danger fw-bold"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success fw-bold"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form action="edit.php" method="POST" enctype="multipart/form-data">

<div style="display:flex;gap:30px;align-items:center;">

<div style="text-align:center;">

<img id="preview"
src="avatar.php"
style="width:150px;height:150px;border-radius:50%;
object-fit:cover;border:4px solid #ddd;margin-bottom:10px;">

<p style="font-size:13px;color:#777;margin-bottom:6px;">
Upload New Image
</p>

<input type="file"
name="profile_img"
accept="image/png, image/jpeg, image/gif"
onchange="previewImage(event)">

</div>

<div style="flex:1;">

<label style="font-weight:bold;">Username</label>

<input type="text"
value="<?php echo htmlspecialchars($current_data['username']); ?>"
readonly
style="width:100%;padding:10px;margin-top:5px;margin-bottom:15px;
border-radius:6px;border:1px solid #ccc;background:#f3f3f3;">

<label style="font-weight:bold;">Email</label>

<input type="email"
name="email"
value="<?php echo htmlspecialchars($current_data['email']); ?>"
required
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
border-radius:6px;border:1px solid #ccc;"><?php echo htmlspecialchars($current_data['biography'] ?? ''); ?></textarea>

<p style="font-size:13px;color:#666;margin-top:5px;">
Characters remaining:
<span id="counter">1000</span>
</p>

</div>

<button type="submit"
style="margin-top:25px;width:100%;background:#3498db;color:white;
padding:12px;border:none;border-radius:6px;font-size:16px;font-weight:bold;cursor:pointer;">
Save Changes
</button>

</form>

</div>

</div>

</div>

<script>

function previewImage(event){
const reader = new FileReader();
reader.onload = function(){
document.getElementById('preview').src = reader.result;
};
reader.readAsDataURL(event.target.files[0]);
}

function updateCounter(){
let max = 1000;
let current = document.getElementById("bio").value.length;
document.getElementById("counter").innerText = max-current;
}

updateCounter();

</script>

<?php include '../includes/footer.php'; ?>