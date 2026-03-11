<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Ensure the user is logged in before they can edit a profile
if (!isset($_SESSION['user_id'])) {
    // We will uncomment this redirect later when the DB is hooked up
    // header("Location: ../auth/login.php"); 
    // exit();
}

include '../includes/header.php'; 

// Temporary mock data for the UI
$mock_username = "Ankit";
$mock_email = "cs25mtech11023@iith.ac.in";
$mock_bio = "Computer Science enthusiast. Ready for the War Game.";
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white text-center">
                <h4 class="mb-0">Edit Profile</h4>
            </div>
            <div class="card-body p-4">
                <form action="edit.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <img src="../assets/uploads/default.png" alt="Profile Image" class="img-thumbnail rounded-circle mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                            <div class="mb-3">
                                <label for="profile_img" class="form-label text-muted fw-bold small">Upload New Image</label>
                                <input class="form-control form-control-sm" type="file" id="profile_img" name="profile_img" accept="image/*">
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="username" class="form-label text-muted fw-bold">Username (Cannot be changed)</label>
                                <input type="text" class="form-control bg-light" id="username" name="username" value="<?php echo htmlspecialchars($mock_username); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label text-muted fw-bold">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($mock_email); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="biography" class="form-label text-muted fw-bold">Biography</label>
                        <textarea class="form-control" id="biography" name="biography" rows="5" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($mock_bio); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>