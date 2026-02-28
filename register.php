<?php
session_start();
require_once 'config.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address_line1 = trim($_POST['address_line1']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postal_code = trim($_POST['postal_code']);
    $user_type = $_POST['user_type'] ?? 'user'; // Default to 'user' if not set
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($phone) || empty($address_line1) || empty($city) || empty($state) || empty($postal_code) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Handle image upload
            $upload_dir = "uploads/profilephoto/";
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $profile_image_path = ""; // Default empty path
            
            if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
                $allowed_types = array("jpg", "jpeg", "png", "gif");
                $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
                
                if (in_array($file_extension, $allowed_types)) {
                    // Generate unique filename
                    $new_file_name = uniqid('profile_') . '.' . $file_extension;
                    $target_file = $upload_dir . $new_file_name;
                    
                    // Check if upload directory is writable
                    if (is_writable($upload_dir)) {
                        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                            // Store relative path in database (without leading slash)
                            $profile_image_path = "uploads/profilephoto/" . $new_file_name;
                        } else {
                            $error = "Error uploading file. Error code: " . $_FILES["profile_image"]["error"];
                        }
                    } else {
                        $error = "Upload directory is not writable. Please check permissions.";
                    }
                } else {
                    $error = "Only JPG, JPEG, PNG & GIF files are allowed";
                }
            } else {
                // If no image uploaded, use default avatar
                $profile_image_path = "uploads/profilephoto/default-avatar.png";
            }
            
            if (empty($error)) {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user data
                $stmt = $conn->prepare("INSERT INTO users (username, email, phone, address_line1, city, state, postal_code, user_type, password, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $username, $email, $phone, $address_line1, $city, $state, $postal_code, $user_type, $hashed_password, $profile_image_path);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! ";
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Kitchen Accessories Stores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b6b;
            --secondary-color: #4ecdc4;
            --accent-color: #ffd166;
            --dark-color: #2d3436;
            --light-color: #f8f9fa;
            --gradient-primary: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
            --gradient-secondary: linear-gradient(135deg, #4ecdc4 0%, #2cbeb5 100%);
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }
        
        body {
            background: url('https://images.unsplash.com/photo-1556910103-1c02745aae4d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Montserrat', sans-serif;
            padding: 30px 0;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(45, 52, 54, 0.7);
            backdrop-filter: blur(5px);
            z-index: -1;
        }
        
        .container {
            position: relative;
            z-index: 10;
        }
        
        .form-container {
            max-width: 850px;
            margin: 0 auto;
            padding: 0;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transform: perspective(1000px) rotateY(0deg);
            transition: transform 0.6s ease;
        }
        
        .form-container:hover {
            transform: perspective(1000px) rotateY(2deg);
        }
        
        /* Decorative elements */
        .decor-circle {
            position: absolute;
            border-radius: 50%;
            z-index: 0;
        }
        
        .decor-circle-1 {
            top: -50px;
            left: -50px;
            width: 200px;
            height: 200px;
            background: var(--gradient-primary);
            opacity: 0.2;
        }
        
        .decor-circle-2 {
            bottom: -80px;
            right: -80px;
            width: 250px;
            height: 250px;
            background: var(--gradient-secondary);
            opacity: 0.2;
        }
        
        .decor-circle-3 {
            top: 50%;
            left: -25px;
            width: 70px;
            height: 70px;
            background: var(--accent-color);
            opacity: 0.2;
        }
        
        .form-header {
            background: var(--gradient-primary);
            padding: 40px 30px;
            color: white;
            text-align: center;
            position: relative;
            z-index: 1;
            border-bottom: 5px solid var(--accent-color);
            clip-path: polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%);
            padding-bottom: 70px;
        }
        
        .form-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 20px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' opacity='.25' fill='%23ffffff'/%3E%3Cpath d='M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z' opacity='.5' fill='%23ffffff'/%3E%3Cpath d='M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z' fill='%23ffffff'/%3E%3C/svg%3E") no-repeat bottom center;
            background-size: cover;
        }
        
        .form-title {
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            position: relative;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .form-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--accent-color);
        }
        
        .form-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 20px;
            font-weight: 300;
        }
        
        .form-body {
            padding: 40px;
            position: relative;
            z-index: 1;
        }
        
        /* Profile image upload */
        .image-upload-wrapper {
            position: relative;
            width: 150px;
            margin: 0 auto 30px;
        }
        
        .profile-container {
            position: relative;
            border-radius: 50%;
            width: 150px;
            height: 150px;
            margin: 0 auto;
            background-color: rgba(241, 241, 241, 0.8);
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border: 4px solid white;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .profile-container:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }
        
        .image-upload-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--gradient-primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .image-upload-icon:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        #image-upload {
            display: none;
        }
        
        /* Form inputs */
        .input-group {
            position: relative;
            z-index: 1;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .form-control, .form-select {
            border: none;
            border-radius: 10px;
            padding: 14px 15px 14px 45px;
            background-color: #f8f9fa;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.2);
            background-color: white;
        }
        
        .input-group-text {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 45px;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            z-index: 10;
        }
        
        .toggle-password {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 45px;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            z-index: 10;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .toggle-password:hover {
            color: var(--dark-color);
        }
        
        /* Buttons */
        .btn-primary {
            border-radius: 30px;
            padding: 14px 20px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            background: var(--gradient-primary);
            border: none;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
            background: linear-gradient(135deg, #ff8e8e 0%, #ff6b6b 100%);
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary i {
            margin-right: 10px;
            transition: transform 0.3s ease;
        }
        
        .btn-primary:hover i {
            transform: translateX(3px);
        }
        
        /* Alerts */
        .alert {
            border-radius: 10px;
            margin-bottom: 25px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            position: relative;
            padding-left: 60px;
            padding-right: 20px;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 40px;
            border-radius: 10px 0 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .alert i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
        }
        
        .alert-danger {
            background-color: rgba(255, 205, 210, 0.3);
            color: #c62828;
            border-left: 3px solid #c62828;
        }
        
        .alert-danger::before {
            background-color: rgba(198, 40, 40, 0.1);
        }
        
        .alert-success {
            background-color: rgba(200, 230, 201, 0.3);
            color: #2e7d32;
            border-left: 3px solid #2e7d32;
        }
        
        .alert-success::before {
            background-color: rgba(46, 125, 50, 0.1);
        }
        
        /* Login link */
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            position: relative;
        }
        
        .login-link::before {
            content: "";
            position: absolute;
            top: 0;
            left: 25%;
            right: 25%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
        }
        
        .card-link {
            padding: 5px 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 600;
            color: var(--primary-color);
            position: relative;
            display: inline-block;
        }
        
        .card-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary-color);
            transform: scaleX(0);
            transform-origin: bottom right;
            transition: transform 0.3s ease;
        }
        
        .card-link:hover {
            color: var(--primary-color);
        }
        
        .card-link:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }
        
        .form-text {
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 8px;
        }
        
        /* Field groups for better layout */
        .field-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Responsive styling */
        @media (max-width: 768px) {
            .field-group {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .form-body {
                padding: 20px;
            }
            
            .form-container {
                margin: 0 15px;
                max-width: 100%;
            }
            
            .form-header {
                padding: 30px 20px 60px;
            }
        }
        
        /* Animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        /* Kitchen elements decoration */
        .kitchen-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: -1;
        }
        
        .kitchen-icon {
            position: absolute;
            opacity: 0.1;
            color: white;
            font-size: 2rem;
        }
        
        .kitchen-icon:nth-child(1) {
            top: 10%;
            left: 10%;
            animation: float 5s ease-in-out infinite;
        }
        
        .kitchen-icon:nth-child(2) {
            top: 20%;
            right: 5%;
            animation: float 7s ease-in-out infinite;
        }
        
        .kitchen-icon:nth-child(3) {
            bottom: 15%;
            left: 15%;
            animation: float 6s ease-in-out infinite;
        }
        
        .kitchen-icon:nth-child(4) {
            bottom: 10%;
            right: 10%;
            animation: float 8s ease-in-out infinite;
        }
        
        .kitchen-icon:nth-child(5) {
            top: 50%;
            left: 5%;
            animation: float 9s ease-in-out infinite;
        }
        
        .kitchen-icon:nth-child(6) {
            top: 40%;
            right: 15%;
            animation: float 5.5s ease-in-out infinite;
        }
        
        /* Input animations */
        .form-control:focus ~ .input-group-text,
        .form-select:focus ~ .input-group-text {
            color: var(--secondary-color);
            transform: scale(1.1);
        }
        
        /* Form validation styling */
        .form-control:valid,
        .form-select:valid {
            border-right: 3px solid var(--secondary-color);
        }
        
        .form-control:invalid:not(:placeholder-shown):not(:focus),
        .form-select:invalid:not(:placeholder-shown):not(:focus) {
            border-right: 3px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Kitchen themed floating icons -->
    <div class="kitchen-icons">
        <i class="fas fa-utensils kitchen-icon"></i>
        <i class="fas fa-blender kitchen-icon"></i>
        <i class="fas fa-mug-hot kitchen-icon"></i>
        <i class="fas fa-wine-glass kitchen-icon"></i>
        <i class="fas fa-pizza-slice kitchen-icon"></i>
        <i class="fas fa-mortar-pestle kitchen-icon"></i>
    </div>
    
    <div class="container">
        <div class="form-container">
            <!-- Decorative elements -->
            <div class="decor-circle decor-circle-1"></div>
            <div class="decor-circle decor-circle-2"></div>
            <div class="decor-circle decor-circle-3"></div>
            
            <div class="form-header">
                <h2 class="form-title">Kitchen Accessories Stores</h2>
                <p class="form-subtitle">Create your account to join our culinary community</p>
            </div>
            
            <div class="form-body">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <!-- Profile Image Upload -->
                    <div class="image-upload-wrapper mb-4">
                        <div class="profile-container">
                            <img src="uploads/profilephoto/default-avatar.png" alt="Profile" class="preview-image" id="profile-preview">
                        </div>
                        <div class="image-upload-icon" onclick="document.getElementById('image-upload').click();">
                            <i class="fas fa-camera"></i>
                        </div>
                        <input type="file" name="profile_image" id="image-upload" accept="image/*" onchange="previewImage(this);">
                    </div>
                    
                    <div class="mb-4">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" placeholder="Choose a unique username" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="Your email address" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="Your contact number" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="address_line1" class="form-label">Address Line 1</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-home"></i></span>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo isset($_POST['address_line1']) ? htmlspecialchars($_POST['address_line1']) : ''; ?>" placeholder="Street address" required>
                        </div>
                    </div>
                    
                    <div class="field-group mb-4">
                        <div>
                            <label for="city" class="form-label">City</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-city"></i></span>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" placeholder="Your city" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="state" class="form-label">State</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>" placeholder="Your state" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="postal_code" class="form-label">Postal Code</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-pin"></i></span>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>" placeholder="Your ZIP/postal code" required>
                        </div>
                       
                        </div>
                    
                    <div class="field-group mb-4">
                        <div>
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a strong password" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('password')">
                                    <i class="fas fa-eye" id="password-toggle-icon"></i>
                                </span>
                            </div>
                            <div class="form-text"><i class="fas fa-shield-alt me-1"></i>Minimum 8 characters</div>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                                    <i class="fas fa-eye" id="confirm-password-toggle-icon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                   <div class="mb-4" style="display: none;">
    <label for="user_type" class="form-label">Account Type</label>
    <div class="input-group">
        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
        <select class="form-select" id="user_type" name="user_type" required>
            <option value="user" selected>Customer</option>
            <option value="seller">Seller</option>
        </select>
    </div>
    <div class="form-text"><i class="fas fa-info-circle me-1"></i>Select "Seller" if you want to sell kitchen accessories on our platform.</div>
</div>

                    
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="card-link">Terms of Service</a> and <a href="#" class="card-link">Privacy Policy</a>
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </div>
                </form>
                
                <div class="login-link">
                    <p>Already have an account? <a href="login.php" class="card-link">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview uploaded profile image
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('profile-preview').setAttribute('src', e.target.result);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(inputId === 'password' ? 'password-toggle-icon' : 'confirm-password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Auto-fill city and state based on postal code
        document.getElementById('postal_code').addEventListener('blur', function() {
            const postalCode = this.value.trim();
            
            if (postalCode.length >= 5) {
                // This would be replaced with an actual API call in production
                // For demo purposes, we'll just simulate it with a setTimeout
                setTimeout(() => {
                    // Example data - this would come from an API in production
                    if (postalCode === '10001') {
                        document.getElementById('city').value = 'New York';
                        document.getElementById('state').value = 'NY';
                    } else if (postalCode === '90210') {
                        document.getElementById('city').value = 'Beverly Hills';
                        document.getElementById('state').value = 'CA';
                    }
                    // Add more examples or replace with actual API
                }, 500);
            }
        });
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match!');
            }
            
            if (password.length < 8) {
                event.preventDefault();
                alert('Password must be at least 8 characters long!');
            }
        });
    </script>
</body>
</html>