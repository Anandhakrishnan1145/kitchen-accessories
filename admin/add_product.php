<?php
// Include database connection
include '../config.php';

// Function to generate unique product ID
function generateProductId($conn) {
    // Get the highest product_id from the database
    $sql = "SELECT MAX(CAST(SUBSTRING(product_id, 5) AS UNSIGNED)) as max_id FROM products";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $next_id = 111; // Default starting value
    if ($row['max_id']) {
        $next_id = $row['max_id'] + 1;
    }
    
    return 'PROD' . $next_id;
}

// Fetch agencies for dropdown
$agencyQuery = "SELECT id, agency_name FROM agencies ORDER BY agency_name";
$agencyResult = $conn->query($agencyQuery);

// Fetch categories for dropdown
$categoryQuery = "SELECT id, category_name FROM categories ORDER BY category_name";
$categoryResult = $conn->query($categoryQuery);

// Fetch materials for dropdown
$materialQuery = "SELECT id, material_name FROM materials ORDER BY material_name";
$materialResult = $conn->query($materialQuery);

// Process category addition
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['new_category']);
    if (!empty($category_name)) {
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->bind_param("s", $category_name);
        if ($stmt->execute()) {
            echo "<script>alert('Category added successfully!'); window.location.href=window.location.href;</script>";
        } else {
            echo "<script>alert('Error adding category: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}

// Process material addition
if (isset($_POST['add_material'])) {
    $material_name = trim($_POST['new_material']);
    if (!empty($material_name)) {
        $stmt = $conn->prepare("INSERT INTO materials (material_name) VALUES (?)");
        $stmt->bind_param("s", $material_name);
        if ($stmt->execute()) {
            echo "<script>alert('Material added successfully!'); window.location.href=window.location.href;</script>";
        } else {
            echo "<script>alert('Error adding material: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_name'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_quantity = $_POST['product_quantity'];
    $product_price = $_POST['product_price'];
    $agency_id = $_POST['agency_id'];
    $profit_percent = $_POST['profit_percent'];
    $product_material = $_POST['product_material'];
    $product_category = $_POST['product_category'];
    
    // Calculate selling price
    $selling_price = $product_price + ($product_price * $profit_percent / 100);
    
    // Handle image upload
    $product_image = NULL;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        // Create directory if it doesn't exist
        $upload_dir = "../uploads/product_images/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $product_image = $target_file;
        } else {
            echo "<div class='alert alert-danger'>Sorry, there was an error uploading your file.</div>";
        }
    }
    
    // Prepare and execute SQL query
    $stmt = $conn->prepare("INSERT INTO products (product_id, product_name, product_quantity, product_price, agency_id, profit_percent, selling_price, product_image, material, product_category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiddssss", $product_id, $product_name, $product_quantity, $product_price, $agency_id, $profit_percent, $selling_price, $product_image, $product_material, $product_category);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Product added successfully!</div>";
        // Redirect to products list
        header("Location: products.php");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    
    $stmt->close();
}

// Generate new product ID
$new_product_id = generateProductId($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .container {
            margin-top: 30px;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
        }
        .form-group label {
            font-weight: 500;
        }
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
        }
        .category-add-btn, .material-add-btn {
            cursor: pointer;
        }
        .category-input-group, .material-input-group {
            margin-bottom: 15px;
        }
        .modal-header {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
<?php include "navbar.php"?>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-plus-circle"></i> Add New Product</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="product_id">Product ID</label>
                                <input type="text" class="form-control" id="product_id" name="product_id" value="<?php echo $new_product_id; ?>" readonly>
                                <small class="form-text text-muted">Auto-generated product ID</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_name">Product Name *</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" required>
                            </div>
                            
                            <div class="form-group material-input-group">
                                <label for="product_material">Material *</label>
                                <div class="input-group">
                                    <select class="form-control" id="product_material" name="product_material" required>
                                        <option value="">-- Select Material --</option>
                                        <?php
                                        if ($materialResult->num_rows > 0) {
                                            while($material = $materialResult->fetch_assoc()) {
                                                echo "<option value='" . $material['material_name'] . "'>" . $material['material_name'] . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary material-add-btn" data-toggle="modal" data-target="#materialModal">
                                            <i class="fas fa-plus"></i> Add New
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group category-input-group">
                                <label for="product_category">Product Category *</label>
                                <div class="input-group">
                                    <select class="form-control" id="product_category" name="product_category" required>
                                        <option value="">-- Select Category --</option>
                                        <?php
                                        if ($categoryResult->num_rows > 0) {
                                            while($category = $categoryResult->fetch_assoc()) {
                                                echo "<option value='" . $category['category_name'] . "'>" . $category['category_name'] . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary category-add-btn" data-toggle="modal" data-target="#categoryModal">
                                            <i class="fas fa-plus"></i> Add New
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_quantity">Product Quantity *</label>
                                <input type="number" class="form-control" id="product_quantity" name="product_quantity" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_price">Product Price (₹) *</label>
                                <input type="number" class="form-control" id="product_price" name="product_price" min="0" step="0.01" required onchange="calculateSellingPrice()">
                            </div>
                            
                            <div class="form-group">
                                <label for="agency_id">Select Agency *</label>
                                <select class="form-control" id="agency_id" name="agency_id" required>
                                    <option value="">-- Select Agency --</option>
                                    <?php
                                    if ($agencyResult->num_rows > 0) {
                                        while($agency = $agencyResult->fetch_assoc()) {
                                            echo "<option value='" . $agency['id'] . "'>" . $agency['agency_name'] . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="profit_percent">Profit Percentage (%) *</label>
                                <input type="number" class="form-control" id="profit_percent" name="profit_percent" min="0" step="0.01" required onchange="calculateSellingPrice()">
                            </div>
                            
                            <div class="form-group">
                                <label for="selling_price">Selling Price (₹)</label>
                                <input type="number" class="form-control" id="selling_price" name="selling_price" readonly>
                                <small class="form-text text-muted">Auto-calculated based on product price and profit percentage</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_image">Product Image</label>
                                <input type="file" class="form-control-file" id="product_image" name="product_image" accept="image/*" onchange="previewImage(this)">
                                <div id="imagePreview"></div>
                            </div>
                            
                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save"></i> Add Product</button>
                                <a href="products.php" class="btn btn-secondary ml-2 px-4"><i class="fas fa-times"></i> Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Add New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_category">Category Name</label>
                            <input type="text" class="form-control" id="new_category" name="new_category" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Material Modal -->
    <div class="modal fade" id="materialModal" tabindex="-1" role="dialog" aria-labelledby="materialModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="materialModalLabel">Add New Material</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_material">Material Name</label>
                            <input type="text" class="form-control" id="new_material" name="new_material" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="add_material" class="btn btn-primary">Add Material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Calculate selling price
        function calculateSellingPrice() {
            const productPrice = parseFloat(document.getElementById('product_price').value) || 0;
            const profitPercent = parseFloat(document.getElementById('profit_percent').value) || 0;
            const sellingPrice = productPrice + (productPrice * profitPercent / 100);
            document.getElementById('selling_price').value = sellingPrice.toFixed(2);
        }
        
        // Preview image
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-image');
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Refresh categories and materials after adding new ones
        <?php if(isset($_POST['add_category']) || isset($_POST['add_material'])): ?>
            $(document).ready(function() {
                // This will automatically refresh the page after modal closes
                $('#categoryModal, #materialModal').on('hidden.bs.modal', function () {
                    location.reload();
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>