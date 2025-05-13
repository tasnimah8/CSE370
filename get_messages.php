<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
$user_id = $_SESSION['user_id'];
$chat_with_id = $_GET['chat_with'] ?? null;

// If chat_with not provided and user is admin, get volunteer_id from request
if (!$chat_with_id && $_SESSION['role'] === 'admin') {
    $chat_with_id = $_GET['volunteer_id'] ?? null;
}

// If still no chat_with_id and user is volunteer, get admin id
if (!$chat_with_id && $_SESSION['role'] === 'volunteer') {
    $query = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
    $result = $conn->query($query);
    $chat_with_id = $result->fetch_assoc()['user_id'];
}

$sql = "SELECT c.*, 
        CONCAT(sender.first_name, ' ', sender.last_name) as sender_name,
        CONCAT(receiver.first_name, ' ', receiver.last_name) as receiver_name
        FROM chatbox c 
        JOIN users sender ON c.sender_id = sender.user_id 
        JOIN users receiver ON c.receiver_id = receiver.user_id
        WHERE (c.sender_id = ? AND c.receiver_id = ?) 
        OR (c.sender_id = ? AND c.receiver_id = ?)
        ORDER BY c.sent_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $user_id, $chat_with_id, $chat_with_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['message_id'],
        'sender_id' => $row['sender_id'],
        'sender_name' => $row['sender_name'],
        'receiver_name' => $row['receiver_name'],
        'message' => $row['message'],
        'sent_at' => $row['sent_at']
    ];
}

header('Content-Type: application/json');
echo json_encode($messages);
$conn->close();
