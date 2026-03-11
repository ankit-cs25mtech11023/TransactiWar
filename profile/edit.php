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
    
    // Update Email in users table
    $update_user = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $update_user->bind_param("si", $new_email, $user_db_id);
    $update_user->execute();

    // Update Biography in profiles table
    $update_bio = $conn->prepare("UPDATE profiles SET biography = ? WHERE user_id = ?");
    $update_bio->bind_param("si", $new_bio, $user_db_id);
    $update_bio->execute();

    // 3. SECURE IMAGE UPLOAD LOGIC
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['profile_img']['tmp_name'];
        $file_name = $_FILES['profile_img']['name'];
        $file_size = $_FILES['profile_img']['size'];
        
        // Ensure the uploads directory exists
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Validate File Type (Security against PHP shell uploads)
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_mime_type = mime_content_type($file_tmp_path);
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_mime_type, $allowed_mime_types) || !in_array($file_extension, $allowed_extensions)) {
            $error = "Security Alert: Invalid file format. Only JPG, PNG, and GIF are allowed.";
        } elseif ($file_size > 2000000) { // Limit to 2MB
            $error = "File is too large. Maximum size is 2MB.";
        } else {
            // Generate a secure, unique filename so users can't overwrite each other's files
            $new_file_name = $user_public_id . '_' . time() . '.' . $file_extension;
            $destination_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_path, $destination_path)) {
                // Save the path to the database
                $db_image_path = '../assets/uploads/' . $new_file_name;
                $update_img = $conn->prepare("UPDATE profiles SET profile_image_path = ? WHERE user_id = ?");
                $update_img->bind_param("si", $db_image_path, $user_db_id);
                $update_img->execute();
            } else {
                $error = "There was an error moving the uploaded file. Check folder permissions.";
            }
        }
    }

    if (empty($error)) {
        $success = "Profile updated successfully!";
    }
}

// 4. Fetch Current User Data to populate the HTML form
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

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        
        <?php if ($error): ?>
            <div class="alert alert-danger fw-bold shadow-sm"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success fw-bold shadow-sm"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white text-center">
                <h4 class="mb-0">Edit Profile</h4>
            </div>
            <div class="card-body p-4">
                <form action="edit.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img src="<?php echo htmlspecialchars($current_data['profile_image_path']); ?>" alt="Profile Image" class="img-thumbnail rounded-circle mb-2 shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">
                            <div class="mb-3">
                                <label for="profile_img" class="form-label text-muted fw-bold small">Upload New Image</label>
                                <input class="form-control form-control-sm" type="file" id="profile_img" name="profile_img" accept="image/png, image/jpeg, image/gif">
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="username" class="form-label text-muted fw-bold">Username</label>
                                <input type="text" class="form-control bg-light" id="username" name="username" value="<?php echo htmlspecialchars($current_data['username']); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label text-muted fw-bold">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($current_data['email']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="biography" class="form-label text-muted fw-bold">Biography</label>
                        <textarea class="form-control" id="biography" name="biography" rows="5" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($current_data['biography'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>