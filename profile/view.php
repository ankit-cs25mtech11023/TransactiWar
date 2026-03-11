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
    <svg class="bg-scene" viewBox="0 0 1440 600" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
    <ellipse cx="720" cy="580" rx="900" ry="260" fill="#7a3e00" opacity="0.4"/>
    <polygon points="0,430 170,210 340,430" fill="#5a3010"/>
    <polygon points="220,430 430,165 640,430" fill="#4a2808"/>
    <polygon points="490,430 700,185 910,430" fill="#5a3010"/>
    <polygon points="800,430 1020,155 1240,430" fill="#4a2808"/>
    <polygon points="1100,430 1300,210 1440,380 1440,430" fill="#5a3010"/>
    <rect x="0" y="430" width="1440" height="170" fill="#2e1800"/>
    </g>
  </svg>
    <div class="container content-wrapper mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($error): ?>
                    <div class="card p-4 text-center">
                        <h3 class="text-warning mb-3"><?php echo htmlspecialchars($error); ?></h3>
                        <a href="../dashboard/index.php" class="btn btn-dark">Return to Search</a>
                    </div>
                <?php else: ?>
                    <div class="card" style="overflow:hidden;">
                        <div class="card-header">
                            User Profile: <?php echo htmlspecialchars($profile_data['username']); ?>
                        </div>
                        <div class="card-body p-4 text-center">
                            <img src="avatar.php?user_id=<?php echo urlencode($profile_data['user_id']); ?>" class="img-fluid rounded-circle mb-3" style="width: 160px; height: 160px; object-fit: cover; border: 4px solid #ddd;">
                            <h2 class="mb-1"><?php echo htmlspecialchars($profile_data['username']); ?></h2>
                            <p class="text-muted mb-2">
                                Joined <?php echo date('F j, Y', strtotime($profile_data['created_at'])); ?>
                            </p>
                            <p class="text-muted mb-4 small">
                                ID: <span class="badge bg-secondary"><?php echo htmlspecialchars($profile_data['user_id']); ?></span>
                            </p>
                            <div class="text-start bg-light p-3 rounded border">
                                <h4 class="mb-2 border-bottom pb-2">Biography</h4>
                                <p class="mb-0" style="white-space: pre-wrap;">
                                    <?php
                                    if (!empty($profile_data['biography'])) {
                                        echo htmlspecialchars($profile_data['biography']);
                                    } else {
                                        echo '<span class="text-muted fst-italic">This user prefers to keep their strategies secret.</span>';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="card-footer p-3 text-center">
                            <?php if ($profile_data['username'] === $_SESSION['username']): ?>
                                <a href="edit.php" class="btn btn-light me-2">Edit Profile</a>
                            <?php endif; ?>
                            <a href="../transfer/index.php?search_query=<?php echo urlencode($profile_data['user_id']); ?>" class="btn btn-success">Send Money</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>