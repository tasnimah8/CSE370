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

// Get camps managed by this volunteer
$volunteer_id = $_SESSION['user_id'];
$camps_query = "
    SELECT 
        rc.*,
        CASE 
            WHEN rc.current_occupancy >= rc.capacity THEN 'full'
            WHEN rc.current_occupancy >= (rc.capacity * 0.8) THEN 'almost-full'
            ELSE 'operational'
        END as camp_status
    FROM relief_camps rc
    WHERE rc.managed_by = ? 
    ORDER BY rc.camp_name";

$stmt = $conn->prepare($camps_query);
$stmt->bind_param("s", $volunteer_id);
$stmt->execute();
$camps = $stmt->get_result();

// Handle camp update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_camp'])) {
    $camp_id = $_POST['camp_id'];
    $current_occupancy = $_POST['current_occupancy'];

    $stmt = $conn->prepare("UPDATE relief_camps SET current_occupancy = ?, last_updated = NOW() WHERE camp_id = ?");
    $stmt->bind_param("is", $current_occupancy, $camp_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Camp information updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating camp information.";
    }
    header('Location: relief-camp-volunteer.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relief Camp Management - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .camp-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .camp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .camp-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .status-operational { background: #d1fae5; color: #065f46; }
        .status-full { background: #fee2e2; color: #991b1b; }
        .status-maintenance { background: #fef3c7; color: #92400e; }

        .camp-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item i {
            color: #4b5563;
            width: 20px;
        }

        .update-form {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .occupancy-meter {
            height: 10px;
            background: #e5e7eb;
            border-radius: 5px;
            margin: 10px 0;
            overflow: hidden;
        }

        .occupancy-fill {
            height: 100%;
            background: linear-gradient(90deg, #34d399 0%, #fbbf24 50%, #ef4444 100%);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="admin-container">
    <?php include 'includes/volunteer-navbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Relief Camp Management</h1>
        </div>

        <?php if ($camps->num_rows > 0): ?>
            <?php while ($camp = $camps->fetch_assoc()): ?>
                <div class="camp-card">
                    <div class="camp-header">
                        <h2><?php echo htmlspecialchars($camp['camp_name']); ?></h2>
                        <span class="camp-status status-<?php echo htmlspecialchars($camp['camp_status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($camp['camp_status'])); ?>
                        </span>
                    </div>

                    <div class="camp-info">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($camp['location']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-users"></i>
                            <span>Capacity: <?php echo htmlspecialchars($camp['capacity']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user-check"></i>
                            <span>Current Occupancy: <?php echo htmlspecialchars($camp['current_occupancy']); ?></span>
                        </div>
                    </div>

                    <div class="occupancy-meter">
                        <div class="occupancy-fill" style="width: <?php echo ($camp['current_occupancy'] / $camp['capacity']) * 100; ?>%"></div>
                    </div>

                    <form class="update-form" method="POST">
                        <input type="hidden" name="camp_id" value="<?php echo htmlspecialchars($camp['camp_id']); ?>">
                        <input type="hidden" name="update_camp" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Current Occupancy</label>
                                <input type="number" name="current_occupancy" value="<?php echo htmlspecialchars($camp['current_occupancy']); ?>" min="0" max="<?php echo htmlspecialchars($camp['capacity']); ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Occupancy</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data-message">
                <p>You are not currently managing any relief camps.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
