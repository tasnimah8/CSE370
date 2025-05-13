<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$conn = new mysqli('localhost', 'root', '', 'floodguard');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donation_id = $_POST['donation_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $admin_id = $_SESSION['user_id'];

    $conn->begin_transaction();
    try {
        // Update donation status
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE donations SET status = ?, approved_by = ?, approved_at = NOW() WHERE donation_id = ?");
        $stmt->bind_param("sss", $status, $admin_id, $donation_id);
        $stmt->execute();

        // If approved and not monetary, add to inventory
        if ($action === 'approve') {
            $donation = $conn->query("SELECT * FROM donations WHERE donation_id = '$donation_id'")->fetch_assoc();
            if ($donation['donation_type'] !== 'monetary') {
                $inventory_id = 'INV-' . time();
                $stmt = $conn->prepare("INSERT INTO inventory (inventory_id, item_type, quantity, item_name, source_donation_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiss", $inventory_id, $donation['donation_type'], $donation['quantity'], $donation['items'], $donation_id);
                $stmt->execute();
            }
        }

        // Create notification
        $notification_id = 'NOT-' . time();
        $title = $action === 'approve' ? 'Donation Approved' : 'Donation Rejected';
        $message = $action === 'approve' ? 
            'Your donation has been approved and added to inventory.' : 
            'Your donation has been rejected.';
        
        $stmt = $conn->prepare("INSERT INTO notifications 
            (notification_id, user_id, title, message, type, reference_id) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $type = $action === 'approve' ? 'donation_approved' : 'donation_rejected';
        $stmt->bind_param("ssssss", 
            $notification_id, 
            $donation['donor_id'], 
            $title, 
            $message, 
            $type, 
            $donation_id
        );
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conn->close();
