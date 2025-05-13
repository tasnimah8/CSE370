<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle task assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $conn->begin_transaction();
    try {
        // Generate IDs
        $distribution_task_id = 'TASK-' . time();
        $task_id = 'TSK-' . time();
        $volunteer_id = $_POST['volunteer_id'];
        $inventory_id = $_POST['inventory_id'];
        $victim_id = $_POST['victim_id'];
        $quantity = $_POST['quantity'];
        $location = $_POST['location'];

        // Insert into distribution_tasks
        $stmt = $conn->prepare("INSERT INTO distribution_tasks (task_id, volunteer_id, inventory_id, victim_id, quantity, location, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssss", $distribution_task_id, $volunteer_id, $inventory_id, $victim_id, $quantity, $location);
        $stmt->execute();

        // Get victim details for task description
        $victim_query = "SELECT name, priority FROM victims WHERE victim_id = ?";
        $stmt = $conn->prepare($victim_query);
        $stmt->bind_param("s", $victim_id);
        $stmt->execute();
        $victim = $stmt->get_result()->fetch_assoc();

        // Get inventory item details
        $item_query = "SELECT item_name FROM inventory WHERE inventory_id = ?";
        $stmt = $conn->prepare($item_query);
        $stmt->bind_param("s", $inventory_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();

        // Create task description
        $task_description = sprintf(
            "Distribute %d units of %s to %s at %s. Distribution Task ID: %s",
            $quantity,
            $item['item_name'],
            $victim['name'],
            $location,
            $distribution_task_id
        );

        // Insert into tasks table
        $stmt = $conn->prepare("INSERT INTO tasks (task_id, title, description, priority, due_date, location, assigned_to, status) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), ?, ?, 'pending')");
        $task_title = "Relief Distribution Task";
        $stmt->bind_param("ssssss", 
            $task_id, 
            $task_title,
            $task_description,
            $victim['priority'],
            $location,
            $volunteer_id
        );
        $stmt->execute();

        // Update volunteer availability
        $stmt = $conn->prepare("UPDATE volunteer_profiles SET is_available = 0 WHERE volunteer_id = ?");
        $stmt->bind_param("s", $volunteer_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success_message'] = "Task assigned successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error assigning task: " . $e->getMessage();
    }
    header('Location: assign-tasks.php');
    exit();
}

// Get all tasks with details
$tasks_query = "
    SELECT 
        t.*,
        CONCAT(v.first_name, ' ', v.last_name) as volunteer_name,
        vic.name as victim_name,
        i.item_name as relief_type,
        i.quantity as available_quantity
    FROM distribution_tasks t
    JOIN users v ON t.volunteer_id = v.user_id
    JOIN victims vic ON t.victim_id = vic.victim_id
    JOIN inventory i ON t.inventory_id = i.inventory_id
    ORDER BY t.assigned_date DESC";
$tasks = $conn->query($tasks_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Tasks - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="admin-container">
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Distribution Task Management</h1>
            <div class="header-actions">
                <button class="assign-task-button" onclick="openAssignModal()">
                    <i class="fas fa-plus"></i>
                    <span>Assign New Task</span>
                    <div class="notification-dot"></div>
                </button>
            </div>
        </div>

        <!-- Display Tasks -->
        <div class="table-container">
            <div class="table-header">
                <h2>Task List</h2>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Task ID</th>
                        <th>Volunteer</th>
                        <th>Victim</th>
                        <th>Relief Type</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Assigned Date</th>
                        <th>Completed Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($task = $tasks->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['task_id']); ?></td>
                        <td><?php echo htmlspecialchars($task['volunteer_name']); ?></td>
                        <td><?php echo htmlspecialchars($task['victim_name']); ?></td>
                        <td><?php echo htmlspecialchars($task['relief_type']); ?></td>
                        <td><?php echo htmlspecialchars($task['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($task['location']); ?></td>
                        <td>
                            <span class="badge <?php echo $task['status']; ?>">
                                <?php echo ucfirst($task['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($task['assigned_date'])); ?></td>
                        <td>
                            <?php echo $task['completion_date'] 
                                ? date('Y-m-d H:i', strtotime($task['completion_date'])) 
                                : '-'; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Task Assignment Modal -->
        <div id="assignTaskModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Assign Distribution Task</h3>
                    <span class="close" onclick="closeAssignModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="assign_task" value="1">
                        
                        <div class="form-group">
                            <label>Select Volunteer</label>
                            <select name="volunteer_id" required>
                                <option value="">Choose a volunteer</option>
                                <?php
                                $volunteers = $conn->query("SELECT user_id, first_name, last_name FROM users WHERE role = 'volunteer'");
                                while ($volunteer = $volunteers->fetch_assoc()):
                                ?>
                                <option value="<?php echo $volunteer['user_id']; ?>">
                                    <?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Select Item</label>
                            <select name="inventory_id" required>
                                <option value="">Choose an item</option>
                                <?php
                                $items = $conn->query("SELECT inventory_id, item_name, quantity FROM inventory WHERE quantity > 0");
                                while ($item = $items->fetch_assoc()):
                                ?>
                                <option value="<?php echo $item['inventory_id']; ?>">
                                    <?php echo htmlspecialchars($item['item_name'] . ' (Available: ' . $item['quantity'] . ')'); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Select Victim</label>
                            <select name="victim_id" required>
                                <option value="">Choose a victim</option>
                                <?php
                                $victims = $conn->query("SELECT victim_id, name, location FROM victims WHERE status = 'pending'");
                                while ($victim = $victims->fetch_assoc()):
                                ?>
                                <option value="<?php echo $victim['victim_id']; ?>">
                                    <?php echo htmlspecialchars($victim['name'] . ' (' . $victim['location'] . ')'); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" min="1" required>
                        </div>

                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" required>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Assign Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
        }

        .header-actions {
            margin-left: auto;
        }

        .assign-task-button {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.2);
        }

        .assign-task-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.3);
            background: linear-gradient(135deg, #27ae60, #219a52);
        }

        .assign-task-button i {
            font-size: 1.2rem;
        }

        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 10px;
            height: 10px;
            background-color: #e74c3c;
            border-radius: 50%;
            border: 2px solid white;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.5);
                opacity: 0.5;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Adjust modal styling for better presentation */
        .modal-content {
            max-width: 600px;
            width: 90%;
            margin: 5vh auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: #2ecc71;
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }
    </style>

    <script>
        function openAssignModal() {
            document.getElementById('assignTaskModal').style.display = 'block';
        }

        function closeAssignModal() {
            document.getElementById('assignTaskModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('assignTaskModal')) {
                closeAssignModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
