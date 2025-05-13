<?php if (!isset($_SESSION)) session_start(); ?>
<nav class="navbar">
    <div class="logo">
        <i class="fas fa-hands-helping"></i>
        <h1>Floodguard Donor</h1>
    </div>
    <ul class="nav-links">
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="donor-dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'donor-dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a></li>
        <li><a href="distribution-repo.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'distribution-repo.php' ? 'active' : ''; ?>">
            <i class="fas fa-box-open"></i> Distribution Repo
        </a></li>
        <li>
            <div class="admin-profile">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : 'images/profile-user.png'; ?>" alt="Profile">
                    <div>
                        <p><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : 'Donor'; ?></p>
                        <small>Donor</small>
                    </div>
                <?php endif; ?>
            </div>
        </li>
        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
    </ul>
</nav>

<style>
    .navbar {
        background: #2c3e50;
        padding: 15px 30px;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 20px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .nav-links li a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 15px;
        border-radius: 4px;
        transition: background 0.3s;
    }

    .nav-links li a:hover,
    .nav-links li a.active {
        background: #34495e;
    }

    .admin-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
        padding: 0 15px;
    }

    .admin-profile img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
    }

    .admin-profile div p {
        margin: 0;
        font-size: 14px;
    }

    .admin-profile div small {
        color: #95a5a6;
    }

    .logout-btn {
        color: #e74c3c !important;
    }

    .logout-btn:hover {
        background: rgba(231, 76, 60, 0.1) !important;
    }
</style>
