
<nav class="navbar">
    <div class="logo">
        <i class="fas fa-hands-helping"></i>
        <h1>Floodguard Admin</h1>
    </div>
    <ul class="nav-links">
        <li><a href="index.html"><i class="fas fa-home"></i> Home</a></li>
        <li <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php') ? 'class="active"' : ''; ?>>
            <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </li>
        <li><a href="volunteers.php"><i class="fas fa-users"></i> Volunteers</a></li>
        <li><a href="inventory.php"><i class="fas fa-box-open"></i> Inventory</a></li>
        <li><a href="donations.php"><i class="fas fa-donate"></i> Donations</a></li>
        <li>
            <a href="assign-tasks.php" class="assign-task-btn">
                <i class="fas fa-plus-circle"></i> Assign New Task
                <span class="pulse-dot"></span>
            </a>
        </li>
        <li><a href="distribution-repo.php"><i class="fas fa-box-open"></i> Distribution Repo</a></li>
        <li>
            <div class="admin-profile">
                <img src="profile-user.png" alt="Admin Profile">
            </div>
        </li>
        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
    </ul>
</nav>
