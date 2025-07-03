<?php
// Start session for theme persistence
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change to your database username
define('DB_PASS', ''); // Change to your database password
define('DB_NAME', 'restaurant_db');

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create reservations table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    guests INT NOT NULL,
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTable)) {
    die("Error creating table: " . $conn->error);
}

// Handle theme toggle
if (isset($_GET['toggle_theme'])) {
    $_SESSION['theme'] = ($_SESSION['theme'] ?? 'light') === 'light' ? 'dark' : 'light';
    echo '<script>localStorage.setItem("theme", "' . $_SESSION['theme'] . '");</script>';
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect_url);
    exit;
}

// Set current theme
$currentTheme = $_SESSION['theme'] ?? 'light';

// Handle form submission
$formSuccess = false;
$reservationId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation'])) {
    // Sanitize and validate input
    $name = $conn->real_escape_string(htmlspecialchars($_POST['name']));
    $email = $conn->real_escape_string(htmlspecialchars($_POST['email']));
    $phone = $conn->real_escape_string(htmlspecialchars($_POST['phone']));
    $date = $conn->real_escape_string(htmlspecialchars($_POST['date']));
    $time = $conn->real_escape_string(htmlspecialchars($_POST['time']));
    $guests = (int)$_POST['guests'];
    $requests = $conn->real_escape_string(htmlspecialchars($_POST['special-requests']));

    // Insert into database using prepared statement
    $stmt = $conn->prepare("INSERT INTO reservations (name, email, phone, reservation_date, reservation_time, guests, special_requests) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssis", $name, $email, $phone, $date, $time, $guests, $requests);
    
    if ($stmt->execute()) {
        $formSuccess = true;
        $reservationId = $stmt->insert_id;
    } else {
        // For debugging - remove in production
        echo "<script>console.error('Database error: " . $stmt->error . "');</script>";
    }
    $stmt->close();
    
    // Store in session for form repopulation
    $_SESSION['reservation'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'date' => $date,
        'time' => $time,
        'guests' => $guests,
        'requests' => $requests
    ];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $currentTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gourmet | Fine Dining Experience</title>
    <style>
        /* CSS Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Variables for Theme Switching */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #495057;
            --accent-color: #d62828;
            --card-bg: #ffffff;
            --nav-bg: rgba(255, 255, 255, 0.9);
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] {
            --bg-primary: #121212;
            --bg-secondary: #1e1e1e;
            --text-primary: #f8f9fa;
            --text-secondary: #adb5bd;
            --accent-color: #f77f00;
            --card-bg: #1e1e1e;
            --nav-bg: rgba(30, 30, 30, 0.9);
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        /* Base Styles */
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Typography */
        h1, h2, h3 {
            font-weight: 700;
            line-height: 1.2;
        }

        h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
        }

        h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50%;
            height: 4px;
            background-color: var(--accent-color);
        }

        p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Layout */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 0;
        }

        section {
            padding: 5rem 0;
        }

        /* Navigation */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background-color: var(--nav-bg);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 5%;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent-color);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--accent-color);
            transition: width 0.3s;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        /* Theme Toggle */
        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-primary);
            transition: transform 0.3s;
            padding: 0.5rem;
        }

        .theme-toggle:hover {
            transform: rotate(30deg);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 0 5%;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=3840&q=80') center/cover;
            z-index: -1;
        }

        [data-theme="dark"] .hero::before {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.9)), 
                        url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=3840&q=80') center/cover;
        }

        .hero h1 {
            color: white;
            margin-bottom: 1rem;
            opacity: 0;
            animation: fadeIn 1s forwards 0.5s;
        }

        .hero p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            max-width: 600px;
            opacity: 0;
            animation: slideUp 1s forwards 1s;
        }

        /* Buttons */
        .cta-button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            opacity: 0;
            animation: fadeIn 1s forwards 1.5s;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .filter-btn {
            padding: 0.5rem 1.5rem;
            background: none;
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filter-btn.active, .filter-btn:hover {
            background-color: var(--accent-color);
            color: white;
        }

        /* Menu Section */
        .menu-filters {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 3rem;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .menu-item {
            background-color: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s;
        }

        .menu-item:hover {
            transform: translateY(-10px);
        }

        .menu-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .menu-item-content {
            padding: 1.5rem;
        }

        .menu-item h3 {
            margin-bottom: 0.5rem;
        }

        .price {
            display: block;
            font-weight: 700;
            color: var(--accent-color);
            margin-top: 0.5rem;
            font-size: 1.2rem;
        }

        /* About Section */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .about-image {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 3rem auto 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 100%;
            background-color: var(--accent-color);
        }

        .timeline-item {
            padding: 1.5rem;
            position: relative;
            width: 50%;
            opacity: 0;
        }

        .timeline-item:nth-child(odd) {
            left: 0;
            text-align: right;
            animation: slideLeft 1s forwards;
        }

        .timeline-item:nth-child(even) {
            left: 50%;
            animation: slideRight 1s forwards;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--accent-color);
            transform: translateY(-50%);
        }

        .timeline-item:nth-child(odd)::after {
            right: -30px;
        }

        .timeline-item:nth-child(even)::after {
            left: -30px;
        }

        /* Reservations */
        #reservationForm {
            max-width: 600px;
            margin: 0 auto;
            background-color: var(--card-bg);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        .form-success {
            background: var(--accent-color);
            color: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            text-align: center;
        }

        /* Footer */
        footer {
            background-color: var(--bg-secondary);
            padding: 3rem 0;
            text-align: center;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .social-links {
            display: flex;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .social-links a {
            color: var(--text-primary);
            font-size: 1.5rem;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: var(--accent-color);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideLeft {
            from { 
                opacity: 0;
                transform: translateX(-50px);
            }
            to { 
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideRight {
            from { 
                opacity: 0;
                transform: translateX(50px);
            }
            to { 
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }

        .animate-bounce {
            animation: bounce 2s infinite;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .about-content {
                grid-template-columns: 1fr;
            }

            .timeline::before {
                left: 30px;
            }

            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 0;
            }

            .timeline-item:nth-child(odd), 
            .timeline-item:nth-child(even) {
                left: 0;
                text-align: left;
            }

            .timeline-item:nth-child(odd)::after, 
            .timeline-item:nth-child(even)::after {
                left: 20px;
                right: auto;
            }

            nav {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header/Navigation -->
    <header>
        <nav>
            <div class="logo">Gourmet Restaurant</div>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#menu">Menu</a>
                <a href="#about">About</a>
                <a href="#reservations">Reservations</a>
            </div>
            <button class="theme-toggle" id="themeToggle">🌓</button>
        </nav>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <h1>Artisanal Dining Experience</h1>
        <p>Michelin-starred cuisine crafted with locally-sourced ingredients and innovative techniques</p>
        <button class="cta-button" onclick="document.getElementById('reservations').scrollIntoView({behavior: 'smooth'})">Reserve Now</button>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="container">
        <h2>Our Menu</h2>
        <div class="menu-filters">
            <button class="filter-btn active" data-category="all">All</button>
            <button class="filter-btn" data-category="starters">Starters</button>
            <button class="filter-btn" data-category="mains">Mains</button>
            <button class="filter-btn" data-category="desserts">Desserts</button>
        </div>
        <div class="menu-grid">
            <div class="menu-item" data-category="starters">
                <img src="https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=3840&q=80" alt="Tuna Tartare">
                <div class="menu-item-content">
                    <h3>Tuna Tartare</h3>
                    <p>Fresh yellowfin tuna, avocado, sesame oil, crispy wontons</p>
                    <span class="price">$24</span>
                </div>
            </div>
            <div class="menu-item" data-category="starters">
                <img src="https://images.unsplash.com/photo-1607532941433-304659e8198a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=3840&q=80" alt="Burrata Salad">
                <div class="menu-item-content">
                    <h3>Burrata Salad</h3>
                    <p>Creamy burrata, heirloom tomatoes, basil, aged balsamic</p>
                    <span class="price">$18</span>
                </div>
            </div>
            <div class="menu-item" data-category="mains">
                <img src="https://images.unsplash.com/photo-1544025162-d76694265947?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=3840&q=80" alt="Filet Mignon">
                <div class="menu-item-content">
                    <h3>Filet Mignon</h3>
                    <p>8oz grass-fed beef, truffle mashed potatoes, seasonal vegetables</p>
                    <span class="price">$42</span>
                </div>
            </div>
            <div class="menu-item" data-category="mains">
                <img src="https://images.unsplash.com/photo-1565557623262-b51c2513a641?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=3840&q=80" alt="Pan-Seared Salmon">
                <div class="menu-item-content">
                    <h3>Pan-Seared Salmon</h3>
                    <p>Wild-caught salmon, lemon beurre blanc, asparagus, forbidden rice</p>
                    <span class="price">$36</span>
                </div>
            </div>
            <div class="menu-item" data-category="desserts">
                <img src="https://images.unsplash.com/photo-1563805042-7684c019e1cb?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=3840&q=80" alt="Chocolate Soufflé">
                <div class="menu-item-content">
                    <h3>Chocolate Soufflé</h3>
                    <p>Warm chocolate soufflé with vanilla bean ice cream</p>
                    <span class="price">$14</span>
                </div>
            </div>
            <div class="menu-item" data-category="desserts">
                <img src="burlee.jpg" alt="Crème Brûlée">
                <div class="menu-item-content">
                    <h3>Crème Brûlée</h3>
                    <p>Classic vanilla bean custard with caramelized sugar crust</p>
                    <span class="price">$12</span>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="container">
        <h2>Our Story</h2>
        <div class="about-content">
            <div class="about-text">
                <p>Founded in 2010 by Chef Marco Ferrara, Gourmet began as a small 12-seat bistro with a simple mission: to create extraordinary dining experiences using the finest local ingredients.</p>
                <p>Today, our Michelin-starred restaurant continues that tradition, blending innovation with respect for culinary heritage in every dish we serve.</p>
            </div>
            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1600891964599-f61ba0e24092?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=3840&q=80" alt="Our Chef">
            </div>
        </div>
        
        <div class="timeline">
            <div class="timeline-item">
                <h3>2010</h3>
                <p>Gourmet opens its doors in downtown</p>
            </div>
            <div class="timeline-item">
                <h3>2014</h3>
                <p>First James Beard Award nomination</p>
            </div>
            <div class="timeline-item">
                <h3>2017</h3>
                <p>Received first Michelin star</p>
            </div>
            <div class="timeline-item">
                <h3>2020</h3>
                <p>Expanded to current 50-seat location</p>
            </div>
            <div class="timeline-item">
                <h3>2023</h3>
                <p>Named among "Top 50 Restaurants in America"</p>
            </div>
        </div>
    </section>

    <!-- Reservations Section -->
    <section id="reservations" class="container">
        <h2>Make a Reservation</h2>
        <?php if (isset($formSuccess)): ?>
            <div class="form-success">
                Thank you for your reservation, <?php echo htmlspecialchars($_SESSION['reservation']['name']); ?>!<br>
                Your reservation ID is #<?php echo $reservationId; ?>.<br>
                We've sent a confirmation to <?php echo htmlspecialchars($_SESSION['reservation']['email']); ?>.
            </div>
        <?php endif; ?>
        <form id="reservationForm" method="POST">
            <input type="hidden" name="reservation" value="1">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required value="<?php echo isset($_SESSION['reservation']) ? htmlspecialchars($_SESSION['reservation']['name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_SESSION['reservation']) ? htmlspecialchars($_SESSION['reservation']['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required value="<?php echo isset($_SESSION['reservation']) ? htmlspecialchars($_SESSION['reservation']['phone']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required value="<?php echo isset($_SESSION['reservation']) ? htmlspecialchars($_SESSION['reservation']['date']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="time">Time</label>
                <input type="time" id="time" name="time" required value="<?php echo isset($_SESSION['reservation']) ? htmlspecialchars($_SESSION['reservation']['time']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="guests">Number of Guests</label>
                <select id="guests" name="guests" required>
                    <option value="1" <?php echo (isset($_SESSION['reservation']) && $_SESSION['reservation']['guests'] == '1') ? 'selected' : ''; ?>>1 person</option>
                    <option value="2" <?php echo (isset($_SESSION['reservation']) && $_SESSION['reservation']['guests'] == '2') ? 'selected' : ''; ?>>2 people</option>
                    <option value="3" <?php echo (isset($_SESSION['reservation']) && $_SESSION['reservation']['guests'] == '3') ? 'selected' : ''; ?>>3 people</option>
                    <option value="4" <?php echo (isset($_SESSION['reservation']) && $_SESSION['reservation']['guests'] == '4') ? 'selected' : ''; ?>>4 people</option>
                    <option value="5" <?php echo (isset($_SESSION['reservation']) && $_SESSION['reservation']['guests'] == '5') ? 'selected' : ''; ?>>5+ people</option>
                </select>
            </div>
            <div class="form-group">
                <label for="special-requests">Special Requests</label>
                <textarea id="special-requests" name="special-requests" rows="3"><?php echo isset($_SESSION['reservation']) ? htmlspecialchars($_SESSION['reservation']['requests']) : ''; ?></textarea>
            </div>
            <button type="submit" class="cta-button">Book Table</button>
        </form>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <div class="logo">Gourmet Restaurant</div>
            <p>123 Culinary Avenue, Foodville, FC 12345</p>
            <p>Open Tuesday-Sunday, 5pm-11pm</p>
            <div class="social-links">
                <a href="#"><i>📱</i></a>
                <a href="#"><i>📸</i></a>
                <a href="#"><i>🍽️</i></a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> Gourmet Restaurant. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Theme Toggle - Fixed Version
        document.getElementById('themeToggle').addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            // Update immediately
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Send to server
            fetch(window.location.pathname + '?toggle_theme=1', {
                method: 'GET'
            }).catch(err => {
                console.log('Theme preference saved locally');
            });
        });

        // Menu Filter Functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(btn => 
                    btn.classList.remove('active'));
                button.classList.add('active');
                
                const filter = button.dataset.category;
                document.querySelectorAll('.menu-item').forEach(item => {
                    item.style.display = (filter === 'all' || item.dataset.category === filter) 
                        ? 'block' : 'none';
                });
            });
        });

        // Scroll Animations
        const animateOnScroll = () => {
            document.querySelectorAll('.timeline-item').forEach((item, index) => {
                if (item.getBoundingClientRect().top < window.innerHeight / 1.3) {
                    item.style.animationDelay = `${index * 0.2}s`;
                    item.style.opacity = '1';
                }
            });
        };
        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll();

        // Smooth Scrolling for Navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Initialize theme from localStorage
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || '<?php echo $currentTheme; ?>';
            document.documentElement.setAttribute('data-theme', savedTheme);
        });
    </script>
</body>
</html>
<?php
// Close database connection if it exists
if (isset($conn)) {
    $conn->close();
}
?>