<?php
session_start();

// Check if user is logged in as volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get inventory statistics
$stats = [
    'total_items' => $conn->query("SELECT SUM(quantity) as count FROM inventory")->fetch_assoc()['count'] ?? 0,
    'available_items' => $conn->query("SELECT COUNT(*) as count FROM inventory WHERE quantity > 0")->fetch_assoc()['count'] ?? 0,
    'expiring_soon' => $conn->query("SELECT COUNT(*) as count FROM inventory WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) AND expiry_date > CURRENT_DATE")->fetch_assoc()['count'] ?? 0
];

// Get inventory items
$inventory_query = "
    SELECT 
        inventory_id,
        item_name,
        item_type as relief_type,
        quantity,
        expiry_date,
        CASE 
            WHEN quantity = 0 THEN 'out_of_stock'
            WHEN quantity < 10 THEN 'low'
            ELSE 'good'
        END as status
    FROM inventory 
    ORDER BY quantity ASC";
$inventory = $conn->query($inventory_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .badge.out_of_stock {
            background-color: #ff4757;
            color: white;
        }
        .badge.low {
            background-color: #ffa502;
            color: white;
        }
        .badge.good {
            background-color: #2ed573;
            color: white;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 10px;
        }
        .read-only-note {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .read-only-note i {
            font-size: 1.2em;
        }
    </style>
</head>
<body class="admin-container">
    <?php include 'includes/volunteer-navbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Relief Inventory</h1>
        </div>

        <div class="read-only-note">
            <i class="fas fa-info-circle"></i>
            <span>This is a read-only view of the inventory. Contact an administrator for any updates needed.</span>
        </div>

        <!-- Inventory Summary Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-box"></i>
                <div class="stat-info">
                    <h3>Total Items</h3>
                    <p><?php echo number_format($stats['total_items']); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-info">
                    <h3>Available Items</h3>
                    <p><?php echo number_format($stats['available_items']); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-info">
                    <h3>Expiring Soon</h3>
                    <p><?php echo number_format($stats['expiring_soon']); ?></p>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="table-container">
            <div class="table-header">
                <h2>Current Inventory</h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Relief Type</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $inventory->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['inventory_id']); ?></td>
                            <td><?php echo htmlspecialchars($item['relief_type']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><?php echo $item['expiry_date'] ? date('Y-m-d', strtotime($item['expiry_date'])) : 'N/A'; ?></td>
                            <td><span class="badge <?php echo $item['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                            </span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
