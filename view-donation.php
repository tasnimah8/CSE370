<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['id'])) {
    echo "No donation selected.";
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$donation_id = $_GET['id'];
$stmt = $conn->prepare("SELECT d.*, u.first_name, u.last_name FROM donations d JOIN users u ON d.donor_id = u.user_id WHERE d.donation_id = ?");
$stmt->bind_param("s", $donation_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Donation not found.";
    exit();
}
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donation Receipt</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Arial', sans-serif;
            padding: 40px;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px dashed #e0e0e0;
            margin-bottom: 30px;
        }
        .receipt-header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 28px;
        }
        .receipt-header p {
            color: #7f8c8d;
            margin: 10px 0;
        }
        .receipt-logo {
            font-size: 40px;
            color: #8AB2A6;
            margin-bottom: 15px;
        }
        .receipt-body {
            margin-bottom: 30px;
        }
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-group {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-group h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .info-group p {
            margin: 5px 0;
            color: #34495e;
        }
        .donation-details {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .donation-details th {
            background: #8AB2A6;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .donation-details td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-rejected {
            background: #fee2e2;
            color: #b91c1c;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px dashed #e0e0e0;
            color: #7f8c8d;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #8AB2A6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: #7aa396;
        }
        @media print {
            .back-btn {
                display: none;
            }
            body {
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="receipt-logo">
                <i class="fas fa-hands-helping"></i>
            </div>
            <h1>Donation Receipt</h1>
            <p>FloodGuard Network</p>
            <p><?php echo date('F d, Y'); ?></p>
        </div>
        
        <div class="receipt-body">
            <div class="receipt-info">
                <div class="info-group">
                    <h3>Donor Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></p>
                    <p><strong>Donation ID:</strong> <?php echo htmlspecialchars($row['donation_id']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($row['donation_date'])); ?></p>
                </div>
                <div class="info-group">
                    <h3>Donation Status</h3>
                    <p><strong>Status:</strong> 
                        <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                        </span>
                    </p>
                    <?php if ($row['approved_by']): ?>
                    <p><strong>Approved By:</strong> <?php echo htmlspecialchars($row['approved_by']); ?></p>
                    <p><strong>Approved Date:</strong> <?php echo date('F d, Y', strtotime($row['approved_at'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <table class="donation-details">
                <tr>
                    <th>Description</th>
                    <th>Details</th>
                </tr>
                <tr>
                    <td>Donation Type</td>
                    <td><?php echo ucfirst(htmlspecialchars($row['donation_type'])); ?></td>
                </tr>
                <?php if ($row['donation_type'] === 'monetary'): ?>
                <tr>
                    <td>Amount</td>
                    <td>$<?php echo number_format($row['amount'], 2); ?></td>
                </tr>
                <tr>
                    <td>Payment Method</td>
                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                </tr>
                <tr>
                    <td>Transaction ID</td>
                    <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td>Items</td>
                    <td><?php echo htmlspecialchars($row['items']); ?></td>
                </tr>
                <tr>
                    <td>Quantity</td>
                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="receipt-footer">
            <p>Thank you for your generous donation!</p>
            <p>For any queries, please contact our support team.</p>
            <a href="donor-dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
