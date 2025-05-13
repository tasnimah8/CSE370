<?php
session_start();

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "floodguard";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add this update profile logic after database connection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['skill_type'])) {
    // Update volunteer profile
    $skill_type = $_POST['skill_type'];
    $sql = "UPDATE volunteer_profiles SET skill_type = ? WHERE volunteer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $skill_type, $user_id);
    if ($stmt->execute()) {
        // Refresh the page to show updated data
        header("Location: volunteer-dashboard.php");
        exit();
    }
}

// Get volunteer information
$user_id = $_SESSION['user_id'];
$volunteer_info = [];
$tasks = [];

// Fetch user and volunteer profile data
$sql = "SELECT u.*, v.* FROM users u 
        JOIN volunteer_profiles v ON u.user_id = v.volunteer_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $volunteer_info = $result->fetch_assoc();
} else {
    die("Volunteer not found");
}

// Fetch assigned tasks
$sql = "SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed' ORDER BY priority DESC, due_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
}

// Handle availability toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_availability'])) {
    $new_availability = $volunteer_info['is_available'] ? 0 : 1;
    $sql = "UPDATE volunteer_profiles SET is_available = ? WHERE volunteer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $new_availability, $user_id);
    $stmt->execute();
    
    // Refresh volunteer info
    $volunteer_info['is_available'] = $new_availability;
}

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_task'])) {
    $task_id = $_POST['task_id'];
    
    $conn->begin_transaction();
    try {
        // Get task and distribution task details
        $sql = "SELECT t.*, dt.* FROM tasks t 
                JOIN distribution_tasks dt ON dt.task_id = SUBSTRING_INDEX(t.description, 'Distribution Task ID: ', -1)
                WHERE t.task_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $task_id);
        $stmt->execute();
        $task_details = $stmt->get_result()->fetch_assoc();
        
        // Create distribution record
        $distribution_id = 'DIST-' . time();
        $sql = "INSERT INTO distribution_records 
                (distribution_id, volunteer_id, victim_id, inventory_id, distribution_date, quantity, location) 
                VALUES (?, ?, ?, ?, NOW(), ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", 
            $distribution_id,
            $task_details['volunteer_id'],
            $task_details['victim_id'],
            $task_details['inventory_id'],
            $task_details['quantity'],
            $task_details['location']
        );
        $stmt->execute();

        // Update tasks table
        $sql = "UPDATE tasks SET status = 'completed', completed_at = NOW() 
                WHERE task_id = ? AND assigned_to = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $task_id, $user_id);
        $stmt->execute();
        
        // Update distribution_tasks table
        $sql = "UPDATE distribution_tasks SET status = 'completed', completion_date = NOW() 
                WHERE task_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $task_details['task_id']);
        $stmt->execute();
        
        // Update inventory quantity
        $sql = "UPDATE inventory SET quantity = quantity - ? 
                WHERE inventory_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", 
            $task_details['quantity'], 
            $task_details['inventory_id']
        );
        $stmt->execute();
        
        // Update volunteer availability and count
        $sql = "UPDATE volunteer_profiles 
                SET is_available = 1, people_helped = people_helped + 1 
                WHERE volunteer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Task completed and distribution recorded successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error completing task: " . $e->getMessage();
    }
    
    header("Location: volunteer-dashboard.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/volunteer-navbar.php'; ?>
    
    <div class="admin-container">
        <main class="main-content">
            <!-- Volunteer Hero Section -->
            <section class="volunteer-hero glass">
                <div class="volunteer-profile">
                    <div class="profile-header">
                        <img src="<?php echo htmlspecialchars($volunteer_info['profile_image'] ?? 'https://randomuser.me/api/portraits/men/32.jpg'); ?>" alt="Volunteer" class="profile-img">
                        <div>
                            <h2>
                                <?php echo htmlspecialchars($volunteer_info['first_name'] . ' ' . $volunteer_info['last_name']); ?>
                                <button onclick="openEditModal()" class="btn-edit" title="Edit Profile">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </h2>
                            <p class="volunteer-id">Volunteer ID: <?php echo htmlspecialchars($volunteer_info['volunteer_id']); ?></p>
                            <!-- Repositioned availability toggle switch -->
                            <form method="POST" class="availability-toggle" id="availabilityForm">
                                <div class="toggle-container">
                                    <span class="toggle-label">Availability:</span>
                                    <label class="switch">
                                        <input type="checkbox" id="availabilityToggle" name="toggle_availability" 
                                            <?php echo $volunteer_info['is_available'] ? 'checked' : ''; ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <span id="availabilityStatus" class="toggle-status <?php echo $volunteer_info['is_available'] ? 'available' : 'unavailable'; ?>">
                                    <?php echo $volunteer_info['is_available'] ? 'Available' : 'Not Available'; ?>
                                </span>
                            </form>
                        </div>
                    </div>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <span>Phone No</span>
                                <strong><?php echo htmlspecialchars($volunteer_info['phone']); ?></strong>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <span>Email</span>
                                <strong><?php echo htmlspecialchars($volunteer_info['email']); ?></strong>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-tools"></i>
                            <div>
                                <span>Skill</span>
                                <strong><?php echo htmlspecialchars($volunteer_info['skill_type'] ?? 'General Volunteer'); ?></strong>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-hand-holding-heart"></i>
                            <div>
                                <span>Total Helped</span>
                                <strong><?php echo htmlspecialchars($volunteer_info['people_helped'] ?? '0'); ?> People</strong>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-clipboard-list"></i>
                            <div>
                                <span>Total Assigned Tasks</span>
                                <strong><?php echo count($tasks); ?> Tasks</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Assigned Tasks Section -->
            <section class="assigned-tasks">
                <div class="section-header">
                    <h2><i class="fas fa-tasks"></i> Assigned Tasks</h2>
                </div>

                <div class="tasks-container">
                    <?php if (empty($tasks)): ?>
                        <div class="no-tasks">
                            <p>You currently have no assigned tasks.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="task-card <?php echo htmlspecialchars($task['priority']); ?>-priority">
                                <div class="task-header">
                                    <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                                    <span class="badge <?php echo htmlspecialchars($task['priority']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($task['priority'])); ?> Priority
                                    </span>
                                </div>
                                <div class="task-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar-day"></i>
                                        <span><?php echo date('l, F j, Y', strtotime($task['due_date'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($task['location']); ?></span>
                                    </div>
                                </div>
                                <div class="task-description">
                                    <p><?php echo htmlspecialchars($task['description']); ?></p>
                                </div>
                                <div class="task-actions">
                                    <form method="POST">
                                        <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['task_id']); ?>">
                                        <button type="submit" name="complete_task" class="btn btn-primary">
                                            <i class="fas fa-check"></i> Task Complete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Add Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" method="POST" action="volunteer-dashboard.php">
                    <div class="form-group">
                        <label for="edit_first_name">First Name</label>
                        <input type="text" id="edit_first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($volunteer_info['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_last_name">Last Name</label>
                        <input type="text" id="edit_last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($volunteer_info['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" 
                               value="<?php echo htmlspecialchars($volunteer_info['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_skills">Skills</label>
                        <input type="text" id="edit_skills" name="skill_type" 
                               value="<?php echo htmlspecialchars($volunteer_info['skill_type']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="tel" id="edit_phone" name="phone" 
                               value="<?php echo htmlspecialchars($volunteer_info['phone']); ?>" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .availability-toggle {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-top: 10px;
        }
        
        .toggle-container {
            display: flex;
            align-items: center;
        }
        
        .toggle-label {
            margin-right: 10px;
            font-weight: 500;
        }
        
        .toggle-status {
            margin-top: 5px;
            font-weight: 500;
        }
        
        .toggle-status.available {
            color: #2ed573;
        }
        
        .toggle-status.unavailable {
            color: #ff4757;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: #2ed573;
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px #2ed573;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 24px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
        
        .no-tasks {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 20px 0;
        }

        /* Add these styles */
        .btn-edit {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 0.9em;
            padding: 5px;
            margin-left: 10px;
        }
        
        .btn-edit:hover {
            color: #2980b9;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 10px;
        }
        
        .close-btn {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-btn:hover,
        .close-btn:focus {
            color: black;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid #e5e5e5;
            padding-top: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-secondary {
            background-color: #ccc;
            color: #333;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
            margin-left: 10px;
        }
    </style>

    <script>
        // Availability toggle functionality with AJAX
        document.getElementById('availabilityToggle').addEventListener('change', function() {
            const statusElement = document.getElementById('availabilityStatus');
            const isAvailable = this.checked;
            
            fetch('update_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'toggle_availability=' + (isAvailable ? '1' : '0')
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusElement.textContent = isAvailable ? 'Available' : 'Not Available';
                    statusElement.className = 'toggle-status ' + (isAvailable ? 'available' : 'unavailable');
                } else {
                    alert('Error updating availability: ' + (data.message || 'Unknown error'));
                    this.checked = !this.checked;
                    statusElement.textContent = this.checked ? 'Available' : 'Not Available';
                    statusElement.className = 'toggle-status ' + (this.checked ? 'available' : 'unavailable');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating availability');
                this.checked = !this.checked;
                statusElement.textContent = this.checked ? 'Available' : 'Not Available';
                statusElement.className = 'toggle-status ' + (this.checked ? 'available' : 'unavailable');
            });
        });

        // Add these functions
        function openEditModal() {
            document.getElementById('editProfileModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>