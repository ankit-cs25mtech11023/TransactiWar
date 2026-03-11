<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../includes/header.php'; 

// Temporary mock data for the UI
$viewed_user = "Ambarish";
$viewed_bio = "Defending my application against all attacks.";
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white text-center">
                <h4 class="mb-0">User Profile: <?php echo htmlspecialchars($viewed_user); ?></h4>
            </div>
            <div class="card-body p-4 text-center">
                <img src="../assets/uploads/default.png" alt="Profile Image" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                
                <h2 class="mb-1"><?php echo htmlspecialchars($viewed_user); ?></h2>
                <p class="text-muted mb-4">Joined March 2026</p>
                
                <div class="text-start bg-light p-4 rounded">
                    <h5 class="fw-bold border-bottom pb-2">Biography</h5>
                    <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($viewed_bio); ?></p>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="../transfer/index.php?to=<?php echo urlencode($viewed_user); ?>" class="btn btn-outline-success">Send Money to <?php echo htmlspecialchars($viewed_user); ?></a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>