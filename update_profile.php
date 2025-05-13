<?php
session_start();

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'volunteer') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "floodguard";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get form data
    $user_id = $_SESSION['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    $location = trim($_POST['location']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Check if email is already in use by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("ss", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already in use by another user");
        }

        // Update user information including email
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $user_id);
        $stmt->execute();

        // Update volunteer profile including location and other details
        $stmt = $conn->prepare("UPDATE volunteer_profiles SET location = ?, address = ?, city = ?, state = ?, zip_code = ? WHERE volunteer_id = ?");
        $stmt->bind_param("ssssss", $location, $address, $city, $state, $zip_code, $user_id);
        $stmt->execute();

        // Delete existing skills
        $stmt = $conn->prepare("DELETE FROM volunteer_skills WHERE volunteer_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();

        // Insert new skills
        if (!empty($skills)) {
            $stmt = $conn->prepare("INSERT INTO volunteer_skills (volunteer_id, skill_name, proficiency_level) VALUES (?, ?, ?)");
            
            foreach ($_POST['skills'] as $skill) {
                if (!empty($skill['name']) && !empty($skill['level'])) {
                    $stmt->bind_param("sss", $user_id, $skill['name'], $skill['level']);
                    $stmt->execute();
                }
            }
        }

        $conn->commit();
        $_SESSION['full_name'] = $first_name . ' ' . $last_name;
        $_SESSION['email'] = $email;
        header("Location: volunteer-dashboard.php?update=success");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: volunteer-dashboard.php?update=error&message=" . urlencode($e->getMessage()));
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: volunteer-dashboard.php");
}
?>
