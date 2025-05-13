<?php
session_start();

// Check if user is logged in and is a donor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Update donor query to include statistics
$donor_query = "
    SELECT u.*, dp.*,
           COUNT(d.donation_id) as total_donations,
           SUM(CASE WHEN d.donation_type = 'monetary' THEN d.amount ELSE 0 END) as total_amount,
           MAX(d.donation_date) as last_donation_date
    FROM users u 
    LEFT JOIN donor_profiles dp ON u.user_id = dp.donor_id 
    LEFT JOIN donations d ON u.user_id = d.donor_id AND d.status = 'approved'
    WHERE u.user_id = ?
    GROUP BY u.user_id";
$stmt = $conn->prepare($donor_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$donor_info = $stmt->get_result()->fetch_assoc();

// Make sure this is at the top of the file, after database connection
if (!file_exists('utils/Logger.php')) {
    // Create simple error handling if Logger class doesn't exist
    function error_log_custom($message) {
        $log_file = __DIR__ . '/logs/error.log';
        if (!is_dir(dirname($log_file))) {
            mkdir(dirname($log_file), 0777, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] [ERROR] $message" . PHP_EOL;
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
} else {
    require_once 'utils/Logger.php';
}

// At the start of the file, after session_start()
if (!function_exists('generate_nonce')) {
    function generate_nonce() {
        if (!isset($_SESSION['donation_nonce'])) {
            $_SESSION['donation_nonce'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['donation_nonce'];
    }
}

// Modify the form submission handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    // Verify nonce
    if (!isset($_POST['donation_nonce']) || $_POST['donation_nonce'] !== $_SESSION['donation_nonce']) {
        $_SESSION['error_message'] = "Invalid form submission.";
        header("Location: donor-dashboard.php");
        exit();
    }

    // Reset nonce to prevent form resubmission
    unset($_SESSION['donation_nonce']);
    
    try {
        $donation_id = 'DON-' . time();
        $type = $_POST['donation_type'];
        $amount = $type === 'monetary' ? $_POST['amount'] : null;
        $items = $type !== 'monetary' ? $_POST['items'] : null;
        $quantity = $type !== 'monetary' ? $_POST['quantity'] : null;
        $payment_method = $type === 'monetary' ? $_POST['payment_method'] : null;
        $transaction_id = $type === 'monetary' ? $_POST['transaction_id'] : null;
        $date = date('Y-m-d H:i:s');

        $sql = "INSERT INTO donations (donation_id, donor_id, donation_type, amount, items, quantity, payment_method, transaction_id, donation_date, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdsisss", $donation_id, $user_id, $type, $amount, $items, $quantity, $payment_method, $transaction_id, $date);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Donation submitted successfully!";
        } else {
            throw new Exception("Error submitting donation");
        }
        
        header("Location: donor-dashboard.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: donor-dashboard.php");
        exit();
    }
}

// Fetch recent donations
$donations_query = "
    SELECT * FROM donations 
    WHERE donor_id = ?
    ORDER BY donation_date DESC 
    LIMIT 5";
$stmt = $conn->prepare($donations_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$recent_donations = $stmt->get_result();

// Fetch notifications (recent donations with status)
$notifications_query = "
    SELECT d.*, 
           CASE 
               WHEN d.status = 'approved' THEN 'Your donation has been approved'
               WHEN d.status = 'rejected' THEN 'Your donation has been rejected'
               ELSE 'Your donation is pending review'
           END as message
    FROM donations d
    WHERE d.donor_id = ?
    ORDER BY d.donation_date DESC
    LIMIT 3";
$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Additional styles for notifications */
        .notification-container {
            margin-top: 30px;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .notification-header h2 {
            margin: 0;
        }
        .notification-badge {
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-left: 8px;
        }
        .notification-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        .notification-item.approved {
            border-left-color: #2ed573;
        }
        .notification-item.rejected {
            border-left-color: #ff4757;
        }
        .notification-item.pending {
            border-left-color: #ffa502;
        }
        .notification-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .notification-title h3 {
            margin: 0;
            font-size: 16px;
        }
        .notification-time {
            color: #7f8c8d;
            font-size: 12px;
        }
        .notification-message {
            margin-bottom: 10px;
            color: #333;
        }
        .receipt-details {
            background: #f9f9f9;
            border-radius: 6px;
            padding: 12px;
            margin-top: 10px;
            display: none;
        }
        .receipt-details.show {
            display: block;
        }
        .receipt-row {
            display: flex;
            margin-bottom: 8px;
        }
        .receipt-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .receipt-value {
            flex: 1;
        }
        .toggle-receipt {
            color: #3498db;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
            margin-top: 5px;
        }
        .toggle-receipt:hover {
            text-decoration: underline;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        /* Volunteer Hero Section */
        .volunteer-hero {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .volunteer-id {
            font-size: 14px;
            color: #666;
        }
        .profile-stats {
            margin-top: 20px;
            display: flex;
            gap: 0;
            background: #f2f4f6;
            border-radius: 8px;
            padding: 10px;
        }
        .stat-item {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 10px 20px;
            border-right: 1px solid #e0e0e0;
        }
        .stat-item:last-child {
            border-right: none;
        }
        .stat-item i {
            color: #8AB2A6;
            font-size: 20px;
            margin-right: 12px;
        }
        .stat-item div {
            display: flex;
            flex-direction: column;
        }
        .stat-item span {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }
        .stat-item strong {
            font-size: 14px;
            color: #333;
        }
        @media (max-width: 768px) {
            .profile-stats {
                flex-direction: column;
            }
            .stat-item {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
            .stat-item:last-child {
                border-bottom: none;
            }
        }
        /* Form Styles */
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .form-header h2 {
            margin: 0;
            color: #3E3F5B;
        }
        .form-header i {
            margin-right: 10px;
            color: #8AB2A6;
        }
        .report-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .report-form.compact {
            gap: 15px;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #3E3F5B;
            font-weight: 500;
        }
        .form-group select, 
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-secondary {
            background-color: #f1f1f1;
            color: #3E3F5B;
        }
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        .btn-primary {
            background-color: #8AB2A6;
            color: white;
        }
        .btn-primary:hover {
            background-color: #7aa396;
        }
        .text-right {
            text-align: right;
        }
        /* Hide all conditional fields by default */
        .conditional-field { display: none; }
        /* Show money fields if money radio is checked */
        #donationType-money:checked ~ .conditional-fields .money-fields { display: flex; }
        /* Show item fields if food, clothing, medical, or other is checked */
        #donationType-food:checked ~ .conditional-fields .item-fields,
        #donationType-clothing:checked ~ .conditional-fields .item-fields,
        #donationType-medical:checked ~ .conditional-fields .item-fields,
        #donationType-other:checked ~ .conditional-fields .item-fields { display: flex; }
        /* Style for form-row */
        .form-row { display: flex; gap: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
    </style>
</head>
<body class="admin-container">
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-hands-helping"></i>
            <h1>HelpHub</h1>
        </div>
        <div class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> <span>Home</span></a>
            <a href="donor-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
            <a href="distribution-repo.php"><i class="fas fa-box-open"></i> <span>Distribution Repo</span></a>
            <div class="admin-profile">
                <img src="<?php echo $donor_info['profile_image'] ? htmlspecialchars($donor_info['profile_image']) : 'https://randomuser.me/api/portraits/men/32.jpg'; ?>" alt="Donor">
                <div>
                    <p><?php echo htmlspecialchars($donor_info['first_name'] . ' ' . $donor_info['last_name']); ?></p>
                    <small>Donor</small>
                </div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>
    <!-- Main Content -->
    <main class="main-content">
        <!-- Donor Hero Section -->
        <section class="volunteer-hero">
            <div class="volunteer-profile">
                <div class="profile-header">
                    <div>
                        <h2><?php echo htmlspecialchars($donor_info['first_name'] . ' ' . $donor_info['last_name']); ?></h2>
                        <p class="volunteer-id">Donor ID: <?php echo htmlspecialchars($donor_info['user_id']); ?></p>
                    </div>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <span>Phone No</span>
                            <strong><?php echo htmlspecialchars($donor_info['phone']); ?></strong>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <span>Email</span>
                            <strong><?php echo htmlspecialchars($donor_info['email']); ?></strong>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-hand-holding-usd"></i>
                        <div>
                            <span>Total Donations</span>
                            <strong><?php echo $donor_info['total_donations'] ? number_format($donor_info['total_donations']) . ' Contributions' : 'No donations yet'; ?></strong>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <div>
                            <span>Total Amount</span>
                            <strong>$<?php echo number_format($donor_info['total_amount'] ?? 0, 2); ?></strong>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <span>Last Donation</span>
                            <strong><?php echo $donor_info['last_donation_date'] ? date('M d, Y', strtotime($donor_info['last_donation_date'])) : 'No donations yet'; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Add New Donation Section -->
        <section class="table-container">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Donation</h2>
            </div>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            <form id="donationForm" class="report-form compact" method="POST" action="donor-dashboard.php" style="position:relative;">
                <input type="hidden" name="donation_nonce" value="<?php echo generate_nonce(); ?>">
                <input type="hidden" name="submit_donation" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Donation Type *</label>
                        <div style="display:flex; gap:10px;">
                            <input type="radio" id="donationType-money" name="donation_type" value="monetary" required>
                            <label for="donationType-money" style="margin-right:10px;">Money</label>
                            <input type="radio" id="donationType-food" name="donation_type" value="food">
                            <label for="donationType-food" style="margin-right:10px;">Food</label>
                            <input type="radio" id="donationType-clothing" name="donation_type" value="clothing">
                            <label for="donationType-clothing" style="margin-right:10px;">Clothing</label>
                            <input type="radio" id="donationType-medical" name="donation_type" value="medical">
                            <label for="donationType-medical" style="margin-right:10px;">Medicine</label>
                            <input type="radio" id="donationType-other" name="donation_type" value="other">
                            <label for="donationType-other">Other</label>
                        </div>
                    </div>
                </div>
                <!-- Conditional fields using CSS only -->
                <div class="conditional-fields">
                    <div class="form-row money-fields conditional-field" style="display:none;">
                        <div class="form-group">
                            <label for="amount">Amount ($) *</label>
                            <input type="number" id="amount" name="amount" placeholder="Amount in dollars" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label for="paymentMethod">Payment Method *</label>
                            <select id="paymentMethod" name="payment_method">
                                <option value="">Select payment method</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="mobile">Mobile Payment</option>
                                <option value="credit">Credit Card</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transactionId">Transaction ID *</label>
                            <input type="text" id="transactionId" name="transaction_id" placeholder="Enter transaction ID">
                        </div>
                    </div>
                    <div class="form-row item-fields conditional-field" style="display:none;">
                        <div class="form-group">
                            <label for="items">Items Description *</label>
                            <textarea id="items" name="items" placeholder="Describe the items you're donating"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="quantity">Quantity *</label>
                            <input type="number" id="quantity" name="quantity" min="1">
                        </div>
                    </div>
                </div>
                <div class="form-actions text-right">
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                    <button type="submit" class="btn btn-primary">Submit Donation</button>
                </div>
            </form>
        </section>
        
        <!-- Notification Section -->
        <section class="table-container notification-container">
            <div class="notification-header">
                <h2><i class="fas fa-bell"></i> Notifications</h2>
            </div>
            
            <?php while ($notification = $notifications->fetch_assoc()): ?>
            <div class="notification-item <?php echo $notification['status']; ?>">
                <div class="notification-title">
                    <div>
                        <h3>
                            Donation <?php echo ucfirst($notification['status']); ?>
                            <span class="status-badge status-<?php echo $notification['status']; ?>">
                                <?php echo ucfirst($notification['status']); ?>
                            </span>
                        </h3>
                        <div class="notification-time"><?php echo time_elapsed_string($notification['donation_date']); ?></div>
                    </div>
                </div>
                <div class="notification-message">
                    <?php echo htmlspecialchars($notification['message']); ?>
                    <!-- Add a real link for View Details -->
                    <a href="view-donation.php?id=<?php echo urlencode($notification['donation_id']); ?>" style="color:#3498db; text-decoration:underline; margin-left:8px;">
                        View Details
                    </a>
                </div>
                <div class="receipt-details" id="receipt<?php echo $notification['donation_id']; ?>">
                    <div class="receipt-row">
                        <div class="receipt-label">Donation Type:</div>
                        <div class="receipt-value"><?php echo ucfirst($notification['donation_type']); ?></div>
                    </div>
                    <?php if ($notification['donation_type'] === 'monetary'): ?>
                    <div class="receipt-row">
                        <div class="receipt-label">Amount:</div>
                        <div class="receipt-value">$<?php echo number_format($notification['amount'], 2); ?></div>
                    </div>
                    <div class="receipt-row">
                        <div class="receipt-label">Payment Method:</div>
                        <div class="receipt-value"><?php echo ucfirst($notification['payment_method']); ?></div>
                    </div>
                    <div class="receipt-row">
                        <div class="receipt-label">Transaction ID:</div>
                        <div class="receipt-value"><?php echo htmlspecialchars($notification['transaction_id']); ?></div>
                    </div>
                    <?php else: ?>
                    <div class="receipt-row">
                        <div class="receipt-label">Items:</div>
                        <div class="receipt-value"><?php echo htmlspecialchars($notification['items']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="receipt-row">
                        <div class="receipt-label">Date:</div>
                        <div class="receipt-value"><?php echo date('M d, Y', strtotime($notification['donation_date'])); ?></div>
                    </div>
                    <div class="receipt-row">
                        <div class="receipt-label">Status:</div>
                        <div class="receipt-value"><?php echo ucfirst($notification['status']); ?></div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            <?php if ($notifications->num_rows === 0): ?>
            <div class="notification-item pending">
                <div class="notification-title">
                    <div>
                        <h3>No Notifications</h3>
                        <div class="notification-message">
                            You don't have any notifications yet.
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateFields() {
            var type = document.querySelector('input[name="donation_type"]:checked');
            var moneyFields = document.querySelector('.money-fields');
            var itemFields = document.querySelector('.item-fields');
            if (!type) {
                moneyFields.style.display = 'none';
                itemFields.style.display = 'none';
                return;
            }
            if (type.value === 'monetary') {
                moneyFields.style.display = 'flex';
                itemFields.style.display = 'none';
                document.getElementById('amount').required = true;
                document.getElementById('paymentMethod').required = true;
                document.getElementById('transactionId').required = true;
                document.getElementById('items').required = false;
                document.getElementById('quantity').required = false;
            } else {
                moneyFields.style.display = 'none';
                itemFields.style.display = 'flex';
                document.getElementById('amount').required = false;
                document.getElementById('paymentMethod').required = false;
                document.getElementById('transactionId').required = false;
                document.getElementById('items').required = true;
                document.getElementById('quantity').required = true;
            }
        }
        var radios = document.querySelectorAll('input[name="donation_type"]');
        radios.forEach(function(radio) {
            radio.addEventListener('change', updateFields);
        });
        updateFields(); // Initial call
    });
    </script>
</body>
</html>

<?php
// Helper function to display time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

$conn->close();
?>