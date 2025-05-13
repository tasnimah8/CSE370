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

// Handle approve/reject POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donation_id'], $_POST['action'])) {
    $donation_id = $_POST['donation_id'];
    $action = $_POST['action'];
    $admin_id = $_SESSION['user_id'];

    $conn->begin_transaction();
    try {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE donations SET status = ?, approved_by = ?, approved_at = NOW() WHERE donation_id = ?");
        $stmt->bind_param("sss", $status, $admin_id, $donation_id);
        $stmt->execute();

        if ($action === 'approve') {
            $donation = $conn->query("SELECT * FROM donations WHERE donation_id = '$donation_id'")->fetch_assoc();
            if ($donation['donation_type'] !== 'monetary') {
                $inventory_id = 'INV-' . time();
                $stmt = $conn->prepare("INSERT INTO inventory (inventory_id, item_type, quantity, item_name, source_donation_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiss", $inventory_id, $donation['donation_type'], $donation['quantity'], $donation['items'], $donation_id);
                $stmt->execute();
            }
        }
        $conn->commit();
        $message = "Donation status updated successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// Get dashboard statistics
$stats = [
    'volunteers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'volunteer'")->fetch_assoc()['count'],
    'donors' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'donor'")->fetch_assoc()['count'],
    'total_donations' => $conn->query("SELECT SUM(amount) as total FROM donations WHERE status = 'approved'")->fetch_assoc()['total'],
    'distributions' => $conn->query("SELECT SUM(quantity) as total FROM distribution_records")->fetch_assoc()['total'],
    'locations' => $conn->query("SELECT COUNT(DISTINCT location) as count FROM victims")->fetch_assoc()['count']
];

// Get recent donations
$donations_query = "
    SELECT d.donation_id, u.first_name, u.last_name, d.donation_type, 
           d.amount, d.items, d.donation_date, d.status
    FROM donations d
    JOIN users u ON d.donor_id = u.user_id
    ORDER BY d.donation_date DESC
    LIMIT 5
";

$pending_donations_query = "
    SELECT d.*, u.first_name, u.last_name 
    FROM donations d 
    JOIN users u ON d.donor_id = u.user_id 
    WHERE d.status = 'pending' 
    ORDER BY d.donation_date DESC";

$donations_result = $conn->query($donations_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Add these new styles */
        .actions {
            display: flex;
            gap: 5px;
            align-items: center;
            justify-content: flex-start;
        }

        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            color: white;
        }

        .view-btn {
            background-color: #3498db;
        }

        .approve-btn {
            background-color: #2ed573;
        }

        .reject-btn {
            background-color: #ff4757;  /* Changed to red */
        }

        form[method="POST"] {
            display: inline-flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-hands-helping"></i>
            <h1>Floodguard Admin</h1>
        </div>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li class="active"><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="volunteers.php"><i class="fas fa-users"></i> Volunteers</a></li>
            <li><a href="relief-camps.php"><i class="fas fa-campground"></i> Relief Camps</a></li>
            <li><a href="inventory.php"><i class="fas fa-box-open"></i> Inventory</a></li>
            <li><a href="donations.php"><i class="fas fa-donate"></i> Donations</a></li>
            <li><a href="distribution-repo.php"><i class="fas fa-box-open"></i> Distribution Repo</a></li>
            <li>
                <div class="admin-profile">
                    <img src="profile-user.png" alt="Admin Profile">
                </div>
            </li>
            <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="admin-container">
        <main class="main-content">
            <!-- Stats Overview Section -->
            <section class="stats-section">
                <h2>Dashboard Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Volunteers</h3>
                            <p><?php echo number_format($stats['volunteers']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Donors</h3>
                            <p><?php echo number_format($stats['donors']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-donate"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Donations</h3>
                            <p>$<?php echo number_format($stats['total_donations'], 2); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Resources Distributed</h3>
                            <p><?php echo number_format($stats['distributions']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Affected Locations</h3>
                            <p><?php echo number_format($stats['locations']); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Donations Management Section -->
            <section class="donations-section">
                <div class="section-header">
                    <h2>Recent Donations</h2>
                    <div class="section-actions">
                        <button class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                </div>
                <?php if (isset($message)): ?>
                    <div class="alert" style="margin-bottom: 10px;"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Donation ID</th>
                                <th>Donor Name</th>
                                <th>Type</th>
                                <th>Amount/Items</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $donations_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['donation_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['donation_type']); ?></td>
                                <td><?php echo $row['donation_type'] === 'monetary' ? '$' . number_format($row['amount'], 2) : htmlspecialchars($row['items']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['donation_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="action-btn view-btn" title="View Details" onclick="window.location.href='view-donation.php?id=<?php echo $row['donation_id']; ?>'">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($row['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline-flex; gap:5px;">
                                        <input type="hidden" name="donation_id" value="<?php echo htmlspecialchars($row['donation_id']); ?>">
                                        <button type="submit" name="action" value="approve" class="action-btn approve-btn" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="submit" name="action" value="reject" class="action-btn reject-btn" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Task Modal and Scripts -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Task to Volunteer</h3>
                <span class="close-btn" onclick="closeTaskModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="taskForm">
                    <div class="form-group">
                        <label for="volunteerName">Volunteer Name</label>
                        <input type="text" id="volunteerName" value="John Smith" readonly>
                    </div>
                    <div class="form-group">
                        <label for="taskDescription">Task Description</label>
                        <textarea id="taskDescription" rows="3" placeholder="Enter task details"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="taskLocation">Location</label>
                        <select id="taskLocation" onchange="loadVictimsByLocation()">
                            <option value="">Select Location</option>
                            <option value="downtown">Downtown Area</option>
                            <option value="riverside">Riverside</option>
                            <option value="north">North District</option>
                            <option value="eastside">Eastside</option>
                            <option value="west">West District</option>
                        </select>
                    </div>
                    <div class="form-group" id="victimSelectionGroup" style="display: none;">
                        <label for="victimSelection">Select Victim to Assist</label>
                        <select id="victimSelection">
                            <option value="">Loading victims...</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeTaskModal()">Cancel</button>
                <button class="btn btn-primary" onclick="assignTask()">Assign Task</button>
            </div>
        </div>
    </div>

    <script>
        // Task Assignment Modal Functions
        function openTaskModal() {
            document.getElementById('taskModal').style.display = 'block';
        }

        function closeTaskModal() {
            document.getElementById('taskModal').style.display = 'none';
            // Reset form
            document.getElementById('taskForm').reset();
            document.getElementById('victimSelectionGroup').style.display = 'none';
        }
        
        // Load victims by location
        function loadVictimsByLocation() {
            const location = document.getElementById('taskLocation').value;
            const victimSelectionGroup = document.getElementById('victimSelectionGroup');
            const victimSelection = document.getElementById('victimSelection');
            
            if (!location) {
                victimSelectionGroup.style.display = 'none';
                return;
            }
            
            // Show the victim selection dropdown
            victimSelectionGroup.style.display = 'block';
            
            // Clear previous options
            victimSelection.innerHTML = '<option value="">Loading...</option>';
            
            // Simulated data - in a real app, this would be fetched from the server based on location
            const victimsByLocation = {
                'downtown': [
                    { id: 'VIC-001', name: 'Maria Rahman', priority: 'High', reliefNeeded: 'Food, Medicine' },
                    { id: 'VIC-002', name: 'Abdul Karim', priority: 'Medium', reliefNeeded: 'Shelter' },
                    { id: 'VIC-003', name: 'Fatema Begum', priority: 'High', reliefNeeded: 'Medical Aid' }
                ],
                'riverside': [
                    { id: 'VIC-004', name: 'Ismail Hossain', priority: 'High', reliefNeeded: 'Water, Food' },
                    { id: 'VIC-005', name: 'Rahima Khatun', priority: 'Medium', reliefNeeded: 'Clothing' }
                ],
                'north': [
                    { id: 'VIC-006', name: 'Mohammad Ali', priority: 'Low', reliefNeeded: 'Food' },
                    { id: 'VIC-007', name: 'Shahida Akter', priority: 'High', reliefNeeded: 'Medicine, Shelter' }
                ],
                'eastside': [
                    { id: 'VIC-008', name: 'Kamal Ahmed', priority: 'Medium', reliefNeeded: 'Food, Clothing' },
                    { id: 'VIC-009', name: 'Nasima Begum', priority: 'High', reliefNeeded: 'Medicine' }
                ],
                'west': [
                    { id: 'VIC-010', name: 'Rafiq Islam', priority: 'Low', reliefNeeded: 'Clothing' },
                    { id: 'VIC-011', name: 'Hasina Akter', priority: 'Medium', reliefNeeded: 'Food' }
                ]
            };
            
            // Get victims for the selected location
            const victims = victimsByLocation[location] || [];
            
            // Clear the dropdown
            victimSelection.innerHTML = '';
            
            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select a victim';
            victimSelection.appendChild(defaultOption);
            
            // Add victims to dropdown
            victims.forEach(victim => {
                const option = document.createElement('option');
                option.value = victim.id;
                option.textContent = `${victim.name} - ${victim.priority} Priority - Needs: ${victim.reliefNeeded}`;
                option.dataset.priority = victim.priority;
                option.dataset.needs = victim.reliefNeeded;
                victimSelection.appendChild(option);
            });
            
            // If no victims found
            if (victims.length === 0) {
                const noVictimsOption = document.createElement('option');
                noVictimsOption.value = '';
                noVictimsOption.textContent = 'No victims found in this location';
                victimSelection.appendChild(noVictimsOption);
            }
        }

        function assignTask() {
            const location = document.getElementById('taskLocation').value;
            const victimSelect = document.getElementById('victimSelection');
            const victim = victimSelect.value;
            
            if (!location) {
                alert('Please select a location');
                return;
            }
            
            if (!victim) {
                alert('Please select a victim to assist');
                return;
            }
            
            // Get the selected victim's priority from the data attribute
            const selectedOption = victimSelect.options[victimSelect.selectedIndex];
            const priority = selectedOption.dataset.priority;
            
            // Here you would normally send the task data to the server including the priority
            alert(`Task assigned successfully with ${priority} priority!`);
            closeTaskModal();
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('taskModal');
            if (event.target == modal) {
                closeTaskModal();
            }
        }

        function viewDonation(donationId) {
            // Add view donation functionality
            window.location.href = `view-donation.php?id=${donationId}`;
        }

        function updateDonationStatus(donationId, status) {
            if (confirm(`Are you sure you want to ${status} this donation?`)) {
                fetch(`update-donation-status.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `donation_id=${donationId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating donation status');
                    }
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>