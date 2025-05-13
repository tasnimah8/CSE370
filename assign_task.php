<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Generate task ID
$task_id = 'TASK-' . time();

// Sanitize inputs
$volunteer_id = $conn->real_escape_string($_POST['volunteer_id']);
$title = $conn->real_escape_string($_POST['title']);
$description = $conn->real_escape_string($_POST['description']);
$priority = $conn->real_escape_string($_POST['priority']);
$location = $conn->real_escape_string($_POST['location']);
$due_date = $conn->real_escape_string($_POST['due_date']);

// Insert task
$query = "INSERT INTO tasks (task_id, title, description, priority, due_date, location, assigned_to, status) 
          VALUES ('$task_id', '$title', '$description', '$priority', '$due_date', '$location', '$volunteer_id', 'pending')";

if ($conn->query($query)) {
    // Update volunteer availability
    $conn->query("UPDATE volunteer_profiles SET is_available = 0 WHERE volunteer_id = '$volunteer_id'");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating task']);
}

$conn->close();
