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

    $inventory_id = $_POST['inventory_id'];
    $item_name = $_POST['item_name'];
    $item_type = $_POST['item_category'];
    $quantity = $_POST['quantity'];
    $item_description = $_POST['item_description'] ?? '';
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    $stmt = $conn->prepare("UPDATE inventory SET item_name = ?, item_type = ?, quantity = ?, item_description = ?, expiry_date = ? WHERE inventory_id = ?");
    $stmt->bind_param("ssisss", $item_name, $item_type, $quantity, $item_description, $expiry_date, $inventory_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Item updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating item.";
    }

    $conn->close();
    header('Location: inventory.php');
    exit();
}

header('Location: inventory.php');
exit();
