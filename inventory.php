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

// Handle new inventory item addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $inventory_id = 'INV-' . time();
    $item_name = $_POST['item_name'];
    $item_type = $_POST['item_category'];
    $quantity = $_POST['quantity'];
    $item_description = $_POST['item_description'] ?? '';
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $status = 'available';

    $stmt = $conn->prepare("INSERT INTO inventory (inventory_id, item_type, quantity, item_name, item_description, added_date, status, expiry_date) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
    $stmt->bind_param("ssissss", $inventory_id, $item_type, $quantity, $item_name, $item_description, $status, $expiry_date);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Item added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding item.";
    }
    header('Location: inventory.php');
    exit();
}

// Handle item deletion
if (isset($_POST['delete_item'])) {
    $inventory_id = $_POST['inventory_id'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE inventory_id = ?");
    $stmt->bind_param("s", $inventory_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Item deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting item.";
    }
    header('Location: inventory.php');
    exit();
}

// Get inventory statistics
$stats = [
    'total_items' => $conn->query("SELECT COUNT(DISTINCT item_name) as total FROM inventory")->fetch_assoc()['total'] ?? 0,
    'available_items' => $conn->query("SELECT COUNT(*) as available FROM inventory WHERE status = 'available'")->fetch_assoc()['available'] ?? 0,
    'expiring_soon' => $conn->query("SELECT COUNT(*) as count FROM inventory WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) AND expiry_date >= CURRENT_DATE")->fetch_assoc()['count'] ?? 0,
];

// Get inventory items
$inventory_query = "
    SELECT *,
    CASE 
        WHEN expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) AND expiry_date >= CURRENT_DATE THEN 'expiring'
        WHEN expiry_date < CURRENT_DATE THEN 'expired'
        ELSE 'good'
    END as expiry_status
    FROM inventory
    ORDER BY added_date DESC";
$inventory_items = $conn->query($inventory_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Floodguard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="admin-container">
        <main class="main-content">
            <!-- Inventory Management Section -->
            <section class="inventory-section">
                <div class="section-header">
                    <h2>Relief Inventory Management</h2>
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                    <?php endif; ?>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="openInventoryModal()"><i class="fas fa-plus"></i> Add Item</button>
                        <button class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                </div>
                
                <!-- Inventory Summary Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Items</h3>
                            <p><?php echo number_format($stats['total_items']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Available Items</h3>
                            <p><?php echo number_format($stats['available_items']); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Expiring Soon</h3>
                            <p><?php echo number_format($stats['expiring_soon']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item ID</th>
                                <th>Item Name</th>
                                <th>Relief Type</th>
                                <th>Quantity</th>
                                <th>Description</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $inventory_items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['inventory_id']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_type']); ?></td>
                                <td><?php echo number_format($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                                <td>
                                    <?php if ($item['expiry_date']): ?>
                                        <span class="<?php echo strtotime($item['expiry_date']) < time() ? 'expired' : 
                                            (strtotime($item['expiry_date']) < strtotime('+30 days') ? 'expiring-soon' : ''); ?>">
                                            <?php echo date('Y-m-d', strtotime($item['expiry_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="action-btn edit-btn" onclick="openEditModal('<?php echo $item['inventory_id']; ?>', 
                                        '<?php echo htmlspecialchars($item['item_name']); ?>', 
                                        '<?php echo htmlspecialchars($item['item_type']); ?>', 
                                        '<?php echo $item['quantity']; ?>', 
                                        '<?php echo htmlspecialchars($item['item_description']); ?>', 
                                        '<?php echo $item['expiry_date']; ?>')" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                        <input type="hidden" name="inventory_id" value="<?php echo $item['inventory_id']; ?>">
                                        <button type="submit" name="delete_item" class="action-btn delete-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Add Inventory Item Modal -->
    <div id="inventoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Inventory Item</h3>
                <span class="close-btn" onclick="closeInventoryModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="inventoryForm" method="POST" action="inventory.php">
                    <input type="hidden" name="add_item" value="1">
                    <div class="form-group">
                        <label for="item_name">Item Name *</label>
                        <input type="text" id="item_name" name="item_name" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="item_category">Item Type *</label>
                            <select id="item_category" name="item_category" required>
                                <option value="">Select Item Type</option>
                                <option value="food">Food</option>
                                <option value="water">Water</option>
                                <option value="medical">Medical</option>
                                <option value="clothing">Clothing</option>
                                <option value="hygiene">Hygiene</option>
                                <option value="shelter">Shelter</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity">Quantity *</label>
                            <input type="number" id="quantity" name="quantity" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="item_description">Item Description</label>
                        <textarea id="item_description" name="item_description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               onchange="checkExpiryDate(this)">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeInventoryModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Edit Inventory Item Modal -->
    <div id="editInventoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Inventory Item</h3>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editInventoryForm" method="POST" action="update_inventory.php">
                    <input type="hidden" name="inventory_id" id="edit_inventory_id">
                    <div class="form-group">
                        <label for="edit_item_name">Item Name *</label>
                        <input type="text" id="edit_item_name" name="item_name" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_item_category">Item Type *</label>
                            <select id="edit_item_category" name="item_category" required>
                                <option value="">Select Item Type</option>
                                <option value="food">Food</option>
                                <option value="water">Water</option>
                                <option value="medical">Medical</option>
                                <option value="clothing">Clothing</option>
                                <option value="hygiene">Hygiene</option>
                                <option value="shelter">Shelter</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_quantity">Quantity *</label>
                            <input type="number" id="edit_quantity" name="quantity" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_item_description">Item Description</label>
                        <textarea id="edit_item_description" name="item_description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_expiry_date">Expiry Date</label>
                        <input type="date" id="edit_expiry_date" name="expiry_date">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openInventoryModal() {
            document.getElementById('inventoryModal').style.display = 'block';
        }

        function closeInventoryModal() {
            document.getElementById('inventoryModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('inventoryModal');
            if (event.target == modal) {
                closeInventoryModal();
            }
        }

        function checkExpiryDate(input) {
            const selectedDate = new Date(input.value);
            const today = new Date();
            
            if (selectedDate < today) {
                alert("Expiry date cannot be in the past!");
                input.value = '';
            }
        }

        function openEditModal(id, name, type, quantity, description, expiry) {
            document.getElementById('edit_inventory_id').value = id;
            document.getElementById('edit_item_name').value = name;
            document.getElementById('edit_item_category').value = type;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_item_description').value = description;
            document.getElementById('edit_expiry_date').value = expiry;
            document.getElementById('editInventoryModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editInventoryModal').style.display = 'none';
        }
    </script>

    <!-- Add this CSS to your existing styles -->
    <style>
        .expired {
            color: #dc3545;
            font-weight: bold;
        }
        
        .expiring-soon {
            color: #ffc107;
            font-weight: bold;
        }
        
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .delete-btn:hover {
            background-color: #c82333;
        }

        .actions form {
            display: inline-block;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .edit-btn:hover {
            background-color: #2980b9;
        }
    </style>

</body>
</html>
<?php $conn->close(); ?>
