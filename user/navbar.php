<?php
// Start output buffering at the very beginning
if (!ob_get_level()) {
    ob_start();
}

// Handle session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config after session start
require_once '../config.php';

// Set default auto logout time (5 minutes) if not set
if (!isset($_SESSION['auto_logout_time'])) {
    $_SESSION['auto_logout_time'] = 5; // in minutes
}

// Check for inactivity and auto logout
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    $auto_logout_seconds = $_SESSION['auto_logout_time'] * 60;
    
    if ($inactive_time > $auto_logout_seconds) {
        // Clear session and redirect to login with timeout message
        $_SESSION = array();
        session_destroy();
        
        // Clear buffer and redirect
        while (ob_get_level()) {
            ob_end_clean();
        }
        header("Location: ../login.php?timeout=1");
        exit;
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Handle logout functionality
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    
    // Clear buffer and redirect
    while (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: ../login.php");
    exit;
}

// Handle auto logout time update
if (isset($_POST['update_auto_logout'])) {
    $new_time = (int)$_POST['auto_logout_time'];
    if ($new_time >= 1 && $new_time <= 120) { // Limit between 1-120 minutes
        $_SESSION['auto_logout_time'] = $new_time;
        $_SESSION['last_activity'] = time(); // Reset activity timer
    }
    
    // Clear buffer and redirect to avoid form resubmission
    while (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    // Clear buffer and redirect
    while (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: ../login.php");
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fix the profile image path
$profile_image = "../uploads/profilephoto/default-avatar.png";
if (!empty($user['profile_image'])) {
    if (strpos($user['profile_image'], 'uploads/profilephoto/') !== false) {
        $profile_image = "../" . $user['profile_image'];
    } else {
        $profile_image = "../uploads/profilephoto/" . $user['profile_image'];
    }
    
    if (!file_exists($profile_image)) {
        $profile_image = "../uploads/profilephoto/default-avatar.png";
    }
}

// Count items in cart
$cart_count = 0;
$cart_query = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE c.user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();
$cart_data = $cart_result->fetch_assoc();
$cart_count = $cart_data['total'] ?? 0;

// Fetch categories from database
$categories = [];
$category_query = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name");
if ($category_query && $category_query->num_rows > 0) {
    while ($category = $category_query->fetch_assoc()) {
        $categories[] = $category;
    }
}

// Fetch materials from database
$materials = [];
$material_query = $conn->query("SELECT id, material_name FROM materials ORDER BY material_name");
if ($material_query && $material_query->num_rows > 0) {
    while ($material = $material_query->fetch_assoc()) {
        $materials[] = $material;
    }
}

// Calculate remaining time before auto logout (in seconds)
$remaining_time = ($_SESSION['auto_logout_time'] * 60) - (time() - $_SESSION['last_activity']);

// Flush output buffer before HTML
while (ob_get_level() > 1) {
    ob_end_flush();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Accessories Stores | Your Modern Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Prevent caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <style>
        :root {
            --primary-color: #5D3FD3;
            --secondary-color: #f39c12;
            --dark-color: #222831;
            --light-color: #f5f5f5;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #8a65ff);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.1rem;
            color: #fff !important;
        }
        
        .nav-link {
            font-weight: 500;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85) !important;
            padding: 0.4rem 0.8rem !important;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover {
            color: #fff !important;
            transform: translateY(-1px);
        }
        
        .navbar-profile-image {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            margin-right: 10px;
            transition: transform 0.2s ease;
        }
        
        .navbar-profile-image:hover {
            transform: scale(1.1);
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-icon {
            font-size: 1.1rem;
            color: white;
            padding: 0.3rem;
            transition: transform 0.2s ease;
        }
        
        .nav-icon:hover {
            transform: translateY(-2px);
        }
        
        .logout-btn {
            background-color: rgba(255, 255, 255, 0.1);
            color: white !important;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        
        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        .username-display {
            font-size: 0.9rem;
            margin-right: 10px;
        }
        
        .profile-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white !important;
            transition: all 0.2s ease;
        }
        
        .profile-link:hover {
            transform: translateY(-1px);
        }
        
        /* Dropdown selector styles */
        .nav-selector {
            width: 160px;
            margin-left: 15px;
        }
        
        .nav-selector select {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            width: 100%;
            height: 32px;
        }
        
        .nav-selector select:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .nav-selector select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
        }
        
        .nav-selector option {
            background-color: var(--primary-color);
            color: white;
        }
        
        .dropdown-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        /* Settings Modal Styles */
        .settings-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .settings-modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 500px;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .settings-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .settings-modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .close-settings {
            color: #aaa;
            font-size: 1.8rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .close-settings:hover {
            color: #333;
        }
        
        .auto-logout-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(93, 63, 211, 0.2);
            outline: none;
        }
        
        .btn-save {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            background-color: #4a2db3;
            transform: translateY(-2px);
        }
        
        .inactivity-warning {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            border-left: 5px solid var(--secondary-color);
        }
        
        @keyframes slideIn {
            from {transform: translateX(100%); opacity: 0;}
            to {transform: translateX(0); opacity: 1;}
        }
        
        .inactivity-warning.show {
            display: block;
        }
        
        .inactivity-warning p {
            margin: 0;
            font-size: 0.9rem;
        }
        
        .inactivity-warning .countdown {
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        @media (max-width: 992px) {
            .navbar-collapse {
                padding-top: 15px;
            }
            
            .d-flex {
                flex-direction: column;
                gap: 10px !important;
                align-items: flex-start !important;
                padding: 10px 0;
            }
            
            .logout-btn {
                margin-top: 10px;
            }
            
            .nav-selector {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
                max-width: none;
            }
            
            .dropdown-container {
                flex-direction: column;
                width: 100%;
                gap: 5px;
            }
            
            .settings-modal-content {
                margin: 20% auto;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="">
                <i class="fas fa-store me-1"></i>Kitchen Accessories Stores
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="fas fa-box me-1"></i>Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="deals.php"><i class="fas fa-tag me-1"></i>Deals</a>
                    </li>
                </ul>
                
                <!-- Dropdown Selectors -->
                <div class="dropdown-container">
                    <!-- Category Dropdown Selector -->
                    <div class="nav-selector">
                        <select id="categoryDropdown" onchange="navigateToCategory()">
                            <option value="">Browse Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Material Dropdown Selector -->
                    <div class="nav-selector">
                        <select id="materialDropdown" onchange="navigateToMaterial()">
                            <option value="">Browse Materials</option>
                            <?php foreach ($materials as $material): ?>
                                <option value="<?php echo htmlspecialchars($material['material_name']); ?>">
                                    <?php echo htmlspecialchars($material['material_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-3 ms-3">
                    <div class="position-relative">
                        <a href="cart.php" class="nav-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="position-relative">
                        <a href="orders.php" class="nav-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </a>
                    </div>
                    
                    <button id="settingsBtn" class="nav-icon" style="background: none; border: none;">
                        <i class="fas fa-cog"></i>
                    </button>
                    
                    <a href="profile.php" class="profile-link">
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="navbar-profile-image">
                        <span class="username-display"><?php echo htmlspecialchars($user['username']); ?></span>
                    </a>
                    
                    <form method="get" action="">
                        <button type="submit" name="logout" class="logout-btn">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Settings Modal -->
    <div id="settingsModal" class="settings-modal">
        <div class="settings-modal-content">
            <div class="settings-modal-header">
                <h3 class="settings-modal-title"><i class="fas fa-cog me-2"></i>Session Settings</h3>
                <span class="close-settings">&times;</span>
            </div>
            <form method="post" class="auto-logout-form">
                <div class="form-group">
                    <label for="auto_logout_time">Auto Logout After Inactivity (minutes):</label>
                    <input type="number" id="auto_logout_time" name="auto_logout_time" 
                           class="form-control" min="1" max="120" 
                           value="<?php echo $_SESSION['auto_logout_time']; ?>">
                    <small class="text-muted">Set how many minutes of inactivity before automatic logout (1-120 minutes)</small>
                </div>
                <button type="submit" name="update_auto_logout" class="btn-save">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Inactivity Warning -->
    <div id="inactivityWarning" class="inactivity-warning">
        <p><i class="fas fa-exclamation-triangle me-2"></i>You will be logged out due to inactivity in <span id="logoutCountdown" class="countdown"></span> seconds.</p>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navigation functions
        function navigateToCategory() {
            const categoryDropdown = document.getElementById('categoryDropdown');
            const selectedCategory = categoryDropdown.value;
            
            if (selectedCategory) {
                window.location.href = `category_products.php?category=${encodeURIComponent(selectedCategory)}`;
            }
            
            categoryDropdown.selectedIndex = 0;
        }
        
        function navigateToMaterial() {
            const materialDropdown = document.getElementById('materialDropdown');
            const selectedMaterial = materialDropdown.value;
            
            if (selectedMaterial) {
                window.location.href = `material_products.php?material=${encodeURIComponent(selectedMaterial)}`;
            }
            
            materialDropdown.selectedIndex = 0;
        }
        
        // Settings Modal functionality
        const settingsModal = document.getElementById('settingsModal');
        const settingsBtn = document.getElementById('settingsBtn');
        const closeSettings = document.querySelector('.close-settings');
        
        settingsBtn.onclick = function() {
            settingsModal.style.display = 'block';
        }
        
        closeSettings.onclick = function() {
            settingsModal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == settingsModal) {
                settingsModal.style.display = 'none';
            }
        }
        
        // Auto logout countdown functionality
        let remainingTime = <?php echo $remaining_time; ?>;
        const warningThreshold = 60; // Show warning 60 seconds before logout
        const warningElement = document.getElementById('inactivityWarning');
        const countdownElement = document.getElementById('logoutCountdown');
        
        function updateInactivityTimer() {
            remainingTime--;
            
            // Show warning if time is below threshold
            if (remainingTime <= warningThreshold) {
                warningElement.classList.add('show');
                countdownElement.textContent = remainingTime;
            }
            
            // Logout when time reaches 0
            if (remainingTime <= 0) {
                window.location.href = '../login.php?timeout=1';
            }
        }
        
        // Update timer every second
        const inactivityTimer = setInterval(updateInactivityTimer, 1000);
        
        // Reset timer on user activity
        function resetInactivityTimer() {
            remainingTime = <?php echo $_SESSION['auto_logout_time'] * 60; ?>;
            warningElement.classList.remove('show');
        }
        
        // Listen for user activity
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keypress', resetInactivityTimer);
        document.addEventListener('click', resetInactivityTimer);
        document.addEventListener('scroll', resetInactivityTimer);
        
        // Prevent back button navigation after logout
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
    </script>
</body>
</html>