<?php 
if (!isset($_SESSION)) {
    session_start();
}

// Get volunteer info from database if not already in session
if (!isset($_SESSION['first_name']) && isset($_SESSION['user_id'])) {
    $conn = new mysqli('localhost', 'root', '', 'floodguard');
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT first_name, last_name, profile_image FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['profile_image'] = $row['profile_image'];
        }
        $conn->close();
    }
}
?>
<nav class="navbar">
    <div class="logo">
        <i class="fas fa-hands-helping"></i>
        <h1>Floodguard Volunteer</h1>
    </div>
    <ul class="nav-links">
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="volunteer-dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'volunteer-dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a></li>
        <li><a href="volunteer-inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'volunteer-inventory.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Inventory
        </a></li>
        <li><a href="volunteer-victims.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'volunteer-victims.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Victims
        </a></li>
        <li><a href="relief-camp-volunteer.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'relief-camp-volunteer.php' ? 'active' : ''; ?>">
            <i class="fas fa-campground"></i> Update Camp
        </a></li>
        <li><a href="volunteer-distribution.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'volunteer-distribution.php' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i> Distribution Repo
        </a></li>
        <li>
            <div class="admin-profile">
                <img src="<?php echo isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : 'images/profile-user.png'; ?>" alt="Profile">
                <span><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) : 'Volunteer'; ?></span>
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

    .nav-links li a:hover {
        background: #34495e;
    }

    .nav-links li a.active {
        background: #3498db;
    }

    .admin-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
    }

    .admin-profile img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
    }
</style>
