<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone, address_line1, city, state, postal_code, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fix the profile image path
$profile_image = "../uploads/profilephoto/default-avatar.png"; // Default image
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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address_line1 = $_POST['address_line1'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $postal_code = $_POST['postal_code'];

    // Update user details in the database
    $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address_line1 = ?, city = ?, state = ?, postal_code = ? WHERE id = ?");
    $update_stmt->bind_param("sssssssi", $username, $email, $phone, $address_line1, $city, $state, $postal_code, $user_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows > 0) {
        $success_message = "Profile updated successfully!";
        // Update session username if changed
        $_SESSION['username'] = $username;
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Accessories Stores s | Your Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #5D3FD3;
            --secondary-color: #f39c12;
            --dark-color: #222831;
            --light-color: #f5f5f5;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-info {
            margin-left: 25px;
        }
        
        .profile-name {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-email {
            color: #666;
            font-size: 1rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.6rem 1rem;
            border: 1px solid #e0e0e0;
            box-shadow: none;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(93, 63, 211, 0.15);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #4a32a8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(93, 63, 211, 0.2);
        }
        
        /* Toast for messages */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .toast {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .toast-success .toast-header {
            background-color: var(--success-color);
            color: white;
        }
        
        .toast-error .toast-header {
            background-color: var(--danger-color);
            color: white;
        }
        
        .back-btn {
            margin-bottom: 20px;
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Notification Toasts -->
    <?php if(isset($success_message) || isset($error_message)): ?>
    <div class="toast-container">
        <?php if(isset($success_message)): ?>
        <div class="toast toast-success show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto"><i class="fas fa-check-circle me-2"></i>Success</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
        <div class="toast toast-error show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto"><i class="fas fa-exclamation-circle me-2"></i>Error</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Back Button -->
    <div class="container mt-3">
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left me-2"></i>Back to Home
        </a>
    </div>

    <!-- Profile Section -->
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Photo" class="profile-image" onerror="this.src='../uploads/profilephoto/default-avatar.png'">
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label"><i class="fas fa-user me-2"></i>Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label"><i class="fas fa-phone me-2"></i>Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="address_line1" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Address Line 1</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($user['address_line1']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="city" class="form-label"><i class="fas fa-city me-2"></i>City</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="state" class="form-label"><i class="fas fa-map me-2"></i>State</label>
                        <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="postal_code" class="form-label"><i class="fas fa-mail-bulk me-2"></i>Postal Code</label>
                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code']); ?>" required>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close toasts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                setTimeout(function() {
                    var bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
        });
    </script>
</body>
</html>