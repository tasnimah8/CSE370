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

    try {
        $camp_id = 'CAMP-' . time();
        $camp_name = $_POST['camp_name'];
        $location = $_POST['location'];
        $capacity = $_POST['capacity'];
        $managed_by = !empty($_POST['managed_by']) ? $_POST['managed_by'] : null;

        $stmt = $conn->prepare("INSERT INTO relief_camps (camp_id, camp_name, location, capacity, current_occupancy, managed_by) VALUES (?, ?, ?, ?, 0, ?)");
        $stmt->bind_param("sssss", $camp_id, $camp_name, $location, $capacity, $managed_by);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Relief camp created successfully!";
        } else {
            throw new Exception("Error creating relief camp");
        }

        $conn->close();
        header("Location: relief-camps.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: relief-camps.php");
        exit();
    }
}

header("Location: relief-camps.php");
exit();
