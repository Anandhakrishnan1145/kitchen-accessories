<?php
// Start output buffering at the very top
ob_start();

// Start session and include config before any output
session_start();
include '../config.php';

// Check admin status before proceeding
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    ob_end_flush();
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update product quantities and reorder levels
    if (isset($_POST['update_products'])) {
        $agency_id = intval($_POST['agency_id']);
        $success_count = 0;
        $error_count = 0;
        
        // Loop through all product updates
        foreach ($_POST['products'] as $product_id => $values) {
            $product_id = intval($product_id);
            $reorder_level = intval($values['reorder_level']);
            $qty_to_add = intval($values['qty_to_add']); // Changed to qty_to_add
            
            // Validate inputs
            if ($reorder_level < 0) {
                $error_count++;
                continue;
            }
            
            if ($qty_to_add < 0) { // Still validate that added qty is not negative
                $error_count++;
                continue;
            }
            
            // Get current quantity first
            $stmt = $conn->prepare("SELECT product_quantity FROM products WHERE id = ? AND agency_id = ?");
            $stmt->bind_param("ii", $product_id, $agency_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_qty = 0;
            
            if ($row = $result->fetch_assoc()) {
                $current_qty = $row['product_quantity'];
            }
            $stmt->close();
            
            // Calculate new quantity by adding the input value to the current quantity
            $new_quantity = $current_qty + $qty_to_add;
            
            // Update the product record with both new values
            $stmt = $conn->prepare("UPDATE products SET 
                                    reorder_level = ?, 
                                    product_quantity = ?,
                                    updated_at = NOW() 
                                    WHERE id = ? AND agency_id = ?");
            $stmt->bind_param("iiii", $reorder_level, $new_quantity, $product_id, $agency_id);
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $stmt->close();
        }
        
        if ($success_count > 0) {
            $_SESSION['success_message'] = "$success_count product(s) updated successfully!";
        }
        
        if ($error_count > 0) {
            $_SESSION['error_message'] = "$error_count product(s) could not be updated. Please check for errors.";
        }
        
        header("Location: reorder_products.php?agency_id=" . $agency_id);
        exit();
    }
}

// Get all agencies
$agencies = [];
$agency_result = $conn->query("SELECT id, agency_name FROM agencies ORDER BY agency_name");
if ($agency_result->num_rows > 0) {
    while ($row = $agency_result->fetch_assoc()) {
        $agencies[] = $row;
    }
}

// Get selected agency ID
$selected_agency_id = isset($_GET['agency_id']) ? intval($_GET['agency_id']) : (count($agencies) > 0 ? $agencies[0]['id'] : 0);

// Get products for selected agency
$products = [];
if ($selected_agency_id > 0) {
    // Check if we're filtering for low stock items
    $filter_low_stock = isset($_GET['filter']) && $_GET['filter'] === 'low_stock';
    
    $query = "SELECT id, product_id, product_name, product_quantity, 
                     COALESCE(reorder_level, 10) as reorder_level,
                     product_price, selling_price, material, product_category
              FROM products 
              WHERE agency_id = ? AND is_visible = 1";
    
    // Add filter condition if needed
    if ($filter_low_stock) {
        $query .= " AND product_quantity <= reorder_level";
    }
    
    $query .= " ORDER BY product_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_agency_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $stmt->close();
}

// Get low stock products count for the navbar
$low_stock_count = 0;
if ($_SESSION['user_type'] === 'admin') {
    $low_stock_query = "SELECT COUNT(id) as low_stock_count
                        FROM products 
                        WHERE product_quantity <= reorder_level AND is_visible = 1";
    $low_stock_result = $conn->query($low_stock_query);
    if ($low_stock_result) {
        $row = $low_stock_result->fetch_assoc();
        $low_stock_count = $row['low_stock_count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Reorder Levels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .low-stock {
            background-color: #fff3cd;
        }
        .critical-stock {
            background-color: #f8d7da;
        }
        .agency-selector {
            max-width: 300px;
        }
        .selling-price {
            color: #dc3545;
            font-weight: bold;
        }
        .cost-price {
            color: #28a745;
            font-weight: bold;
        }
        .badge-low-stock {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-critical-stock {
            background-color: #dc3545;
            color: white;
        }
        .quantity-input, .reorder-input {
            width: 80px;
        }
        .editable-field {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
        }
        .disabled-field {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #6c757d;
        }
        .table td {
            vertical-align: middle;
        }
        .current-qty {
            font-weight: bold;
        }
        
    </style>
</head>
<body>
<?php include "navbar.php"; ?>
    <div class="container py-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Manage Product Inventory</h1>
            <?php if ($low_stock_count > 0): ?>
                <a href="reorder_products.php?filter=low_stock<?= $selected_agency_id ? '&agency_id=' . $selected_agency_id : '' ?>" class="btn btn-warning position-relative">
                    <i class="bi bi-exclamation-triangle"></i> Low Stock
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $low_stock_count ?>
                    </span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="agency_id" class="form-label">Select Agency</label>
                        <select class="form-select agency-selector" id="agency_id" name="agency_id" onchange="this.form.submit()">
                            <?php foreach ($agencies as $agency): ?>
                                <option value="<?= htmlspecialchars($agency['id']) ?>" <?= $selected_agency_id == $agency['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($agency['agency_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isset($_GET['filter']) && $_GET['filter'] === 'low_stock'): ?>
                        <div class="col-md-6">
                            <a href="reorder_products.php<?= $selected_agency_id ? '?agency_id=' . $selected_agency_id : '' ?>" class="btn btn-secondary">Show All Products</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if ($selected_agency_id > 0): ?>
            <div class="table-container">
                <form method="post" action="reorder_products.php" id="updateProductsForm">
                    <input type="hidden" name="agency_id" value="<?= $selected_agency_id ?>">
                    
                    <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered text-center align-middle">
                    <thead class="table-primary" style="color: black;">
    <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Material</th>
        <th>Current Qty</th>
        <th>Add Qty</th>
        <th>Reorder Level</th>
        <th>Status</th>
        <th>Cost</th>
        <th>Price</th>
    </tr>
</thead>

                            <tbody>
                                <?php foreach ($products as $product): 
                                    $is_low_stock = $product['product_quantity'] <= $product['reorder_level'];
                                    $is_critical = $product['product_quantity'] <= ($product['reorder_level'] / 2);
                                    $row_class = '';
                                    $badge_class = '';
                                    $status_text = '';
                                    
                                    if ($is_critical) {
                                        $row_class = 'critical-stock';
                                        $badge_class = 'badge-critical-stock';
                                        $status_text = 'Critical';
                                    } elseif ($is_low_stock) {
                                        $row_class = 'low-stock';
                                        $badge_class = 'badge-low-stock';
                                        $status_text = 'Low';
                                    } else {
                                        $status_text = 'OK';
                                        $badge_class = 'bg-success';
                                    }
                                ?>
                                    <tr class="<?= $row_class ?>">
                                        <td><?= htmlspecialchars($product['product_id']) ?></td>
                                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td><?= htmlspecialchars($product['product_category']) ?></td>
                                        <td><?= htmlspecialchars($product['material']) ?></td>
                                        <td class="current-qty"><?= htmlspecialchars($product['product_quantity']) ?></td>
                                        <td>
                                            <input type="number" name="products[<?= $product['id'] ?>][qty_to_add]" 
                                                   value="0" 
                                                   min="0" class="form-control form-control-sm quantity-input editable-field">
                                        </td>
                                        <td>
                                            <input type="number" name="products[<?= $product['id'] ?>][reorder_level]" 
                                                   value="<?= htmlspecialchars($product['reorder_level']) ?>" 
                                                   min="0" class="form-control form-control-sm reorder-input editable-field">
                                        </td>
                                        <td>
                                            <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td class="cost-price">₹<?= number_format($product['product_price'], 2) ?></td>
                                        <td class="selling-price">₹<?= number_format($product['selling_price'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!empty($products)): ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                            <button type="submit" name="update_products" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No products found for this agency.</div>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">No agencies found. Please add agencies first.</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Highlight rows with low stock
            const lowStockRows = document.querySelectorAll('.low-stock, .critical-stock');
            lowStockRows.forEach(row => {
                row.addEventListener('mouseover', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.transition = 'transform 0.2s';
                });
                row.addEventListener('mouseout', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Form validation
            document.getElementById('updateProductsForm').addEventListener('submit', function(e) {
                const quantityInputs = document.querySelectorAll('.quantity-input');
                const reorderInputs = document.querySelectorAll('.reorder-input');
                let hasError = false;
                
                quantityInputs.forEach(input => {
                    if (parseInt(input.value) < 0) {
                        input.classList.add('is-invalid');
                        hasError = true;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                reorderInputs.forEach(input => {
                    if (parseInt(input.value) < 0) {
                        input.classList.add('is-invalid');
                        hasError = true;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                if (hasError) {
                    e.preventDefault();
                    alert('Please fix the errors in the form. All values must be non-negative.');
                }
            });
            
            // Save the form when pressing Ctrl+S
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    document.getElementById('updateProductsForm').submit();
                }
            });
        });
    </script>
</body>
</html>
<?php
// End output buffering and flush the buffer
ob_end_flush();
?>