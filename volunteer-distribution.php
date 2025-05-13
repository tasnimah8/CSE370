<?php
session_start();

// Check if user is logged in as volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get distribution records with details - filtered for current volunteer
$volunteer_id = $_SESSION['user_id'];
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
    WHERE d.volunteer_id = ?
    ORDER BY d.distribution_date DESC";

$stmt = $conn->prepare($distribution_query);
$stmt->bind_param("s", $volunteer_id);
$stmt->execute();
$distributions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Distributions - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .distribution-summary {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-card {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .summary-card i {
            font-size: 24px;
            color: #3498db;
            margin-bottom: 10px;
        }

        .summary-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-card p {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body class="admin-container">
    <?php include 'includes/volunteer-navbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>My Distribution History</h1>
        </div>

        <!-- Distribution Summary -->
        <div class="distribution-summary">
            <?php
            $total_distributions = $distributions->num_rows;
            $total_items = 0;
            $total_victims = [];
            $locations = [];

            // Calculate summaries
            if ($total_distributions > 0) {
                $distributions_copy = $distributions->fetch_all(MYSQLI_ASSOC);
                foreach ($distributions_copy as $dist) {
                    $total_items += $dist['quantity'];
                    $total_victims[$dist['victim_name']] = 1;
                    $locations[$dist['location']] = 1;
                }
                // Reset pointer for main loop
                $distributions->data_seek(0);
            }
            ?>
            <div class="summary-card">
                <i class="fas fa-boxes"></i>
                <h3>Total Distributions</h3>
                <p><?php echo $total_distributions; ?></p>
            </div>
            <div class="summary-card">
                <i class="fas fa-box-open"></i>
                <h3>Items Distributed</h3>
                <p><?php echo $total_items; ?></p>
            </div>
            <div class="summary-card">
                <i class="fas fa-users"></i>
                <h3>Victims Helped</h3>
                <p><?php echo count($total_victims); ?></p>
            </div>
            <div class="summary-card">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Locations Covered</h3>
                <p><?php echo count($locations); ?></p>
            </div>
        </div>
        
        <!-- Distribution History Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Distribution ID</th>
                        <th>Date</th>
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
                                <td><?php echo date('Y-m-d H:i', strtotime($row['distribution_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['victim_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['relief_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No distribution records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
