<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "floodguard";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$isAvailable = isset($_POST['toggle_availability']) ? (int)$_POST['toggle_availability'] : 0;

// Update availability status
$stmt = $conn->prepare("UPDATE volunteer_profiles SET is_available = ? WHERE volunteer_id = ?");
$stmt->bind_param("is", $isAvailable, $user_id);

$response = ['success' => false];

if ($stmt->execute()) {
    $response['success'] = true;
    $response['is_available'] = (bool)$isAvailable;
} else {
    $response['message'] = 'Failed to update availability';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
