<?php
session_start();
require_once '../config.php';

$error = "";
$success = "";

// Initialize variables to avoid undefined variable warnings
$agency_name = $agent_name = $agency_code = $phone = $address = $email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if each POST variable exists before accessing it
    $agency_name = isset($_POST['agency_name']) ? trim($_POST['agency_name']) : '';
    $agent_name = isset($_POST['agent_name']) ? trim($_POST['agent_name']) : '';
    $agency_code = isset($_POST['agency_code']) ? trim($_POST['agency_code']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Validate inputs
    if (empty($agency_name) || empty($agent_name) || empty($agency_code) || empty($phone) || empty($address) || empty($email)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Rest of your code remains the same...
        // Check if agency code or email already exists
        $stmt = $conn->prepare("SELECT * FROM agencies WHERE agency_code = ? OR email = ?");
        $stmt->bind_param("ss", $agency_code, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Agency code or email already exists";
        } else {
            // Handle image upload
            $upload_dir = "../uploads/agency_profiles/";
            
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
                    $new_file_name = uniqid('agency_') . '.' . $file_extension;
                    $target_file = $upload_dir . $new_file_name;
                    
                    // Check if upload directory is writable
                    if (is_writable($upload_dir)) {
                        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                            // Store relative path in database
                            $profile_image_path = "../uploads/agency_profiles/" . $new_file_name;
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
                // If no image uploaded, use default
                $profile_image_path = "../uploads/agency_profiles/default-agency.png";
            }
            
            if (empty($error)) {
                // Insert agency data
                $stmt = $conn->prepare("INSERT INTO agencies (agency_name, agent_name, agency_code, phone, address, email, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $agency_name, $agent_name, $agency_code, $phone, $address, $email, $profile_image_path);
                
                if ($stmt->execute()) {
                    $success = "Agency registration successful! View all agencies <a href='view_agencies.php'>here</a>.";
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
    <title>Agency Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 15px;
            background-color: #fff;
        }
        .form-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-weight: 600;
        }
        .image-upload-wrapper {
            position: relative;
            width: 150px;
            margin: 0 auto 20px;
        }
        .preview-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto;
            display: block;
            border: 3px solid #f0f0f0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .image-upload-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #0d6efd;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-upload-icon:hover {
            background: #0b5ed7;
            transform: scale(1.1);
        }
        #image-upload {
            display: none;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
    </style>
</head>

<body class="bg-light">
<?php include "navbar.php"?>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Agency Registration</h2>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <!-- Profile Image Upload -->
                <div class="image-upload-wrapper mb-4">
                    <img src="../uploads/agency_profiles/default-agency.png" alt="Agency Profile" class="preview-image" id="profile-preview">
                    <div class="image-upload-icon" onclick="document.getElementById('image-upload').click();">
                        <i class="fas fa-camera"></i>
                    </div>
                    <input type="file" name="profile_image" id="image-upload" accept="image/*" onchange="previewImage(this);">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="agency_name" class="form-label">Agency Name</label>
                        <input type="text" class="form-control" id="agency_name" name="agency_name" value="<?php echo isset($_POST['agency_name']) ? htmlspecialchars($_POST['agency_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="agent_name" class="form-label">Agent Name</label>
                        <input type="text" class="form-control" id="agent_name" name="agent_name" value="<?php echo isset($_POST['agent_name']) ? htmlspecialchars($_POST['agent_name']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="agency_code" class="form-label">Agency Code</label>
                        <input type="text" class="form-control" id="agency_code" name="agency_code" value="<?php echo isset($_POST['agency_code']) ? htmlspecialchars($_POST['agency_code']) : ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-primary">Register Agency</button>
                </div>
                
                <div class="text-center mt-3">
                    <a href="view_agencies.php" class="text-decoration-none">View All Agencies</a>
                </div>
            </form>
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
    </script>
</body>
</html>