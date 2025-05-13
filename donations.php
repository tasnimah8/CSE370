<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get donation statistics
$stats = [
    'total_donations' => $conn->query("SELECT COUNT(*) as count FROM donations")->fetch_assoc()['count'] ?? 0,
    'approved_donations' => $conn->query("SELECT COUNT(*) as count FROM donations WHERE status = 'approved'")->fetch_assoc()['count'] ?? 0,
    'pending_donations' => $conn->query("SELECT COUNT(*) as count FROM donations WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0,
    'rejected_donations' => $conn->query("SELECT COUNT(*) as count FROM donations WHERE status = 'rejected'")->fetch_assoc()['count'] ?? 0
];

// Get pending donations
$pending_donations_query = "
    SELECT d.*, u.first_name, u.last_name
    FROM donations d
    JOIN users u ON d.donor_id = u.user_id
    WHERE d.status = 'pending'
    ORDER BY d.donation_date DESC";
$pending_donations = $conn->query($pending_donations_query);

// Get donation history
$donation_history_query = "
    SELECT d.*, u.first_name, u.last_name
    FROM donations d
    JOIN users u ON d.donor_id = u.user_id
    WHERE d.status != 'pending'
    ORDER BY d.donation_date DESC";
$donation_history = $conn->query($donation_history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Copy all styles from donations.html */
        .modal-toggle:checked + .modal {
            display: block;
        }

        // ...existing styles from donations.html...
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="admin-container">
        <main class="main-content">
            <section class="donations-section">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-donate"></i></div>
                        <div class="stat-info">
                            <h3>Total Donations</h3>
                            <p><?php echo number_format($stats['total_donations']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3>Approved</h3>
                            <p><?php echo number_format($stats['approved_donations']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <h3>Pending</h3>
                            <p><?php echo number_format($stats['pending_donations']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-info">
                            <h3>Rejected</h3>
                            <p><?php echo number_format($stats['rejected_donations']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Pending Donations Table -->
                <div class="tab-content" id="pendingDonations">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                          
                            </thead>
                            <tbody>
                                <?php while ($donation = $pending_donations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($donation['donation_id']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['first_name'] . ' ' . $donation['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['donation_type']); ?></td>
                                    <td>
                                        <?php 
                                        echo $donation['donation_type'] === 'monetary' 
                                            ? '$' . number_format($donation['amount'], 2) 
                                            : htmlspecialchars($donation['items']); 
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($donation['donation_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($donation['payment_method'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($donation['transaction_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $donation['status']; ?>">
                                            <?php echo ucfirst($donation['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <form method="post" action="approve_donation.php" style="display: inline;">
                                            <input type="hidden" name="donation_id" value="<?php echo $donation['donation_id']; ?>">
                                            <button type="submit" name="action" value="approve" class="action-btn approve-btn" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="post" action="reject_donation.php" style="display: inline;">
                                            <input type="hidden" name="donation_id" value="<?php echo $donation['donation_id']; ?>">
                                            <button type="submit" name="action" value="reject" class="action-btn reject-btn" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Donation History Table -->
                <div class="tab-content" id="donationHistory">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Donation ID</th>
                                    <th>Donor Name</th>
                                    <th>Type</th>
                                    <th>Amount/Items</th>
                                    <th>Date Received</th>
                                    <th>Payment Method</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($donation = $donation_history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($donation['donation_id']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['first_name'] . ' ' . $donation['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['donation_type']); ?></td>
                                    <td>
                                        <?php 
                                        echo $donation['donation_type'] === 'monetary' 
                                            ? '$' . number_format($donation['amount'], 2) 
                                            : htmlspecialchars($donation['items']); 
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($donation['donation_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($donation['payment_method'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($donation['transaction_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $donation['status']; ?>">
                                            <?php echo ucfirst($donation['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Keep existing modal and style code -->


</body>
</html>
<?php $conn->close(); ?>
