<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle camp updates if admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    if (isset($_POST['update_camp'])) {
        $camp_id = $_POST['camp_id'];
        $capacity = $_POST['capacity'];
        $location = $_POST['location'];
        $managed_by = $_POST['managed_by'];
        
        $stmt = $conn->prepare("UPDATE relief_camps SET capacity = ?, location = ?, managed_by = ? WHERE camp_id = ?");
        $stmt->bind_param("isss", $capacity, $location, $managed_by, $camp_id);
        $stmt->execute();
    }
}

// Fetch all relief camps
$camps_query = "
    SELECT rc.*, u.first_name, u.last_name 
    FROM relief_camps rc
    LEFT JOIN users u ON rc.managed_by = u.user_id
    ORDER BY rc.location";
$camps = $conn->query($camps_query);

// Fetch available volunteers for admin assignment
if ($_SESSION['role'] === 'admin') {
    $volunteers_query = "SELECT user_id, first_name, last_name FROM users WHERE role = 'volunteer'";
    $volunteers = $conn->query($volunteers_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Relief Camps Management - Floodguard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .relief-camps {
            margin-top: 20px;
            padding: 20px;
        }

        .camps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .camp-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .camp-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .camp-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .camp-header h3 {
            color: #3E3F5B;
            margin: 0;
            font-size: 1.2rem;
        }

        .camp-info {
            margin-bottom: 20px;
        }

        .camp-info p {
            color: #666;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .camp-info i {
            color: #8AB2A6;
        }

        .occupancy-bar {
            width: 100%;
            height: 10px;
            background: #eee;
            border-radius: 5px;
            margin: 15px 0;
            overflow: hidden;
        }

        .occupancy-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s ease;
        }

        .full { background: linear-gradient(to right, #ff4757, #ff6b81); }
        .almost-full { background: linear-gradient(to right, #ffa502, #ffd700); }
        .available { background: linear-gradient(to right, #2ed573, #7bed9f); }

        .camp-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .stat-item strong {
            display: block;
            color: #3E3F5B;
            margin-bottom: 5px;
        }

        .stat-item p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .edit-btn { 
            background: #3498db;
            color: white;
        }

        .edit-btn:hover {
            background: #2980b9;
        }

        .assign-btn { 
            background: #8AB2A6;
            color: white;
        }

        .assign-btn:hover {
            background: #7aa396;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .section-header h2 {
            color: #3E3F5B;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h2 i {
            color: #8AB2A6;
        }

        .btn-primary {
            background: #8AB2A6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #7aa396;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #3E3F5B;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #8AB2A6;
            outline: none;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.full {
            background-color: #fee2e2;
            color: #ef4444;
        }

        .badge.almost-full {
            background-color: #fef3c7;
            color: #d97706;
        }

        .badge.available {
            background-color: #d1fae5;
            color: #059669;
        }

        /* Add these icon styles */
        .action-btn i {
            font-size: 16px;
            margin-right: 5px;
        }

        .action-btn.edit-btn { 
            background: #3498db;
            color: white;
            padding: 8px 15px;
            width: auto;
            height: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn.assign-btn { 
            background: #8AB2A6;
            color: white;
            padding: 8px 15px;
            width: auto;
            height: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary i {
            font-size: 16px;
            margin-right: 5px;
        }

        .camp-info i {
            font-size: 18px;
            color: #8AB2A6;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .section-header h2 i {
            font-size: 24px;
            color: #8AB2A6;
            margin-right: 10px;
        }

        /* Update badge styles for better visibility */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge i {
            font-size: 14px;
        }

        /* Fix navbar icon visibility */
        .nav-links i {
            font-size: 18px;
            width: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .logo i {
            font-size: 24px;
            margin-right: 10px;
            color: white;
        }

        /* Add Button Styles */
        .add-btn-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #8AB2A6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .add-btn-floating:hover {
            transform: scale(1.1);
            background: #7aa396;
        }

        .add-btn-floating i {
            font-size: 24px;
        }

        .add-camp-form {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 500px;
            z-index: 1001;
        }

        .form-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .edit-form-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            z-index: 1001;
            width: 90%;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <!-- Navbar (same as admin-dashboard.php) -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-hands-helping"></i>
            <h1>Floodguard Network</h1>
        </div>
        <ul class="nav-links">
            <li><a href="index.html"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="<?php echo $_SESSION['role']; ?>-dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            <li class="active"><a href="relief-camps.php">
                <i class="fas fa-campground"></i> Relief Camps
            </a></li>
            <!-- Add other navigation items -->
        </ul>
    </nav>

    <div class="admin-container">
        <main class="main-content">
            <section class="relief-camps">
                <div class="section-header">
                    <h2><i class="fas fa-campground"></i> Relief Camps Management</h2>
                </div>

                <!-- Add floating button -->
                <button class="add-btn-floating" onclick="toggleAddForm()">
                    <i class="fas fa-plus"></i>
                </button>

                <!-- Add form overlay -->
                <div class="form-overlay" onclick="hideAllForms()"></div>

                <!-- Move the form here -->
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <form method="POST" action="add_camp.php" class="add-camp-form">
                    <div class="form-group">
                        <label for="camp_name">Camp Name *</label>
                        <input type="text" id="camp_name" name="camp_name" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    <div class="form-group">
                        <label for="capacity">Capacity *</label>
                        <input type="number" id="capacity" name="capacity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="managed_by">Assign Manager</label>
                        <select id="managed_by" name="managed_by">
                            <option value="">Select Volunteer</option>
                            <?php while ($volunteer = $volunteers->fetch_assoc()): ?>
                            <option value="<?php echo $volunteer['user_id']; ?>">
                                <?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create New Camp</button>
                </form>
                <?php endif; ?>

                <!-- Add edit form -->
                <div class="edit-form-container">
                    <h3>Edit Camp Details</h3>
                    <form method="POST" action="update_camp.php" id="editCampForm">
                        <input type="hidden" name="camp_id" id="edit_camp_id">
                        <div class="form-group">
                            <label for="edit_camp_name">Camp Name *</label>
                            <input type="text" id="edit_camp_name" name="camp_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_location">Location *</label>
                            <input type="text" id="edit_location" name="location" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_capacity">Capacity *</label>
                            <input type="number" id="edit_capacity" name="capacity" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_managed_by">Assign Manager</label>
                            <select id="edit_managed_by" name="managed_by">
                                <option value="">Select Volunteer</option>
                                <?php 
                                $volunteers->data_seek(0);
                                while ($volunteer = $volunteers->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $volunteer['user_id']; ?>">
                                    <?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="hideAllForms()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Camp</button>
                        </div>
                    </form>
                </div>

                <div class="camps-grid">
                    <?php while ($camp = $camps->fetch_assoc()): ?>
                        <?php 
                        $occupancy_percentage = ($camp['current_occupancy'] / $camp['capacity']) * 100;
                        $status_class = $occupancy_percentage >= 90 ? 'full' : 
                                     ($occupancy_percentage >= 70 ? 'almost-full' : 'available');
                        ?>
                        <div class="camp-card" data-camp-id="<?php echo $camp['camp_id']; ?>">
                            <div class="camp-header">
                                <h3><?php echo htmlspecialchars($camp['camp_name']); ?></h3>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo $camp['current_occupancy']; ?>/<?php echo $camp['capacity']; ?>
                                </span>
                            </div>

                            <div class="camp-info">
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($camp['location']); ?></p>
                                <div class="occupancy-bar">
                                    <div class="occupancy-fill <?php echo $status_class; ?>" 
                                         style="width: <?php echo $occupancy_percentage; ?>%">
                                    </div>
                                </div>
                            </div>

                            <div class="camp-stats">
                                <div class="stat-item">
                                    <strong>Manager:</strong>
                                    <p data-manager-id="<?php echo $camp['managed_by']; ?>"><?php echo $camp['first_name'] ? 
                                        htmlspecialchars($camp['first_name'] . ' ' . $camp['last_name']) : 
                                        'Unassigned'; ?></p>
                                </div>
                                <div class="stat-item">
                                    <strong>Last Updated:</strong>
                                    <p><?php echo date('M d, Y H:i', strtotime($camp['last_updated'])); ?></p>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button class="action-btn edit-btn" onclick="editCamp('<?php echo $camp['camp_id']; ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn assign-btn" onclick="assignManager('<?php echo $camp['camp_id']; ?>')">
                                    <i class="fas fa-user-plus"></i> Assign Manager
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        </main>
    </div>

    <style>
        .add-camp-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .add-camp-form .form-group {
            margin-bottom: 15px;
        }
        
        .add-camp-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .add-camp-form input,
        .add-camp-form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .add-camp-form button {
            margin-top: 10px;
        }
    </style>

    <script>
        // Add JavaScript for handling modals and camp management
        function toggleAddForm() {
            const form = document.querySelector('.add-camp-form');
            const overlay = document.querySelector('.form-overlay');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
            overlay.style.display = overlay.style.display === 'none' ? 'block' : 'none';
        }

        function hideAllForms() {
            document.querySelector('.add-camp-form').style.display = 'none';
            document.querySelector('.edit-form-container').style.display = 'none';
            document.querySelector('.form-overlay').style.display = 'none';
        }

        function editCamp(campId) {
            // Get camp data from the card
            const campCard = document.querySelector(`[data-camp-id="${campId}"]`);
            const campName = campCard.querySelector('.camp-header h3').textContent;
            const location = campCard.querySelector('.camp-info p').textContent.trim();
            const capacity = campCard.querySelector('.badge').textContent.split('/')[1];
            const managedBy = campCard.querySelector('[data-manager-id]')?.dataset.managerId || '';

            // Populate edit form
            document.getElementById('edit_camp_id').value = campId;
            document.getElementById('edit_camp_name').value = campName;
            document.getElementById('edit_location').value = location;
            document.getElementById('edit_capacity').value = capacity;
            document.getElementById('edit_managed_by').value = managedBy;

            // Show edit form and overlay
            document.querySelector('.edit-form-container').style.display = 'block';
            document.querySelector('.form-overlay').style.display = 'block';
        }
    </script>
</body>
</html>
