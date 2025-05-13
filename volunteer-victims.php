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

// Handle form submission for new victim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $victim_id = 'VIC-' . time();
    $name = $_POST['victimName'];
    $family_size = $_POST['familySize'];
    $phone = $_POST['phoneNumber'];
    $location = $_POST['location'];
    $priority = $_POST['priority'];
    $needs = $_POST['needs'];

    $stmt = $conn->prepare("INSERT INTO victims (victim_id, name, location, priority, needs, phone, family_size) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $victim_id, $name, $location, $priority, $needs, $phone, $family_size);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Victim registered successfully.";
    } else {
        $_SESSION['error_message'] = "Error registering victim.";
    }
    header('Location: volunteer-victims.php');
    exit();
}

// Fetch victims list
$victims_query = "
    SELECT 
        victim_id,
        name,
        family_size,
        phone,
        location,
        priority,
        needs,
        registration_date
    FROM victims 
    ORDER BY registration_date DESC";
$victims = $conn->query($victims_query);
$total_victims = $victims->num_rows;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Victims - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-secondary {
            background: #f1f1f1;
            color: #333;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge.high {
            background: #fee2e2;
            color: #dc2626;
        }

        .badge.medium {
            background: #fef3c7;
            color: #d97706;
        }

        .badge.low {
            background: #d1fae5;
            color: #059669;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #34d399;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #f87171;
        }
    </style>
</head>
<body class="admin-container">
    <?php include 'includes/volunteer-navbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Victim Management</h1>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add New Victim Form -->
        <div class="form-container">
            <h2><i class="fas fa-user-plus"></i> Register New Victim</h2>
            <form id="victimForm" method="POST" action="volunteer-victims.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="victimName">Full Name</label>
                        <input type="text" id="victimName" name="victimName" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="familySize">Family Size</label>
                        <input type="number" id="familySize" name="familySize" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phoneNumber">Contact Number</label>
                        <input type="tel" id="phoneNumber" name="phoneNumber" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priority Level</label>
                        <select id="priority" name="priority" required>
                            <option value="">Select priority</option>
                            <option value="high">High (Immediate)</option>
                            <option value="medium">Medium (24hrs)</option>
                            <option value="low">Low (Stable)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="needs">Needs</label>
                        <input type="text" id="needs" name="needs" placeholder="e.g., Food, Water, Medicine" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">Clear</button>
                        <button type="submit" class="btn btn-primary">Save Victim</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Victims List Table -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Registered Victims</h2>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Victim ID</th>
                        <th>Name</th>
                        <th>Family Size</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Needs</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($victim = $victims->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($victim['victim_id']); ?></td>
                        <td><?php echo htmlspecialchars($victim['name']); ?></td>
                        <td><?php echo htmlspecialchars($victim['family_size']); ?></td>
                        <td><?php echo htmlspecialchars($victim['location']); ?></td>
                        <td><span class="badge <?php echo htmlspecialchars($victim['priority']); ?>"><?php echo ucfirst(htmlspecialchars($victim['priority'])); ?></span></td>
                        <td><?php echo htmlspecialchars($victim['needs']); ?></td>
                        <td><?php echo htmlspecialchars($victim['phone']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div class="table-footer">
                <div class="table-summary">
                    <p>Total victims: <strong><?php echo $total_victims; ?></strong></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
