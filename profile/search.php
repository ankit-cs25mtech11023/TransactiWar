<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK
if (!isset($_SESSION['db_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

$username = $_SESSION['username'];
$user_db_id = $_SESSION['db_id'];

// 1. Mandatory Logging
$ip_address = $_SERVER['REMOTE_ADDR'];
$webpage = "/profile/search.php";
$log_stmt = $conn->prepare("INSERT INTO activity_logs (webpage, username, ip_address) VALUES (?, ?, ?)");
$log_stmt->bind_param("sss", $webpage, $username, $ip_address);
$log_stmt->execute();

$search_results = [];
$search_term = '';

// 2. Database Lookup for Partial Matches (Excluding the logged-in user)
if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $search_term = trim($_GET['query']);
    $search_like = "%" . $search_term . "%";
    
    $query = "SELECT u.username, u.user_id, p.profile_image_path 
              FROM users u 
              LEFT JOIN profiles p ON u.id = p.user_id 
              WHERE (u.username LIKE ? OR u.user_id = ?) AND u.id != ? 
              LIMIT 20";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $search_like, $search_term, $user_db_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $search_results[] = $row;
    }
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
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1), 0 20px 50px rgba(0,0,0,0.15);
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
    h2, .cinzel-font {
        font-family: 'Cinzel', serif;
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
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .btn-dark:hover {
        background: #8b2500;
        color: #fff;
    }
    .btn-outline-dark {
        font-family: 'Cinzel', serif;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>

<div class="battle-page">
    <?php include '../includes/bg_scene.php'; ?>
    <div class="container content-wrapper">
        <div class="row mt-4 mb-4">
        </div>

        <div class="row mb-5 justify-content-center">
            <div class="col-md-8">
                <form action="search.php" method="GET">
                    <div class="input-group input-group-lg shadow-sm" style="border: 1px solid rgba(170,145,100,0.5); border-radius: 6px; overflow: hidden;">
                        <input type="text" name="query" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by Username or ID..." required style="background: rgba(255,253,248,0.9) !important;">
                        <button type="submit" class="btn btn-dark px-4 fw-bold">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($search_term)): ?>
                <?php if (count($search_results) > 0): ?>
                    <?php foreach ($search_results as $user): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 text-center p-4">
                                <?php $img_src = "avatar.php?user_id=" . urlencode($user['user_id']); ?>
                                <img src="<?php echo $img_src; ?>" alt="Profile" class="rounded-circle mx-auto mb-3 shadow-sm" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #e8dfc9;">
                                
                                <h5 class="fw-bold mb-1 text-dark cinzel-font"><?php echo htmlspecialchars($user['username']); ?></h5>
                                <p class="text-muted small mb-3 border-bottom pb-3"><?php echo htmlspecialchars($user['user_id']); ?></p>
                                
                                <a href="view.php?user=<?php echo urlencode($user['user_id']); ?>" class="btn btn-outline-dark btn-sm w-100 mt-auto rounded-pill">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="card d-inline-block px-5 py-4">
                            <h4 class="fw-normal cinzel-font mb-2">No operatives found matching "<?php echo htmlspecialchars($search_term); ?>"</h4>
                            <p class="text-muted mb-0">The battlefield yields no intelligence on this target.</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>