<?php
// Start session and check admin status
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Database Connection
require_once '../config.php';

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone, address_line1, city, state, postal_code, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Set profile image path
$profile_image = !empty($user['profile_image']) 
    ? (strpos($user['profile_image'], 'uploads/profilephoto/') !== false 
        ? "../" . $user['profile_image'] 
        : "../uploads/profilephoto/" . $user['profile_image'])
    : "../uploads/profilephoto/default-avatar.png";

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate inputs
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    
    // Process if no errors
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Handle file upload
            $new_image_path = $user['profile_image'];
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $_FILES['profile_image']['tmp_name']);
                
                if (in_array($mime_type, $allowed_types)) {
                    $upload_dir = "../uploads/profilephoto/";
                    $new_filename = uniqid('profile_') . '.' . pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                        // Delete old image if not default
                        if ($user['profile_image'] && $user['profile_image'] !== 'default-avatar.png') {
                            $old_image = strpos($user['profile_image'], 'uploads/profilephoto/') !== false 
                                ? "../" . $user['profile_image'] 
                                : "../uploads/profilephoto/" . $user['profile_image'];
                            if (file_exists($old_image)) unlink($old_image);
                        }
                        $new_image_path = "uploads/profilephoto/" . $new_filename;
                    }
                }
            }
            
            // Handle password update if provided
            $password_update = '';
            if (!empty($_POST['new_password'])) {
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    throw new Exception("Passwords do not match");
                }
                if (strlen($_POST['new_password']) < 8) {
                    throw new Exception("Password must be at least 8 characters");
                }
                $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $password_update = ", password = ?";
            }
            
            // Build and execute query
            $query = "UPDATE users SET username = ?, email = ?, phone = ?, address_line1 = ?, city = ?, state = ?, postal_code = ?, profile_image = ?" . $password_update . " WHERE id = ?";
            $stmt = $conn->prepare($query);
            
            if ($password_update) {
                $stmt->bind_param("sssssssssi", 
                    $username, 
                    $email, 
                    $_POST['phone'], 
                    $_POST['address_line1'], 
                    $_POST['city'], 
                    $_POST['state'], 
                    $_POST['postal_code'], 
                    $new_image_path,
                    $hashed_password,
                    $user_id
                );
            } else {
                $stmt->bind_param("ssssssssi", 
                    $username, 
                    $email, 
                    $_POST['phone'], 
                    $_POST['address_line1'], 
                    $_POST['city'], 
                    $_POST['state'], 
                    $_POST['postal_code'], 
                    $new_image_path,
                    $user_id
                );
            }
            
            if ($stmt->execute()) {
                $_SESSION['username'] = $username;
                $_SESSION['profile_image'] = $new_image_path;
                $success = true;
                $conn->commit();
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT username, email, phone, address_line1, city, state, postal_code, profile_image FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                // Update profile image path
                $profile_image = !empty($user['profile_image']) 
                    ? (strpos($user['profile_image'], 'uploads/profilephoto/') !== false 
                        ? "../" . $user['profile_image'] 
                        : "../uploads/profilephoto/" . $user['profile_image'])
                    : "../uploads/profilephoto/default-avatar.png";
            } else {
                throw new Exception("Database error: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 70px;
            background-color: #f8f9fa;
        }
        .profile-card {
            max-width: 800px;
            margin: 20px auto;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .profile-img-container {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            position: relative;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #dee2e6;
        }
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .img-upload-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #0d6efd;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card profile-card">
            <div class="card-header bg-white border-bottom-0">
                <h4 class="mb-0 text-center">Update Profile</h4>
            </div>
            
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Profile updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <div class="col-12 text-center mb-4">
                        <div class="profile-img-container">
                            <img src="<?= htmlspecialchars($profile_image) ?>" class="profile-img" id="profile-img-preview">
                            <label for="profile_image" class="img-upload-btn">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>
                    
                    <div class="col-12">
                        <label for="address_line1" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" 
                               value="<?= htmlspecialchars($user['address_line1']) ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" 
                               value="<?= htmlspecialchars($user['city']) ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="state" class="form-label">State</label>
                        <input type="text" class="form-control" id="state" name="state" 
                               value="<?= htmlspecialchars($user['state']) ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="postal_code" class="form-label">Postal Code</label>
                        <input type="text" class="form-control" id="postal_code" name="postal_code" 
                               value="<?= htmlspecialchars($user['postal_code']) ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="col-12 mt-4">
                        <div class="d-flex justify-content-between">
                            <a href="admin_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('profile_image').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-img-preview').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    new bootstrap.Alert(alert).close();
                });
            }, 5000);
        });
    </script>
</body>
</html>