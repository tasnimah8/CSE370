<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch available volunteers
$volunteer_query = "
    SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone,
           vp.location, vp.is_available, vp.skill_type
    FROM users u
    JOIN volunteer_profiles vp ON u.user_id = vp.volunteer_id
    WHERE u.role = 'volunteer' AND u.status = 'active'
    ORDER BY vp.is_available DESC, u.first_name ASC";

$volunteers = $conn->query($volunteer_query);

$camps_query = "
    SELECT rc.*, u.first_name, u.last_name 
    FROM relief_camps rc
    LEFT JOIN users u ON rc.managed_by = u.user_id
    ORDER BY rc.location";
$camps = $conn->query($camps_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Contacts - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Hero Section Styles */
        .emergency-hero {
            background-color: #3E3F5B;
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 0 0 20px 20px;
            margin-bottom: 3rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .emergency-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }
        
        .contact-cards {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .contact-card {
            background: rgba(255,255,255,0.1);
            padding: 2rem;
            border-radius: 15px;
            width: 300px;
            transition: transform 0.3s;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
        }
        
        .contact-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #8AB2A6;
        }
        
        .contact-card h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .contact-card p {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        
        /* Table Section */
        .volunteer-section {
            padding: 0 2rem 4rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .volunteer-section h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: #3E3F5B;
            font-size: 2rem;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background-color: #3E3F5B;
            color: white;
            font-weight: 500;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #f1f1f1;
        }
        
        .badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge.available {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }
        
        .badge.not-available {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        /* Navigation Styles */
        .navbar {
            background-color: #2c3e50;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            font-size: 24px;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 25px;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }
        
        .nav-links a:hover, .nav-links .active a {
            color: #3498db;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .badge.full {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }
        
        .badge.almost-full {
            background-color: rgba(255, 152, 0, 0.1);
            color: #FF9800;
        }
        
        .badge.available {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <!-- Header with Navigation -->
    <header>
        <nav class="navbar glass">
            <div class="logo">
                <i class="fas fa-hands-helping"></i>
                <h1>Floodguard Network</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login/Signup</a></li>
                <li><a href="admin-dashboard.php">Admin Dashboard</a></li>
                <li><a href="volunteer-dashboard.php">Volunteer Dashboard</a></li>
                <li><a href="donor-dashboard.php">Donor Dashboard</a></li>
                <li><a href="emergency-contact.php" class="active">Emergency Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Emergency Contacts Hero Section -->
    <section class="emergency-hero">
        <h1>Emergency Contacts</h1>
        <div class="contact-cards">
            <div class="contact-card glass">
                <i class="fas fa-phone-alt"></i>
                <h3>National Disaster Helpline</h3>
                <p>Call: 1-800-DISASTER</p>
                <p>Available 24/7</p>
            </div>
            <div class="contact-card glass">
                <i class="fas fa-ambulance"></i>
                <h3>Emergency Medical</h3>
                <p>Call: 911 or 112</p>
                <p>Immediate medical assistance</p>
            </div>
            <div class="contact-card glass">
                <i class="fas fa-life-ring"></i>
                <h3>Flood Rescue</h3>
                <p>Call: 1-800-FLOODSV</p>
                <p>Water rescue services</p>
            </div>
        </div>
    </section>

    <!-- Volunteer List Section -->
    <section class="volunteer-section">
        <h2>Available Volunteers</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Volunteer Name</th>
                        <th>Phone Number</th>
                        <th>Email</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($volunteer = $volunteers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($volunteer['first_name'] . ' ' . $volunteer['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($volunteer['phone']); ?></td>
                        <td><?php echo htmlspecialchars($volunteer['email']); ?></td>
                        <td><?php echo htmlspecialchars($volunteer['location']); ?></td>
                        <td>
                            <span class="badge <?php echo $volunteer['is_available'] ? 'available' : 'not-available'; ?>">
                                <?php echo $volunteer['is_available'] ? 'Available' : 'Not Available'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($volunteers->num_rows === 0): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No volunteers found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Relief Camps Section -->
    <section class="volunteer-section">
        <h2>Relief Camp Locations</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Camp Name</th>
                        <th>Location</th>
                        <th>Capacity</th>
                        <th>Current Occupancy</th>
                        <th>Managed By</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($camp = $camps->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($camp['camp_name']); ?></td>
                        <td><?php echo htmlspecialchars($camp['location']); ?></td>
                        <td><?php echo htmlspecialchars($camp['capacity']); ?> people</td>
                        <td>
                            <?php 
                            $occupancy_percentage = ($camp['current_occupancy'] / $camp['capacity']) * 100;
                            $status_class = $occupancy_percentage >= 90 ? 'full' : 
                                         ($occupancy_percentage >= 70 ? 'almost-full' : 'available');
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($camp['current_occupancy']); ?> / <?php echo htmlspecialchars($camp['capacity']); ?>
                            </span>
                        </td>
                        <td><?php echo $camp['first_name'] ? htmlspecialchars($camp['first_name'] . ' ' . $camp['last_name']) : 'Unassigned'; ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($camp['last_updated'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($camps->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No relief camps found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
        // You can add JavaScript functionality here if needed
        // For example, filtering the volunteer table or making status updates
    </script>
</body>
</html>
<?php $conn->close(); ?>