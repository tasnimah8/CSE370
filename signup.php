<?php
// Initialize variables
$error = '';
$success = '';
$first_name = '';
$last_name = '';
$email = '';
$phone = '';
$role = '';
$location = '';
$skill_type = '';  // Add this line instead of address fields

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $servername = "localhost";
    $db_username = "root";
    $password = "";
    $dbname = "floodguard";

    // Create connection
    $conn = new mysqli($servername, $db_username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        $error = "Connection failed: " . $conn->connect_error;
    } else {
        // Get form data
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $role = trim($_POST['signup-role']);
        $new_password = $_POST['new-password'];
        $confirm_password = $_POST['confirm-password'];
        
        // Volunteer-specific fields
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $skill_type = isset($_POST['skill_type']) ? trim($_POST['skill_type']) : '';
        
        // Validate form data
        if (empty($first_name) || empty($last_name) || empty($email) || empty($new_password) || empty($confirm_password)) {
            $error = "All required fields must be filled";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } elseif (empty($role)) {
            $error = "Please select a role";
        } elseif ($role == 'volunteer' && (empty($location) || empty($_POST['skill_type']))) {
            $error = "Location and skill type must be specified for volunteers";
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already exists. Please use another email or try to login.";
            } else {
                // Generate user ID
                $user_id = 'USER-' . time();
                
                // Hash the password properly
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Insert new user with hashed password
                $stmt = $conn->prepare("INSERT INTO users (user_id, first_name, last_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $user_id, $first_name, $last_name, $email, $hashed_password, $phone, $role);
                
                if ($stmt->execute()) {
                    // If user is registering as a volunteer, add them to volunteer_profiles table
                    if ($role == 'volunteer') {
                        $volunteer_id = 'VOL-' . time();
                        $skill_type = $_POST['skill_type'];
                        $stmt = $conn->prepare("INSERT INTO volunteer_profiles (volunteer_id, location, skill_type) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $user_id, $location, $skill_type);
                        if (!$stmt->execute()) {
                            $error = "Error creating volunteer profile: " . $stmt->error;
                        }
                    }
                    // If user is registering as a donor, add them to donor_profiles table
                    elseif ($role == 'donor') {
                        $donor_id = 'DON-' . time();
                        $stmt = $conn->prepare("INSERT INTO donor_profiles (donor_id, donor_type) VALUES (?, 'individual')");
                        $stmt->bind_param("s", $user_id);
                        if (!$stmt->execute()) {
                            $error = "Error creating donor profile: " . $stmt->error;
                        }
                    }
                    
                    if (empty($error)) {
                        $success = "Registration successful!";
                        // Clear form data after successful registration
                        $first_name = '';
                        $last_name = '';
                        $email = '';
                        $phone = '';
                        $role = '';
                        $location = '';
                        $skill_type = '';
                        
                        // Redirect to login page
                        header("Location: login.php");
                        exit;
                    }
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
        }
        
        // Close connection
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Floodguard Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Login/Signup Section */
        .auth-section {
            padding: 5rem 5%;
            min-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .auth-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            position: relative;
        }
        
        .auth-form {
            padding: 3rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .auth-form h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .volunteer-fields {
            display: none;
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .submit-container {
            display: flex;
            justify-content: center;
            margin: 2rem 0 1rem;
        }
        
        .btn-submit {
            padding: 0.8rem 2rem;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 200px;
            text-align: center;
        }
        
        .btn-submit:hover {
            background-color: var(--accent-color);
        }
        
        .toggle-auth {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .toggle-auth a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .toggle-auth a:hover {
            text-decoration: underline;
        }
        
        /* Error/Success Messages */
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            text-align: center;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
                <li><a href="index.html">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php" class="active">Sign Up</a></li>
                <li><a href="emergency-contact.html">Emergency Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Signup Section -->
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-form">
                <h2>Sign Up</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="message error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="message success-message">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-role">Role</label>
                        <select id="signup-role" name="signup-role" required onchange="toggleRoleFields()">
                            <option value="">Select Role</option>
                            <option value="admin" <?php if($role == 'admin') echo 'selected'; ?>>Admin</option>
                            <option value="volunteer" <?php if($role == 'volunteer') echo 'selected'; ?>>Volunteer</option>
                            <option value="donor" <?php if($role == 'donor') echo 'selected'; ?>>Donor</option>
                        </select>
                    </div>
                    
                    <!-- Volunteer-specific fields -->
                    <div id="volunteer-fields" class="volunteer-fields" style="<?php echo ($role == 'volunteer') ? 'display:block;' : 'display:none;'; ?>">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>">
                        </div>
                        <div class="form-group">
                            <label for="skill_type">Skills</label>
                            <select id="skill_type" name="skill_type">
                                <option value="">Select Skill</option>
                                <option value="Medical">Medical</option>
                                <option value="Logistics">Logistics</option>
                                <option value="Transportation">Transportation</option>
                                <option value="Communication">Communication</option>
                                <option value="General">General Volunteer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new-password">Password</label>
                        <input type="password" id="new-password" name="new-password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm-password" required>
                    </div>
                    <div class="submit-container">
                        <button type="submit" class="btn-submit">Sign Up</button>
                    </div>
                    <div class="toggle-auth">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        function toggleRoleFields() {
            const roleSelect = document.getElementById('signup-role');
            const volunteerFields = document.getElementById('volunteer-fields');
            
            if (roleSelect.value === 'volunteer') {
                volunteerFields.style.display = 'block';
                document.getElementById('location').required = true;
                document.getElementById('skill_type').required = true;
            } else {
                volunteerFields.style.display = 'none';
                document.getElementById('location').required = false;
                document.getElementById('skill_type').required = false;
            }
        }
        
        // Initialize the form based on PHP values when page loads
        document.addEventListener('DOMContentLoaded', function() {
            toggleRoleFields();
        });
    </script>
</body>
</html>