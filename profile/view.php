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
    
    // Join users and profiles tables to get all the public info
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

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        
        <?php if ($error): ?>
            <div class="alert alert-warning fw-bold shadow-sm text-center">
                <?php echo htmlspecialchars($error); ?>
                <br><a href="../transfer/index.php" class="btn btn-sm btn-outline-dark mt-2">Return to Search</a>
            </div>
        <?php else: ?>
            
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white text-center">
                    <h4 class="mb-0">User Profile: <?php echo htmlspecialchars($profile_data['username']); ?></h4>
                </div>
                <div class="card-body p-4 text-center">
                    
                    <?php 
                        $img_path = !empty($profile_data['profile_image_path']) ? $profile_data['profile_image_path'] : '../assets/uploads/default.png'; 
                    ?>
                    <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Profile Image" class="img-thumbnail rounded-circle mb-3 shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">
                    
                    <h2 class="mb-1 fw-bold"><?php echo htmlspecialchars($profile_data['username']); ?></h2>
                    <p class="text-muted mb-4">
                        Enlisted: <?php echo date('F j, Y', strtotime($profile_data['created_at'])); ?> 
                        <br>
                        ID: <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($profile_data['user_id']); ?></span>
                    </p>
                    
                    <div class="text-start bg-light p-4 rounded shadow-sm">
                        <h5 class="fw-bold border-bottom pb-2">Biography</h5>
                        <p class="mb-0" style="white-space: pre-wrap;"><?php 
                            if (!empty($profile_data['biography'])) {
                                echo htmlspecialchars($profile_data['biography']);
                            } else {
                                echo '<span class="text-muted fst-italic">This user prefers to keep their strategies secret.</span>';
                            }
                        ?></p>
                    </div>
                </div>
                
                <div class="card-footer text-center bg-white border-0 pb-4">
                    <a href="../transfer/index.php?search_query=<?php echo urlencode($profile_data['user_id']); ?>" class="btn btn-success fw-bold px-4 shadow-sm">
                        Send Money to <?php echo htmlspecialchars($profile_data['username']); ?>
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include '../includes/footer.php'; ?>