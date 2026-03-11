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

        $upload_dir = '/var/www/uploads/';

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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        Edit Profile
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                        <div class="alert alert-danger fw-bold"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success fw-bold"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form action="edit.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <img id="preview" src="avatar.php" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #ddd;">
                                    <label for="profile_img" class="form-label">Upload New Image</label>
                                    <input type="file" class="form-control" name="profile_img" id="profile_img" accept="image/png, image/jpeg, image/gif" onchange="previewImage(event)">
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_data['username']); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($current_data['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label for="bio" class="form-label">Biography</label>
                                <textarea id="bio" name="biography" class="form-control" maxlength="1000" onkeyup="updateCounter()" rows="4"><?php echo htmlspecialchars($current_data['biography'] ?? ''); ?></textarea>
                                <p class="form-text">Characters remaining: <span id="counter">1000</span></p>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 mt-3">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
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