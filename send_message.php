<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

$sender_id = $_SESSION['user_id'];
$message = trim($_POST['message']);
$receiver_id = $_POST['receiver_id'] ?? null;

if (empty($message)) {
    die(json_encode(['success' => false, 'error' => 'Message cannot be empty']));
}

// If receiver_id is not provided, get the appropriate receiver based on role
if (!$receiver_id) {
    if ($_SESSION['role'] === 'volunteer') {
        // Get admin ID
        $query = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
        $result = $conn->query($query);
        $receiver_id = $result->fetch_assoc()['user_id'];
    } else if ($_SESSION['role'] === 'admin') {
        $receiver_id = $_POST['volunteer_id'];
    }
}

$sql = "INSERT INTO chatbox (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $sender_id, $receiver_id, $message);

$response = ['success' => $stmt->execute()];
if (!$response['success']) {
    $response['error'] = $stmt->error;
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
