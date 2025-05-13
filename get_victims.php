<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['error' => 'Unauthorized']));
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

$location = $conn->real_escape_string($_GET['location']);
$query = "SELECT victim_id, name, priority, needs FROM victims WHERE location = '$location'";
$result = $conn->query($query);

$victims = [];
while ($row = $result->fetch_assoc()) {
    $victims[] = $row;
}

header('Content-Type: application/json');
echo json_encode($victims);

$conn->close();
