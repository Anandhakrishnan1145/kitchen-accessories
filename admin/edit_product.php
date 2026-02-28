<?php
// Include database connection
include '../config.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch agencies for dropdown
$agencyQuery = "SELECT id, agency_name FROM agencies ORDER BY agency_name";
$agencyResult = $conn->query($agencyQuery);

// Fetch categories for dropdown
$categoryQuery = "SELECT id, category_name FROM categories ORDER BY category_name";
$categoryResult = $conn->query($categoryQuery);

// Fetch materials for dropdown
$materialQuery = "SELECT id, material_name FROM materials ORDER BY material_name";
$materialResult = $conn->query($materialQuery);

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Initialize alert variables
$alert_type = '';
$alert_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $product_name = trim($_POST['product_name']);
    $product_quantity = intval($_POST['product_quantity']);
    $product_price = floatval($_POST['product_price']);
    $agency_id = intval($_POST['agency_id']);
    $profit_percent = floatval($_POST['profit_percent']);
    $material = trim($_POST['material']);
    $product_category = trim($_POST['product_category']);
    
    // Validate required fields
    if (empty($product_name)) {
        $alert_type = 'danger';
        $alert_message = 'Product name is required';
    } else {
        // Calculate selling price
        $selling_price = $product_price + ($product_price * $profit_percent / 100);
        
        // Handle image upload
        $product_image = $product['product_image'];
        
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
            // File upload configuration
            $upload_dir = "../uploads/product_images/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Get file info
            $file_name = $_FILES['product_image']['name'];
            $file_tmp = $_FILES['product_image']['tmp_name'];
            $file_size = $_FILES['product_image']['size'];
            $file_error = $_FILES['product_image']['error'];
            
            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed)) {
                if ($file_error === 0) {
                    if ($file_size <= 2097152) { // 2MB max
                        // Generate unique filename
                        $new_file_name = 'product_' . uniqid() . '.' . $file_ext;
                        $file_destination = $upload_dir . $new_file_name;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file_tmp, $file_destination)) {
                            // Delete old image if exists
                            if (!empty($product['product_image']) && file_exists($product['product_image'])) {
                                unlink($product['product_image']);
                            }
                            $product_image = $file_destination;
                        } else {
                            $alert_type = 'danger';
                            $alert_message = 'Failed to move uploaded file';
                        }
                    } else {
                        $alert_type = 'danger';
                        $alert_message = 'File size too large (max 2MB)';
                    }
                } else {
                    $alert_type = 'danger';
                    $alert_message = 'File upload error';
                }
            } else {
                $alert_type = 'danger';
                $alert_message = 'Invalid file type. Only JPG, JPEG, PNG & GIF are allowed';
            }
        }
        
        // Handle image removal if checkbox is checked
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            if (!empty($product['product_image']) && file_exists($product['product_image'])) {
                unlink($product['product_image']);
            }
            $product_image = NULL;
        }
        
        // Only proceed if no errors so far
        if (empty($alert_message)) {
            // Prepare and execute SQL query
            $stmt = $conn->prepare("UPDATE products SET 
                product_name = ?, 
                product_quantity = ?, 
                product_price = ?, 
                agency_id = ?, 
                profit_percent = ?, 
                selling_price = ?, 
                product_image = ?, 
                material = ?, 
                product_category = ?, 
                updated_at = NOW() 
                WHERE id = ?");
            
            // Bind parameters - note the order must match the SQL statement
            $stmt->bind_param("siiddssssi", 
                $product_name, 
                $product_quantity, 
                $product_price, 
                $agency_id, 
                $profit_percent, 
                $selling_price, 
                $product_image, 
                $material, 
                $product_category, 
                $product_id
            );
            
            if ($stmt->execute()) {
                $alert_type = 'success';
                $alert_message = 'Product updated successfully!';
            } else {
                $alert_type = 'danger';
                $alert_message = 'Error updating product: ' . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-dark: #5a5c69;
            --text-light: #858796;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .form-control, .custom-select {
            border-radius: 0.35rem;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d3e2;
        }
        
        .form-control:focus, .custom-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 2rem;
            border-radius: 0.35rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
        }
        
        .preview-image-container {
            border: 2px dashed #d1d3e2;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
            background-color: var(--secondary-color);
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        
        .image-actions {
            margin-top: 10px;
        }
        
        .info-icon {
            color: var(--primary-color);
            margin-right: 8px;
        }
        
        .input-group-text {
            background-color: #eaecf4;
            border: 1px solid #d1d3e2;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
        }
        
        .alert-fixed {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            width: 350px;
            animation: slideIn 0.5s forwards, fadeOut 0.5s 4s forwards;
        }
        
        @keyframes slideIn {
            from { right: -400px; opacity: 0; }
            to { right: 20px; opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>
<body>
    <?php include "navbar.php"; ?>
    
    <!-- Alert Notification -->
    <?php if (!empty($alert_message)): ?>
        <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show alert-fixed" role="alert">
            <?php echo $alert_message; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit Product</h3>
                            <a href="products.php" class="btn btn-sm btn-light">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Products
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-5">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $product_id; ?>" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4 class="section-title">Basic Information</h4>
                                    
                                    <div class="form-group">
                                        <label for="product_id" class="font-weight-bold">Product ID</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="product_name" class="font-weight-bold">Product Name *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-box"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="material" class="font-weight-bold">Material *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-shapes"></i></span>
                                            </div>
                                            <select class="form-control" id="material" name="material" required>
                                                <option value="">-- Select Material --</option>
                                                <?php
                                                if ($materialResult && $materialResult->num_rows > 0) {
                                                    while($mat = $materialResult->fetch_assoc()) {
                                                        $selected = ($mat['material_name'] == $product['material']) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($mat['material_name']) . "' $selected>" . htmlspecialchars($mat['material_name']) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <small class="form-text text-muted"><i class="fas fa-info-circle info-icon"></i>E.g., copper, stainless steel, aluminum, plastic</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="product_category" class="font-weight-bold">Product Category *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-layer-group"></i></span>
                                            </div>
                                            <select class="form-control" id="product_category" name="product_category" required>
                                                <option value="">-- Select Category --</option>
                                                <?php
                                                if ($categoryResult->num_rows > 0) {
                                                    // Reset pointer to beginning
                                                    $categoryResult->data_seek(0);
                                                    while($category = $categoryResult->fetch_assoc()) {
                                                        $selected = ($category['category_name'] == $product['product_category']) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($category['category_name']) . "' $selected>" . htmlspecialchars($category['category_name']) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <small class="form-text text-muted"><i class="fas fa-info-circle info-icon"></i>E.g., Cookware, Storage, Utensils, Containers</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h4 class="section-title">Pricing & Inventory</h4>
                                    
                                    <div class="form-group">
                                        <label for="product_quantity" class="font-weight-bold">Product Quantity *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="product_quantity" name="product_quantity" min="0" value="<?php echo htmlspecialchars($product['product_quantity']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="product_price" class="font-weight-bold">Product Price (₹) *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-rupee-sign"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="product_price" name="product_price" min="0" step="0.01" value="<?php echo htmlspecialchars($product['product_price']); ?>" required onchange="calculateSellingPrice()">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="agency_id" class="font-weight-bold">Select Agency *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                            </div>
                                            <select class="form-control" id="agency_id" name="agency_id" required>
                                                <option value="">-- Select Agency --</option>
                                                <?php
                                                if ($agencyResult->num_rows > 0) {
                                                    // Reset pointer to beginning
                                                    $agencyResult->data_seek(0);
                                                    while($agency = $agencyResult->fetch_assoc()) {
                                                        $selected = ($agency['id'] == $product['agency_id']) ? 'selected' : '';
                                                        echo "<option value='" . htmlspecialchars($agency['id']) . "' $selected>" . htmlspecialchars($agency['agency_name']) . "</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="profit_percent" class="font-weight-bold">Profit Percentage (%) *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="profit_percent" name="profit_percent" min="0" step="0.01" value="<?php echo htmlspecialchars($product['profit_percent']); ?>" required onchange="calculateSellingPrice()">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="selling_price" class="font-weight-bold">Selling Price (₹)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="selling_price" value="<?php echo htmlspecialchars($product['selling_price']); ?>" readonly>
                                        </div>
                                        <small class="form-text text-muted"><i class="fas fa-info-circle info-icon"></i>Auto-calculated based on product price and profit percentage</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h4 class="section-title">Product Image</h4>
                                    
                                    <div class="form-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="product_image" name="product_image" accept="image/*" onchange="previewImage(this)">
                                            <label class="custom-file-label" for="product_image">Choose new image...</label>
                                        </div>
                                        <small class="form-text text-muted">Leave empty to keep current image</small>
                                        
                                        <div class="preview-image-container mt-3" id="imagePreview">
                                            <?php if (!empty($product['product_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['product_image']); ?>" class="preview-image mb-3" alt="Current Product Image">
                                                <div class="image-actions">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" id="remove_image" name="remove_image" value="1">
                                                        <label class="custom-control-label text-danger" for="remove_image">Remove current image</label>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted mb-0"><i class="fas fa-image fa-2x"></i></p>
                                                <p class="text-muted">No image uploaded</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary px-4 py-2 mr-2">
                                        <i class="fas fa-save mr-2"></i>Update Product
                                    </button>
                                    <a href="products.php" class="btn btn-secondary px-4 py-2">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Calculate selling price
        function calculateSellingPrice() {
            const productPrice = parseFloat($('#product_price').val()) || 0;
            const profitPercent = parseFloat($('#profit_percent').val()) || 0;
            const sellingPrice = productPrice + (productPrice * profitPercent / 100);
            $('#selling_price').val(sellingPrice.toFixed(2));
        }
        
        // Initialize selling price on page load
        $(document).ready(function() {
            calculateSellingPrice();
            
            // Update custom file input label
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
        
        // Preview image
        function previewImage(input) {
            const preview = $('#imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.html(`
                        <img src="${e.target.result}" class="preview-image mb-3" alt="Preview">
                        <div class="image-actions">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remove_new_image" name="remove_image" value="1">
                                <label class="custom-control-label text-danger" for="remove_new_image">Remove this image</label>
                            </div>
                        </div>
                    `);
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                // Show existing image if available
                <?php if (!empty($product['product_image'])): ?>
                    preview.html(`
                        <img src="<?php echo htmlspecialchars($product['product_image']); ?>" class="preview-image mb-3" alt="Current Product Image">
                        <div class="image-actions">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remove_image" name="remove_image" value="1">
                                <label class="custom-control-label text-danger" for="remove_image">Remove current image</label>
                            </div>
                        </div>
                    `);
                <?php else: ?>
                    preview.html(`
                        <p class="text-muted mb-0"><i class="fas fa-image fa-2x"></i></p>
                        <p class="text-muted">No image selected</p>
                    `);
                <?php endif; ?>
            }
        }
    </script>
</body>
</html>