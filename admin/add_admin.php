<?php
session_start();
require_once '../config.php'; // Adjust the path to config.php if necessary

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
    $user_type = $_POST['user_type'];
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
            $upload_dir = "../uploads/profilephoto/"; // Upload directory outside the admin folder
            
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
                    $success = "Registration successful! You can now <a href='login.php'>login</a>.";
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
    <title>User Registration | Kitchen Accessories Stores s</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5e72e4;
            --secondary-color: #f7fafc;
            --accent-color: #2dce89;
            --text-color: #525f7f;
            --light-gray: #e9ecef;
        }
        
        body {
            background: linear-gradient(120deg, #f6f9fc 0%, #eef1f5 100%);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
        }
        
        .form-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, #825ee4 100%);
            padding: 25px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .form-header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.05),
                rgba(255, 255, 255, 0.05) 10px,
                rgba(255, 255, 255, 0) 10px,
                rgba(255, 255, 255, 0) 20px
            );
            transform: rotate(30deg);
        }
        
        .form-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
            position: relative;
        }
        
        .form-subtitle {
            font-size: 1rem;
            opacity: 0.8;
            margin-top: 10px;
        }
        
        .form-body {
            padding: 30px 40px;
        }
        
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
            background-color: var(--light-gray);
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .profile-container:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.15);
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
            background: linear-gradient(135deg, var(--accent-color) 0%, #2dcebb 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .image-upload-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        
        #image-upload {
            display: none;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-floating > label {
            color: #8898aa;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            padding: 12px 15px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
            background-color: #fff;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(94, 114, 228, 0.1);
        }
        
        .input-group-text {
            border-radius: 0 8px 8px 0;
            background-color: var(--light-gray);
            border: 1px solid var(--light-gray);
            transition: all 0.3s ease;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--primary-color) 0%, #825ee4 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
            filter: brightness(1.1);
        }
        
        .btn-outline-secondary {
            border-color: var(--light-gray);
            color: #8898aa;
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--light-gray);
            border-color: var(--light-gray);
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .alert-danger {
            background-color: #ffefef;
            border-color: #ffefef;
            color: #e62a45;
        }
        
        .alert-success {
            background-color: #e6fff3;
            border-color: #e6fff3;
            color: #2dce89;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            color: #825ee4;
        }
        
        .form-text {
            color: #8898aa;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .input-group-text {
            cursor: pointer;
        }
        
        /* Field groups for better layout */
        .field-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .field-group {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .form-body {
                padding: 20px;
            }
        }
        
        /* Custom tooltip */
        .tooltip-icon {
            color: #8898aa;
            font-size: 0.9rem;
            margin-left: 5px;
            cursor: help;
        }
        
        .custom-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .custom-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        .tooltip-text {
            visibility: hidden;
            position: absolute;
            width: 200px;
            background-color: #32325d;
            color: white;
            text-align: center;
            border-radius: 5px;
            padding: 5px 10px;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
        }
        
        .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #32325d transparent transparent transparent;
        }
    </style>
</head>
<body>
<?php include "navbar.php"?>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2 class="form-title">Kitchen Accessories Stores s</h2>
                <p class="form-subtitle">Create your account to join our community</p>
            </div>
            
            <div class="form-body">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <!-- Profile Image Upload -->
                    <div class="image-upload-wrapper mb-4">
                        <div class="profile-container">
                            <img src="../uploads/profilephoto/default-avatar.png" alt="Profile" class="preview-image" id="profile-preview">
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
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="address_line1" class="form-label">Address Line 1</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-home"></i></span>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo isset($_POST['address_line1']) ? htmlspecialchars($_POST['address_line1']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="field-group mb-4">
                        <div>
                            <label for="city" class="form-label">City</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-city"></i></span>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="state" class="form-label">State</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="postal_code" class="form-label">Postal Code</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-map-pin"></i></span>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>" required>
                        </div>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>Enter your postal code and we'll auto-fill city and state information if available.</div>
                    </div>
                    
                    <div class="mb-4" style="display: none;"> <!-- Hide the entire dropdown -->
                        <label for="user_type" class="form-label">User Type</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-users-cog"></i></span>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="admin" selected>Admin</option> <!-- Set "admin" as default -->
                                <option value="user">User</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text"><i class="fas fa-shield-alt me-1"></i>Password must be at least 8 characters long</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                    
                    <div class="login-link">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview the selected image before upload
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                confirmPasswordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Auto-fill city and state based on postal code
        document.getElementById('postal_code').addEventListener('blur', function() {
            const postalCode = this.value;
            if (postalCode.length > 0) {
                fetch(`https://api.zippopotam.us/in/${postalCode}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.places && data.places.length > 0) {
                            const place = data.places[0];
                            document.getElementById('city').value = place['place name'];
                            document.getElementById('state').value = place['state'];
                        }
                    })
                    .catch(error => console.error('Error fetching location data:', error));
            }
        });
    </script>
</body>
</html>