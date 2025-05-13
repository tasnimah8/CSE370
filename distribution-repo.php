<?php
session_start();

// Check if user is logged in as admin or donor
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'donor'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get distribution records with distribution details
$distribution_query = "
    SELECT 
        d.distribution_id,
        d.distribution_date,
        CONCAT(v.first_name, ' ', v.last_name) as volunteer_name,
        vic.name as victim_name,
        i.item_name as relief_type,
        d.location,
        d.quantity
    FROM distribution_records d
    JOIN users v ON d.volunteer_id = v.user_id
    JOIN victims vic ON d.victim_id = vic.victim_id
    JOIN inventory i ON d.inventory_id = i.inventory_id
    ORDER BY d.distribution_date DESC";

$distributions = $conn->query($distribution_query);

// Add body class based on user role
$bodyClass = $_SESSION['role'] === 'donor' ? 'donor-view' : 'admin-container';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribution Repository - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="<?php echo $bodyClass; ?>">
    <!-- Include appropriate navbar based on role -->
    <?php 
    if ($_SESSION['role'] === 'admin') {
        include 'includes/navbar.php';
    } else {
        include 'includes/donor-navbar.php';
    }
    ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>Distribution Repository</h1>
        </div>
        
        <!-- Distribution History Section -->
        <div class="table-container">
            <div class="table-header">
                <h2>Distribution History</h2>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Distribution ID</th>
                        <th>Date</th>
                        <th>Volunteer</th>
                        <th>Victim</th>
                        <th>Relief Type</th>
                        <th>Location</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($distributions->num_rows > 0): ?>
                        <?php while ($row = $distributions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['distribution_id']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['distribution_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['volunteer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['victim_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['relief_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No distribution records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>