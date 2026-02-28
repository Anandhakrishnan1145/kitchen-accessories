<?php
require_once '../config.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check admin access
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle logout request
if (isset($_POST['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Redirect to login page with no-cache headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Location: ../login.php");
    exit;
}

// Handle theme preference changes
if (isset($_POST['update_theme'])) {
    $theme = $_POST['theme'] ?? 'light';
    $color_scheme = $_POST['color_scheme'] ?? 'default';
    $font_size = $_POST['font_size'] ?? 'medium';
    $auto_logout = $_POST['auto_logout'] ?? 30;
    
    try {
        $stmt = $conn->prepare("UPDATE users SET theme_preference = ?, color_scheme = ?, font_size = ?, auto_logout = ? WHERE id = ?");
        $stmt->bind_param("sssii", $theme, $color_scheme, $font_size, $auto_logout, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        // Update session with new preferences
        $_SESSION['theme_preference'] = $theme;
        $_SESSION['color_scheme'] = $color_scheme;
        $_SESSION['font_size'] = $font_size;
        $_SESSION['auto_logout'] = $auto_logout;
        
        // Redirect to avoid form resubmission
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        error_log("Theme update error: " . $e->getMessage());
    }
}

// Set default values
$username = 'Admin';
$profile_image = "../uploads/profilephoto/default-avatar.png";
$theme_preference = 'light';
$color_scheme = 'default';
$font_size = 'medium';
$auto_logout = 30; // minutes

// Database operations with error handling
try {
    // Check if connection exists and is alive
    if (isset($conn)) {
        // Test the connection
        if (!$conn->ping()) {
            // Try to reconnect if possible
            if (file_exists('../config.php')) {
                require_once '../config.php';
            }
        }
        
        // If connection is available
        if ($conn && $conn->ping()) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT username, profile_image, theme_preference, color_scheme, font_size, auto_logout FROM users WHERE id = ?");
            
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $username = htmlspecialchars($user['username'] ?? 'Admin');
                    
                    // Handle profile image
                    if (!empty($user['profile_image'])) {
                        $img_path = strpos($user['profile_image'], 'uploads/profilephoto/') !== false 
                            ? "../" . $user['profile_image'] 
                            : "../uploads/profilephoto/" . $user['profile_image'];
                        
                        if (file_exists($img_path)) {
                            $profile_image = $img_path;
                        }
                    }
                    
                    // Get theme preferences
                    $theme_preference = $user['theme_preference'] ?? 'light';
                    $color_scheme = $user['color_scheme'] ?? 'default';
                    $font_size = $user['font_size'] ?? 'medium';
                    $auto_logout = $user['auto_logout'] ?? 30;
                    
                    // Store in session
                    $_SESSION['theme_preference'] = $theme_preference;
                    $_SESSION['color_scheme'] = $color_scheme;
                    $_SESSION['font_size'] = $font_size;
                    $_SESSION['auto_logout'] = $auto_logout;
                }
                $stmt->close();
            }
        }
    }
} catch (Exception $e) {
    // Log error but continue with default values
    error_log("Navbar database error: " . $e->getMessage());
}

