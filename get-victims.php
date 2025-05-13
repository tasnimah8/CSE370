<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "floodguard";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Check if database exists
$dbResult = $conn->query("SHOW DATABASES LIKE '$dbname'");
if ($dbResult->num_rows == 0) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Database does not exist']));
}

// Select the database
$conn->select_db($dbname);

// Get location from query string
$location = isset($_GET['location']) ? $_GET['location'] : '';

if (empty($location)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Prepare and execute query
$stmt = $conn->prepare("SELECT victim_id, name, priority, relief_needed FROM victims WHERE location = ?");
$stmt->bind_param("s", $location);
$stmt->execute();
$result = $stmt->get_result();

$victims = [];
while ($row = $result->fetch_assoc()) {
    $victims[] = [
        'id' => $row['victim_id'],
        'name' => $row['name'],
        'priority' => $row['priority'],
        'needs' => $row['relief_needed']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($victims);

// Close connection
$stmt->close();
$conn->close();
?>
