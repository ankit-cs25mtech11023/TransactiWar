<?php
// Temporary mock data to test the logged-in navbar view
session_start();
// Uncomment the next two lines to test what the navbar looks like when logged in
// $_SESSION['user_id'] = 'CS25MTECH11023';
// $_SESSION['username'] = 'Ankit';

include 'includes/header.php'; 
?>

<div class="row mt-5">
    <div class="col-md-12 text-center">
        <h1 class="display-4">Welcome to TransactiWar</h1>
        <p class="lead">Prepare for Battle.</p>
        <hr class="my-4">
        <p>Your Docker environment is successfully up and running.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>