// Set theme class for body
$body_class = "theme-{$theme_preference} color-{$color_scheme} font-{$font_size}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        :root {
            /* Default light theme colors */
            --primary-color: #6c63ff;
            --secondary-color: #4d44db;
            --dark-color: #222831;
            --light-color: #f5f5f5;
            --accent: #8A65FF;
            --accent-light: #A98FFF;
            --text-light: #f5f5f5;
            --text-dark: #222831;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #333333;
            --border-color: #dee2e6;
        }

        /* Dark theme colors */
        .theme-dark {
            --primary-color: #7c73ff;
            --secondary-color: #5d54eb;
            --dark-color: #121212;
            --light-color: #1e1e1e;
            --accent: #9a75ff;
            --accent-light: #b995ff;
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e0e0e0;
            --border-color: #333333;
        }

        /* Color schemes */
        .color-blue {
            --primary-color: #4285f4;
            --secondary-color: #3367d6;
            --accent: #5a95f5;
            --accent-light: #7aa7f7;
        }

        .color-green {
            --primary-color: #34a853;
            --secondary-color: #2d8e49;
            --accent: #4caf50;
            --accent-light: #66bb6a;
        }

        .color-red {
            --primary-color: #ea4335;
            --secondary-color: #d33426;
            --accent: #f44336;
            --accent-light: #ef5350;
        }

        /* Font sizes */
        .font-small {
            font-size: 0.9rem;
        }

        .font-medium {
            font-size: 1rem;
        }

        .font-large {
            font-size: 1.1rem;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s, color 0.3s;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 15px rgba(0,0,0,0.15);
            padding: 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--light-color) !important;
            font-size: 1.3rem;
            padding-left: 1rem;
        }

        .navbar-toggler {
            border: none;
            color: var(--light-color);
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            color: var(--light-color) !important;
            font-weight: 500;
            padding: 1rem 1.25rem !important;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-link:hover, 
        .nav-link:focus {
            background-color: rgba(255,255,255,0.1);
            border-bottom: 3px solid var(--secondary-color);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            margin-top: 0;
            min-width: 220px;
            padding: 0.5rem 0;
            display: block;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0.3s, opacity 0.3s ease;
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        /* Show dropdown on hover */
        .dropdown:hover .dropdown-menu {
            display: block;
            visibility: visible;
            opacity: 1;
        }

        .dropdown-item {
            padding: 0.75rem 1.5rem;
            color: var(--text-color);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: rgba(108, 99, 255, 0.1);
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0 1rem;
        }

        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--secondary-color);
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.1);
            border-color: var(--accent-light);
        }

        .username {
            font-weight: 600;
            color: var(--light-color);
            margin-right: 1rem;
        }

        .logout-btn {
            background: none;
            border: 1px solid rgba(255,255,255,0.3);
            color: var(--light-color);
            border-radius: 20px;
            padding: 0.25rem 1rem;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--light-color);
        }

        /* Theme settings dropdown */
        .theme-settings {
            max-width: 300px;
            padding: 1rem;
        }

        .theme-option {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .theme-option:hover {
            background-color: rgba(108, 99, 255, 0.1);
        }

        .theme-option.active {
            background-color: rgba(108, 99, 255, 0.2);
            font-weight: bold;
        }

        .theme-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            border: 1px solid var(--border-color);
        }

        /* Animations and effects */
        .dropdown-menu {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Make dropdown full width in mobile */
        @media (max-width: 991.98px) {
            .dropdown-menu {
                width: 100%;
                background-color: rgba(236, 240, 241, 0.05);
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                display: none;
                visibility: visible;
                opacity: 1;
            }
            
            /* In mobile view, use Bootstrap's default dropdown behavior */
            .dropdown:hover .dropdown-menu {
                display: none;
            }
            
            .dropdown.show .dropdown-menu {
                display: block;
            }
            
            .dropdown-item {
                color: var(--light-color);
                padding-left: 2.5rem;
                border-left: 3px solid transparent;
            }
            
            .dropdown-item:hover {
                background-color: rgba(255,255,255,0.1);
                color: white;
                border-left: 3px solid var(--secondary-color);
                transform: none;
            }
            
            .nav-link:hover, 
            .nav-link:focus {
                border-bottom: 3px solid transparent;
            }
            
            .username {
                display: none;
            }
            
            .user-profile {
                padding: 1rem;
                border-top: 1px solid rgba(255,255,255,0.1);
                margin-top: 1rem;
                justify-content: space-between;
            }
        }
        
        /* Make main content area responsive */
        main {
            margin-top: 76px;
            padding: 2rem;
            transition: all 0.3s ease;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        @media (max-width: 767.98px) {
            main {
                padding: 1rem;
            }
        }

        /* Logout warning modal styles */
        .logout-modal .modal-content {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .logout-modal .modal-header {
            border-bottom: 1px solid var(--border-color);
        }
        
        .logout-modal .modal-footer {
            border-top: 1px solid var(--border-color);
        }
        
        .logout-timer {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <header class="admin-header fixed-top">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="admin_dashboard.php">
                    Kitchen Accessories
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="bi bi-list text-light fs-4"></i>
                </button>
                
                <div class="collapse navbar-collapse" id="adminNavbar">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Products
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="add_product.php">Add Product</a></li>
                                <li><a class="dropdown-item" href="products.php">View Products</a></li>
                                <li><a class="dropdown-item" href="admin_products.php">Show Product</a></li>
                                <li><a class="dropdown-item" href="reorder_products.php">Reorder</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Orders
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="admin_orders.php">New Orders</a></li>
                                <li><a class="dropdown-item" href="user_management.php">Customers</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Reports
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="gst.php">GST</a></li>
                                <li><a class="dropdown-item" href="profit.php">Profit/Loss</a></li>
                                <li><a class="dropdown-item" href="categories.php">Categories Sales</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Onsite Sales
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="billing.php">Billing</a></li>
                                <li><a class="dropdown-item" href="sales_report.php">Sales</a></li>
                                <li><a class="dropdown-item" href="canceled_bills.php">Canceled</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                My Profile
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="add_admin.php">Add Admin</a></li>
                                <li><a class="dropdown-item" href="agency_registration.php">Add Agency</a></li>
                            </ul>
                        </li>
                    </ul>
                    
                    <div class="user-profile d-flex align-items-center">
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="themeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-palette-fill me-2" style="color: var(--light-color);"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end theme-settings" aria-labelledby="themeDropdown">
                                <li>
                                    <form method="post" action="">
                                        <h6 class="dropdown-header">Theme Settings</h6>
                                        
                                        <div class="mb-3 px-3">
                                            <label class="form-label">Theme Mode</label>
                                            <div class="d-flex gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="theme" id="lightTheme" value="light" <?php echo $theme_preference === 'light' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="lightTheme">Light</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="theme" id="darkTheme" value="dark" <?php echo $theme_preference === 'dark' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="darkTheme">Dark</label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 px-3">
                                            <label class="form-label">Color Scheme</label>
                                            <div class="d-flex gap-3 flex-wrap">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="color_scheme" id="defaultColor" value="default" <?php echo $color_scheme === 'default' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="defaultColor">Default</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="color_scheme" id="blueColor" value="blue" <?php echo $color_scheme === 'blue' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="blueColor">Blue</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="color_scheme" id="greenColor" value="green" <?php echo $color_scheme === 'green' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="greenColor">Green</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="color_scheme" id="redColor" value="red" <?php echo $color_scheme === 'red' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="redColor">Red</label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 px-3">
                                            <label class="form-label">Font Size</label>
                                            <div class="d-flex gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="font_size" id="smallFont" value="small" <?php echo $font_size === 'small' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="smallFont">Small</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="font_size" id="mediumFont" value="medium" <?php echo $font_size === 'medium' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="mediumFont">Medium</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="font_size" id="largeFont" value="large" <?php echo $font_size === 'large' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="largeFont">Large</label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 px-3">
                                            <label for="autoLogout" class="form-label">Auto Logout (minutes)</label>
                                            <input type="number" class="form-control" id="autoLogout" name="auto_logout" min="1" max="120" value="<?php echo $auto_logout; ?>">
                                        </div>
                                        
                                        <div class="d-grid px-3">
                                            <button type="submit" name="update_theme" class="btn btn-primary">Save Settings</button>
                                        </div>
                                    </form>
                                </li>
                            </ul>
                        </div>
                        
                        <a href="myprofile.php" class="d-flex align-items-center text-decoration-none">
                            <img src="<?php echo $profile_image; ?>" 
                                 alt="Profile" 
                                 class="profile-image"
                                 onerror="this.src='../uploads/profilephoto/default-avatar.png'">
                            <span class="username ms-2"><?php echo $username; ?></span>
                        </a>
                        
                        <form method="post" action="" class="ms-3">
                            <button type="submit" name="logout" class="logout-btn">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <!-- Page content will be inserted here -->
    </main>

    <!-- Logout Warning Modal -->
    <div class="modal fade logout-modal" id="logoutWarningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Session Timeout Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Your session is about to expire due to inactivity. You will be automatically logged out in <span class="logout-timer">60</span> seconds.</p>
                    <p>Move your mouse or press any key to continue your session.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="continueSessionBtn">Continue Session</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto logout functionality with warning
        let idleTimer;
        let warningTimer;
        let countdownTimer;
        const warningTime = 60000; // 1 minute warning before logout (in milliseconds)
        
        // Get auto logout time from PHP (in minutes) and convert to milliseconds
        const autoLogoutTime = <?php echo $auto_logout; ?> * 60 * 1000;
        const logoutTime = autoLogoutTime - warningTime; // Time when warning should appear
        
        function startTimers() {
            // Clear any existing timers
            resetTimers();
            
            // Set the warning timer (fires 1 minute before logout)
            warningTimer = setTimeout(showLogoutWarning, logoutTime);
            
            // Set the main logout timer
            idleTimer = setTimeout(logoutUser, autoLogoutTime);
        }
        
        function resetTimers() {
            // Clear all timers
            clearTimeout(idleTimer);
            clearTimeout(warningTimer);
            clearInterval(countdownTimer);
            
            // Hide the warning modal if it's open
            const modal = bootstrap.Modal.getInstance(document.getElementById('logoutWarningModal'));
            if (modal) {
                modal.hide();
            }
            
            // Restart the timers
            startTimers();
        }
        
        function showLogoutWarning() {
            // Show the warning modal
            const modal = new bootstrap.Modal(document.getElementById('logoutWarningModal'));
            modal.show();
            
            // Start countdown in the modal (60 seconds)
            let secondsLeft = 60;
            document.querySelector('.logout-timer').textContent = secondsLeft;
            
            countdownTimer = setInterval(function() {
                secondsLeft--;
                document.querySelector('.logout-timer').textContent = secondsLeft;
                
                if (secondsLeft <= 0) {
                    clearInterval(countdownTimer);
                    logoutUser();
                }
            }, 1000);
        }
        
        function logoutUser() {
            // Perform logout via AJAX to ensure session is properly destroyed
            fetch('logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'logout=true'
            }).then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    window.location.href = '../login.php';
                }
            });
        }
        
        // Reset timers on any user activity
        ['mousemove', 'keydown', 'scroll', 'click', 'touchstart', 'mousedown'].forEach(event => {
            document.addEventListener(event, resetTimers);
        });
        
        // Continue session button handler
        document.getElementById('continueSessionBtn')?.addEventListener('click', function() {
            // Send a request to refresh the session
            fetch('refresh_session.php')
                .then(response => resetTimers());
        });
        
        // Initialize timers when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startTimers();
            
            // Add active class to current nav item based on URL
            const currentPath = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link, .dropdown-item');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (linkHref === currentPath) {
                    link.classList.add('active');
                    // If it's a dropdown item, also activate its parent
                    if (link.classList.contains('dropdown-item')) {
                        const parentDropdown = link.closest('.dropdown');
                        if (parentDropdown) {
                            const parentLink = parentDropdown.querySelector('.nav-link');
                            if (parentLink) parentLink.classList.add('active');
                        }
                    }
                }
            });
            
            // For mobile: Ensure bootstrap's click behavior still works
            if (window.innerWidth < 992) {
                const dropdownToggleLinks = document.querySelectorAll('.dropdown-toggle');
                dropdownToggleLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const dropdown = this.closest('.dropdown');
                        dropdown.classList.toggle('show');
                        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
                        dropdownMenu.classList.toggle('show');
                    });
                });
            }
            
            // Prevent back button after logout
            window.history.pushState(null, null, window.location.href);
            window.onpopstate = function() {
                window.history.go(1);
            };
        });
    </script>
</body>
</html>