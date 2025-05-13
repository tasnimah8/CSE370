<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$chat_with_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$current_user_id = $_SESSION['user_id'];

// Get chat participant details
if ($chat_with_id) {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $chat_with_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chat_with = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .chat-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 70%;
        }
        .message.sent {
            background: #007bff;
            color: white;
            margin-left: auto;
        }
        .message.received {
            background: #e9ecef;
            color: black;
        }
        .chat-input {
            display: flex;
            gap: 10px;
        }
        .chat-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .chat-input button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .sub-nav {
            background: #f8f9fa;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .sub-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sub-nav-actions button {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Sub Navigation Bar -->
    <div class="sub-nav">
        <div class="container">
            <h1>Chat with <?php echo htmlspecialchars($chat_with['first_name'] . ' ' . $chat_with['last_name']); ?></h1>
            <div class="sub-nav-actions">
                <button class="btn btn-secondary"><i class="fas fa-video"></i> Video Call</button>
                <button class="btn btn-secondary"><i class="fas fa-phone"></i> Voice Call</button>
                <button class="btn btn-secondary"><i class="fas fa-user-circle"></i> View Profile</button>
            </div>
        </div>
    </div>
    
    <div class="chat-container">
        <?php if ($chat_with): ?>
            <h2>Chat with <?php echo htmlspecialchars($chat_with['first_name'] . ' ' . $chat_with['last_name']); ?></h2>
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Type your message...">
                <button onclick="sendMessage()">Send</button>
            </div>
        <?php else: ?>
            <p>Please select a user to chat with.</p>
        <?php endif; ?>
    </div>

    <script>
    const chatWithId = '<?php echo $chat_with_id; ?>';
    const currentUserId = '<?php echo $current_user_id; ?>';

    function loadMessages() {
        fetch(`get_messages.php?chat_with=${chatWithId}`)
            .then(response => response.json())
            .then(messages => {
                const chatMessages = document.getElementById('chatMessages');
                chatMessages.innerHTML = messages.map(message => `
                    <div class="message ${message.sender_id === currentUserId ? 'sent' : 'received'}">
                        ${message.message}
                    </div>
                `).join('');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
    }

    function sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message) return;

        fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `receiver_id=${chatWithId}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadMessages();
            }
        });
    }

    // Load messages every 3 seconds
    loadMessages();
    setInterval(loadMessages, 3000);
    </script>
</body>
</html>
