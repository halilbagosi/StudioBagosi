<?php
// Get the current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="../images/Logo/navbar.PNG" alt="Studio Bagosi Logo" style="height: 40px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
                        <i class="fas fa-calendar-check me-1"></i> Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'gallery.php' ? 'active' : ''; ?>" href="gallery.php">
                        <i class="fas fa-images me-1"></i> Gallery
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'packages.php' ? 'active' : ''; ?>" href="packages.php">
                        <i class="fas fa-box me-1"></i> Packages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="users.php">
                        <i class="fas fa-users me-1"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'admins.php' ? 'active' : ''; ?>" href="admins.php">
                        <i class="fas fa-user-shield me-1"></i> Admins
                    </a>
                </li>

            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Dilni
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
@media (max-width: 991.98px) {
    .navbar-nav {
        padding: 1rem 0;
    }
    .navbar-nav .nav-item {
        margin: 0.25rem 0;
    }
    .navbar-nav .nav-link {
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
    }
    .navbar-nav .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    .navbar-nav .nav-link.active {
        background-color: rgba(255, 255, 255, 0.2);
    }
    .navbar-brand img {
        height: 35px;
    }
}
</style> 