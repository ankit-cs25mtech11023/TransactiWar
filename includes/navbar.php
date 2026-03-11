<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php">TransactiWar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link text-warning">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard/index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/profile/edit.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/transfer/index.php">Transfer Money</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/register.php">Register</a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>