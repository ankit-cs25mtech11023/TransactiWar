<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    // header("Location: ../auth/login.php");
    // exit();
}

include '../includes/header.php'; 
?>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <h2 class="fw-bold mb-0">Transaction Ledger</h2>
            <a href="index.php" class="btn btn-sm btn-primary">New Transfer</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Counterparty (ID)</th>
                                <th>Amount</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Mar 11, 2026 14:30</td>
                                <td><span class="badge bg-danger">Sent</span></td>
                                <td>Ambarish Sarkar (CS25MTECH12345)</td>
                                <td class="text-danger fw-bold">- Rs. 50</td>
                                <td class="text-muted fst-italic">"Thanks for the help with the LLVM pass!"</td>
                            </tr>
                            <tr>
                                <td>Mar 10, 2026 09:15</td>
                                <td><span class="badge bg-success">Received</span></td>
                                <td>System (SYSTEM_INIT)</td>
                                <td class="text-success fw-bold">+ Rs. 100</td>
                                <td class="text-muted fst-italic">"Initial Account Credit"</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>