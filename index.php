<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'floodguard');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get today's statistics
$today = date('Y-m-d');
$today_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT victim_id) as families_served,
        SUM(quantity) as items_distributed,
        COUNT(DISTINCT location) as locations_covered
    FROM distribution_records 
    WHERE DATE(distribution_date) = '$today'
")->fetch_assoc();

// Get monthly statistics
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$monthly_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT victim_id) as families_helped,
        SUM(quantity) as items_distributed
    FROM distribution_records 
    WHERE distribution_date BETWEEN '$month_start' AND '$month_end'
")->fetch_assoc();

// Calculate progress percentages
$daily_target = 50; // Example: daily target of 100 families
$monthly_target = 100; // Monthly target of 1000 families

$daily_progress = min(100, ($today_stats['families_served'] / $daily_target) * 100);
$monthly_progress = min(100, ($monthly_stats['families_helped'] / $monthly_target) * 100);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floodguard Network - Flood Relief Distribution System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Full page hero section styles */
        .hero {
            height: 100vh;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .slideshow-container {
            height: 100%;
            width: 100%;
        }
        
        .slide {
            height: 100%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .slide-img {
            height: 100%;
            width: 100%;
            background-size: cover;
            background-position: center;
            filter: brightness(0.7);
        }
        
        .hero-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 80%;
            max-width: 800px;
            padding: 2rem;
            z-index: 2;
        }
        
        .hero-content h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .cta-button {
            display: inline-block;
            padding: 1rem 2rem;
            background-color: #0077cc;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .cta-button:hover {
            background-color: #005fa3;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar glass">
            <div class="logo">
                <i class="fas fa-hands-helping"></i>
                <h1>Floodguard Network</h1>
            </div>
            <ul class="nav-links">
                <li><a href="#" class="active">Home</a></li>
                <li><a href="login.php">Login/Signup</a></li>
                <li><a href="admin-dashboard.php">Admin Dashboard</a></li>
                <li><a href="volunteer-dashboard.php">Volunteer Dashboard</a></li>
                <li><a href="donor-dashboard.php">Donor Dashboard</a></li>
                <li><a href="emergency-contact.php">Emergency Contact</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="slideshow-container">
                <div class="slide fade">
                    <div class="slide-img" style="background-image: url('bg1.jpg?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');"></div>
                </div>
                <div class="slide fade">
                    <div class="slide-img" style="background-image: url('bg2.jpg?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');"></div>
                </div>
                <div class="slide fade">
                    <div class="slide-img" style="background-image: url('bg3.jpg?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');"></div>
                </div>
            </div>
            <div class="hero-content glass">
                <h2>Connecting Help with Need</h2>
                <p>A comprehensive flood relief distribution system ensuring efficient aid delivery to affected communities</p>
                <a href="login.php" class="cta-button">Get Involved</a>
            </div>
        </section>

        <section class="progress-section">
            <h2>Our Progress</h2>
            <div class="progress-container">
                <div class="daily-progress glass">
                    <h3>Today's Relief Efforts</h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $daily_progress; ?>%;"></div>
                    </div>
                    <p><?php echo round($daily_progress); ?>% of today's relief packages distributed</p>
                    <ul class="progress-stats">
                        <li><i class="fas fa-people-carry"></i> <?php echo number_format($today_stats['families_served']); ?> families served</li>
                        <li><i class="fas fa-box-open"></i> <?php echo number_format($today_stats['items_distributed']); ?> relief items delivered</li>
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo number_format($today_stats['locations_covered']); ?> locations covered</li>
                    </ul>
                </div>
                <div class="monthly-progress glass">
                    <h3>Monthly Achievements</h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $monthly_progress; ?>%;"></div>
                    </div>
                    <p><?php echo round($monthly_progress); ?>% of monthly target achieved</p>
                    <ul class="progress-stats">
                        <li><i class="fas fa-home"></i> <?php echo number_format($monthly_stats['families_helped']); ?> of 1,000 families reached</li>
                        <li><i class="fas fa-box"></i> <?php echo number_format($monthly_stats['items_distributed']); ?> items distributed</li>
                        <li><i class="fas fa-calendar"></i> <?php echo date('F Y'); ?></li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="about-us" class="about-section">
            <h2>About Floodguard Network</h2>
            <div class="about-content glass" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
                <div class="about-text">
                    <h3>Our Mission</h3>
                    <p>Floodguard Network is a comprehensive flood relief distribution system designed to efficiently connect donors, volunteers, and relief organizations with communities affected by flooding. Our mission is to bridge the gap between those who want to help and those who need assistance during flood disasters.</p>
                    
                    <h3>How We Work</h3>
                    <p>Through our innovative platform, we coordinate resources in real-time, track distribution progress, and maintain complete transparency in all relief operations. Our system ensures that aid reaches the most vulnerable populations quickly and efficiently.</p>
                    
                    <h3>Our Impact</h3>
                    <p>Since our founding, we've facilitated the delivery of over 250,000 relief packages to flood-affected areas, coordinated more than 10,000 volunteers, and connected hundreds of donors with communities in need. Our network spans across 15 flood-prone regions, providing rapid response when disasters strike.</p>
                    
                    <h3>Core Values</h3>
                    <ul class="values-list">
                        <li><i class="fas fa-check-circle"></i> <strong>Transparency:</strong> Every donation and distribution is tracked and publicly verifiable</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Efficiency:</strong> Minimizing delays in getting aid to where it's needed most</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Community:</strong> Empowering local communities to participate in relief efforts</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Innovation:</strong> Continuously improving our systems to better serve those in need</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="emergency-contact" class="emergency-section">
            <h2>Emergency Contacts</h2>
            <div class="emergency-contacts">
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
    </main>

    <script>
        // Add this function before the existing slideshow code
        function toggleLocationInput() {
            const roleSelect = document.getElementById('signup-role');
            const locationGroup = document.getElementById('location-group');
            locationGroup.style.display = roleSelect.value === 'volunteer' ? 'block' : 'none';
        }

        // Slideshow functionality
        let slideIndex = 0;
        showSlides();

        function showSlides() {
            let i;
            let slides = document.getElementsByClassName("slide");
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";  
            }
            slideIndex++;
            if (slideIndex > slides.length) {slideIndex = 1}    
            slides[slideIndex-1].style.display = "block";  
            setTimeout(showSlides, 5000); // Change image every 5 seconds
        }
    </script>
    <style>
        /* Add these styles for smoother progress bars */
        .progress-bar {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            background: linear-gradient(90deg, #2ed573, #7bed9f);
            height: 10px;
            border-radius: 10px;
            transition: width 0.5s ease-in-out;
        }
    </style>
</body>
</html>
