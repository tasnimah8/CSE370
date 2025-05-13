<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', '', 'floodguard');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $camp_id = $_POST['camp_id'];
    $camp_name = $_POST['camp_name'];
    $location = $_POST['location'];
    $capacity = $_POST['capacity'];
    $managed_by = !empty($_POST['managed_by']) ? $_POST['managed_by'] : null;

    $stmt = $conn->prepare("UPDATE relief_camps SET camp_name = ?, location = ?, capacity = ?, managed_by = ? WHERE camp_id = ?");
    $stmt->bind_param("ssiss", $camp_name, $location, $capacity, $managed_by, $camp_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Camp updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating camp: " . $conn->error;
    }

    $conn->close();
    header("Location: relief-camps.php");
    exit();
}

header("Location: relief-camps.php");
exit();
