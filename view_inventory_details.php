<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get inventory item details
if (isset($_GET['id'])) {
    $inventory_id = $_GET['id'];
    $stmt = $conn->prepare("
        SELECT i.*, 
               d.donor_id, 
               CONCAT(u.first_name, ' ', u.last_name) as donor_name,
               d.donation_date
        FROM inventory i
        LEFT JOIN donations d ON i.source_donation_id = d.donation_id
        LEFT JOIN users u ON d.donor_id = u.user_id
        WHERE i.inventory_id = ?
    ");
    $stmt->bind_param("s", $inventory_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    // Get distribution history
    $dist_stmt = $conn->prepare("
        SELECT dr.*, rc.camp_name, rc.location
        FROM distribution_records dr
        LEFT JOIN relief_camps rc ON dr.camp_id = rc.camp_id
        WHERE dr.inventory_id = ?
        ORDER BY dr.distribution_date DESC
    ");
    $dist_stmt->bind_param("s", $inventory_id);
    $dist_stmt->execute();
    $distributions = $dist_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Details - Floodguard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .details-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .item-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-group {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-group label {
            display: block;
            color: #666;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        .info-group span {
            font-size: 1.1em;
            color: #333;
            font-weight: 500;
        }

        .distribution-history {
            margin-top: 30px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: 500;
        }

        .back-btn {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="admin-container">
        <main class="main-content">
            <a href="inventory.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Inventory
            </a>

            <?php if (isset($item)): ?>
            <div class="details-container">
                <div class="item-header">
                    <h2><?php echo htmlspecialchars($item['item_name']); ?></h2>
                    <span class="badge <?php echo $item['quantity'] > 0 ? 'available' : 'not-available'; ?>">
                        <?php echo $item['quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                </div>

                <div class="item-info">
                    <div class="info-group">
                        <label>Inventory ID</label>
                        <span><?php echo htmlspecialchars($item['inventory_id']); ?></span>
                    </div>
                    <div class="info-group">
                        <label>Item Type</label>
                        <span><?php echo htmlspecialchars($item['item_type']); ?></span>
                    </div>
                    <div class="info-group">
                        <label>Quantity</label>
                        <span><?php echo htmlspecialchars($item['quantity'] . ' ' . $item['unit']); ?></span>
                    </div>
                    <div class="info-group">
                        <label>Expiry Date</label>
                        <span><?php echo $item['expiry_date'] ? date('M d, Y', strtotime($item['expiry_date'])) : 'N/A'; ?></span>
                    </div>
                    <?php if ($item['donor_name']): ?>
                    <div class="info-group">
                        <label>Donor</label>
                        <span><?php echo htmlspecialchars($item['donor_name']); ?></span>
                    </div>
                    <div class="info-group">
                        <label>Donation Date</label>
                        <span><?php echo date('M d, Y', strtotime($item['donation_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="distribution-history">
                    <h3>Distribution History</h3>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Quantity</th>
                                <th>Relief Camp</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($dist = $distributions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($dist['distribution_date'])); ?></td>
                                <td><?php echo htmlspecialchars($dist['quantity'] . ' ' . $item['unit']); ?></td>
                                <td><?php echo htmlspecialchars($dist['camp_name']); ?></td>
                                <td><?php echo htmlspecialchars($dist['location']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($distributions->num_rows === 0): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No distribution records found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="details-container">
                <p>Item not found.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
