<?php
// Initialize variables
$error = '';
$email = '';
$role = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname = "floodguard";

    // Create connection
    $conn = new mysqli($servername, $db_username, $db_password);

    // Check connection
    if ($conn->connect_error) {
        $error = "Connection failed: " . $conn->connect_error;
    } else {
        // Check if database exists
        $dbResult = $conn->query("SHOW DATABASES LIKE '$dbname'");
        if ($dbResult->num_rows == 0) {
            $error = "Database does not exist. Please contact admin.";
        } else {
            // Select the database
            $conn->select_db($dbname);
            
            // Get form data
            $email = trim($_POST['login-email'] ?? '');
            $role = trim($_POST['login-role'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validate form data
            if (empty($email) || empty($password) || empty($role)) {
                $error = "All fields are required";
            } else {
                // Check user credentials - updated to use first_name and last_name
                $stmt = $conn->prepare("SELECT user_id, email, password, role, first_name, last_name FROM users WHERE email = ? AND role = ?");
                if ($stmt) {
                    $stmt->bind_param("ss", $email, $role);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        
                        // Verify password using password_verify()
                        if (password_verify($password, $user['password'])) {
                            // Start session
                            session_start();
                            
                            // Set session variables - combine first and last name
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                            
                            // Update last login time
                            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                            $stmt->bind_param("s", $user['user_id']);
                            $stmt->execute();
                            
                            // Redirect based on role
                            switch($role) {
                                case 'admin':
                                    header("Location: admin-dashboard.php");
                                    break;
                                case 'volunteer':
                                    header("Location: volunteer-dashboard.php");
                                    break;
                                case 'donor':
                                    header("Location: donor-dashboard.php");
                                    break;
                                default:
                                    header("Location: index.html");
                                    break;
                            }
                            exit;
                        } else {
                            $error = "Invalid email or password";
                        }
                    } else {
                        $error = "Invalid email or password";
                    }
                } else {
                    $error = "Database error: " . $conn->error;
                }
            }
            
            // Close connection
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Floodguard Network</title>
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
            transition: all 0.3s ease;
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
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Error message styling */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
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
                <li><a href="login.php" class="active">Login/Signup</a></li>
                <li><a href="admin-dashboard.html">Admin Dashboard</a></li>
                <li><a href="volunteer-dashboard.html">Volunteer Dashboard</a></li>
                <li><a href="donor-dashboard.html">Donor Dashboard</a></li>
                <li><a href="emergency-contact.html">Emergency Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Login Section -->
    <section id="login-section" class="auth-section">
        <div class="auth-container">
            <div class="auth-form glass fade-in">
                <h2>Login</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="form-group">
                        <label for="login-email">Email</label>
                        <input type="email" id="login-email" name="login-email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-role">Role</label>
                        <select id="login-role" name="login-role" required>
                            <option value="" <?php if(empty($role)) echo 'selected'; ?>>Select Role</option>
                            <option value="admin" <?php if($role == 'admin') echo 'selected'; ?>>Admin</option>
                            <option value="volunteer" <?php if($role == 'volunteer') echo 'selected'; ?>>Volunteer</option>
                            <option value="donor" <?php if($role == 'donor') echo 'selected'; ?>>Donor</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="submit-container">
                        <button type="submit" class="btn-submit">Login</button>
                    </div>
                    
                    <div class="toggle-auth">
                        Don't have an account? <a href="signup.php">Sign Up</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>
</html>