<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY CHECK: Kick out unauthenticated users
if (!isset($_SESSION['user_id'])) {
    // header("Location: ../auth/login.php");
    // exit();
}

include '../includes/header.php'; 

// Temporary mock data
$mock_balance = 100;
$mock_search_results = []; // Will populate from DB later
?>

<div class="row mt-4">
    <div class="col-md-12 mb-3">
        <h2 class="fw-bold border-bottom pb-2">Money Transfer Operations</h2>
        <p class="text-muted">Current Available Balance: <strong class="text-success">Rs. <?php echo $mock_balance; ?></strong></p>
    </div>
</div>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Search Users</h5>
            </div>
            <div class="card-body bg-light">
                <form action="index.php" method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search_query" placeholder="Enter Username or User ID..." required>
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                    </div>
                </form>
                
                <div class="list-group">
                    <div class="list-group-item list-group-item-action py-3">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold">Ambarish Sarkar</h6>
                                <small class="text-muted">ID: CS25MTECH12345</small>
                            </div>
                            <button class="btn btn-sm btn-primary">Select</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Initiate Transfer</h5>
            </div>
            <div class="card-body p-4">
                <form action="index.php" method="POST">
                    <div class="mb-3">
                        <label for="receiver_id" class="form-label fw-bold text-muted">Receiver User ID</label>
                        <input type="text" class="form-control" id="receiver_id" name="receiver_id" placeholder="e.g., CS25MTECH12345" required>
                        <div class="form-text text-danger">Transfers must be made using the exact User ID.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label fw-bold text-muted">Amount (Rs.)</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1" step="1" placeholder="0" required>
                    </div>

                    <div class="mb-4">
                        <label for="comment" class="form-label fw-bold text-muted">Comment (Optional)</label>
                        <textarea class="form-control" id="comment" name="comment" rows="2" placeholder="Add a message for the receiver..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold">Confirm Transfer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>