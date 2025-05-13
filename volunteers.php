<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch volunteers with their profiles
$volunteers_query = "
    SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone,
           vp.is_available, vp.skill_type, vp.location, vp.people_helped
    FROM users u
    LEFT JOIN volunteer_profiles vp ON u.user_id = vp.volunteer_id
    WHERE u.role = 'volunteer'
    ORDER BY u.first_name";
$volunteers_result = $conn->query($volunteers_query);

// Fetch victims for task assignment
$victims_query = "SELECT victim_id, name, location, priority, needs FROM victims WHERE status = 'pending'";
$victims_result = $conn->query($victims_query);
$victims_by_location = [];
while ($victim = $victims_result->fetch_assoc()) {
    $victims_by_location[$victim['location']][] = $victim;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteers - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-hands-helping"></i>
            <h1>Floodguard Admin</h1>
        </div>
        <ul class="nav-links">
            <li><a href="index.html"> Home</a></li>
            <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="active"><a href="volunteers.html"><i class="fas fa-users"></i> Volunteers</a></li>
            <li><a href="inventory.html"><i class="fas fa-box-open"></i> Inventory</a></li>
            <li><a href="donations.html"><i class="fas fa-donate"></i> Donations</a></li>
            <li><a href="distribution-repo.html"><i class="fas fa-box-open"></i> Distribution Repo</a></li>
            <li>
                <div class="admin-profile">
                    <img src="profile-user.png" alt="Admin Profile">
                </div>
            </li>
            <li><a href="#" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="admin-container">
        <main class="main-content">
            <section class="volunteers-section">
                <div class="section-header">
                    <h2>Volunteer Management</h2>
                    <div class="section-actions">
                        <button class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Volunteer ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Availability</th>
                                <th>Skills</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $volunteers_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['is_available'] ? 'available' : 'not-available'; ?>">
                                        <?php echo $row['is_available'] ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['skill_type'] ?? 'Not specified'); ?></td>
                                <td><?php echo htmlspecialchars($row['location'] ?? 'Not specified'); ?></td>
                                <td class="actions">
                                    <button class="action-btn chat-btn" title="Chat" 
                                            onclick="openChat('<?php echo $row['user_id']; ?>', '<?php echo $row['first_name'] . ' ' . $row['last_name']; ?>')">
                                        <i class="fas fa-comment-dots"></i>
                                    </button>
                                    <button class="action-btn assign-btn" title="Assign Task" 
                                            onclick="openTaskModal('<?php echo $row['user_id']; ?>', 
                                                                 '<?php echo addslashes($row['first_name'] . ' ' . $row['last_name']); ?>')">
                                        <i class="fas fa-tasks"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Task Assignment Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Task to Volunteer</h3>
                <span class="close" onclick="closeTaskModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="taskForm" method="POST" action="assign_task.php">
                    <input type="hidden" id="volunteerId" name="volunteer_id">
                    <div class="form-group">
                        <label for="volunteerName">Volunteer Name</label>
                        <input type="text" id="volunteerName" readonly>
                    </div>
                    <div class="form-group">
                        <label for="taskTitle">Task Title</label>
                        <input type="text" id="taskTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="taskDescription">Task Description</label>
                        <textarea id="taskDescription" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="taskPriority">Priority</label>
                        <select id="taskPriority" name="priority" required>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="taskLocation">Location</label>
                        <select id="taskLocation" name="location" required onchange="loadVictimsByLocation()">
                            <option value="">Select Location</option>
                            <?php foreach (array_keys($victims_by_location) as $location): ?>
                                <option value="<?php echo htmlspecialchars($location); ?>">
                                    <?php echo htmlspecialchars($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dueDate">Due Date</label>
                        <input type="datetime-local" id="dueDate" name="due_date" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeTaskModal()">Cancel</button>
                <button class="btn btn-primary" onclick="assignTask()">Assign Task</button>
            </div>
        </div>
    </div>

    <!-- Add Chat Modal -->
    <div id="chatModal" class="chat-modal">
        <div class="chat-container">
            <div class="chat-header">
                <h3>Chat with <span id="chatWithName"></span></h3>
                <button class="close-chat" onclick="closeChat()">&times;</button>
            </div>
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input-container">
                <input type="text" id="messageInput" placeholder="Type a message...">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <style>
    /* Chat Modal Styles */
    .chat-modal {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        z-index: 1000;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }

    .chat-container {
        display: flex;
        flex-direction: column;
        height: 500px;
    }

    .chat-header {
        padding: 15px;
        background: #3498db;
        color: white;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        background: #f8f9fa;
    }

    .chat-input-container {
        padding: 15px;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 10px;
    }

    .chat-input-container input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }

    .chat-input-container button {
        padding: 8px 16px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .message {
        margin-bottom: 10px;
        max-width: 80%;
        clear: both;
    }

    .message.sent {
        float: right;
    }

    .message.sent .message-content {
        background: #3498db;
        color: white;
    }

    .message.received {
        float: left;
    }

    .message.received .message-content {
        background: #e9ecef;
        color: #212529;
    }

    .message-content {
        padding: 10px;
        border-radius: 10px;
        margin-bottom: 5px;
    }

    .message-info {
        font-size: 0.8em;
        color: #6c757d;
        clear: both;
    }
    </style>

    <script>
    // Store victims data for JavaScript use
    const victimsByLocation = <?php echo json_encode($victims_by_location); ?>;

    function openTaskModal(volunteerId, volunteerName) {
        document.getElementById('taskModal').style.display = 'block';
        document.getElementById('volunteerId').value = volunteerId;
        document.getElementById('volunteerName').value = volunteerName;
    }

    function loadVictimsByLocation() {
        const location = document.getElementById('taskLocation').value;
        if (!location) return;

        const victims = victimsByLocation[location] || [];
        const victimSelect = document.getElementById('victimSelection');
        const victimGroup = document.getElementById('victimSelectionGroup');
        
        victimSelect.innerHTML = '<option value="">Select a victim</option>';
        victims.forEach(victim => {
            victimSelect.innerHTML += `
                <option value="${victim.victim_id}">
                    ${victim.name} - Priority: ${victim.priority} - Needs: ${victim.needs}
                </option>`;
        });
        
        victimGroup.style.display = 'block';
    }

    function assignTask() {
        const form = document.getElementById('taskForm');
        const formData = new FormData(form);

        fetch('assign_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Task assigned successfully!');
                closeTaskModal();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function closeTaskModal() {
        document.getElementById('taskModal').style.display = 'none';
        document.getElementById('taskForm').reset();
        document.getElementById('victimSelectionGroup').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const taskModal = document.getElementById('taskModal');
        const chatModal = document.getElementById('chatModal');
        
        if (event.target == taskModal) {
            closeTaskModal();
        }
        if (event.target == chatModal) {
            closeChat();
        }
    }

    let currentChatVolunteerId = null;

    function openChat(volunteerId, volunteerName) {
        currentChatVolunteerId = volunteerId;
        document.getElementById('chatWithName').textContent = volunteerName;
        const chatModal = document.getElementById('chatModal');
        chatModal.style.display = 'block';
        loadMessages();
        
        // Clear existing interval if any
        if (window.chatRefreshInterval) {
            clearInterval(window.chatRefreshInterval);
        }
        
        // Start auto-refresh
        window.chatRefreshInterval = setInterval(loadMessages, 3000);
    }

    function closeChat() {
        const chatModal = document.getElementById('chatModal');
        chatModal.style.display = 'none';
        
        // Clear refresh interval
        if (window.chatRefreshInterval) {
            clearInterval(window.chatRefreshInterval);
        }
        
        // Reset current chat volunteer
        currentChatVolunteerId = null;
    }

    function loadMessages() {
        if (!currentChatVolunteerId) return;
        
        fetch(`get_messages.php?volunteer_id=${currentChatVolunteerId}`)
            .then(response => response.json())
            .then(messages => {
                const chatMessages = document.getElementById('chatMessages');
                const currentUserId = '<?php echo $_SESSION['user_id']; ?>';
                
                let html = '';
                messages.forEach(message => {
                    const isCurrentUser = message.sender_id === currentUserId;
                    html += `
                        <div class="message ${isCurrentUser ? 'sent' : 'received'}">
                            <div class="message-content">
                                ${message.message}
                            </div>
                            <div class="message-info">
                                <small>${message.sender_name} - ${new Date(message.sent_at).toLocaleTimeString()}</small>
                            </div>
                        </div>
                    `;
                });
                
                chatMessages.innerHTML = html;
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
    }

    function sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message || !currentChatVolunteerId) return;

        const formData = new FormData();
        formData.append('message', message);
        formData.append('volunteer_id', currentChatVolunteerId);

        fetch('send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadMessages();
            }
        });
    }

    // Handle enter key in message input
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>