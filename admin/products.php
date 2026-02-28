<?php
// Start output buffering at the very top
ob_start();

// Start session and include config before any output
session_start();
include '../config.php';

// Check admin status before including navbar
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    ob_end_flush();
    exit();
}

// Get all products with agency info
$query = "SELECT p.*, a.agency_name 
          FROM products p 
          LEFT JOIN agencies a ON p.agency_id = a.id 
          ORDER BY p.created_at DESC";
$result = $conn->query($query);

$products = array();
if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        $products[] = $product;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Products</title>
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
        .selling-price {
            color: #dc3545;
            font-weight: bold;
        }
        .profit-positive {
            color: #28a745;
        }
        .profit-negative {
            color: #dc3545;
        }
        .profit-neutral {
            color: #6c757d;
        }
    </style>
</head>
<body>
<?php include "navbar.php"?>
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
            <h1 class="h3">Manage Products</h1>
            <a href="add_product.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Product
            </a>
        </div>
        
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Qty</th>
                            <th>Cost</th>
                            <th>Price</th>
                            <th>Profit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): 
                            $profit = $product['selling_price'] - $product['product_price'];
                            $profit_percent = ($product['product_price'] > 0) 
                                ? ($profit / $product['product_price']) * 100 
                                : 0;
                            
                            $profit_class = ($profit > 0) ? 'profit-positive' : 
                                          (($profit < 0) ? 'profit-negative' : 'profit-neutral');
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($product['product_name']); ?></td>
                                <td><?= htmlspecialchars($product['product_quantity']); ?></td>
                                <td>₹<?= number_format($product['product_price'], 2); ?></td>
                                <td class="selling-price">₹<?= number_format($product['selling_price'], 2); ?></td>
                                <td class="<?= $profit_class ?>">
                                    ₹<?= number_format($profit, 2); ?> 
                                    (<?= number_format($profit_percent, 0); ?>%)
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="edit_product.php?id=<?= $product['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="delete_product.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this product?');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
<?php
// End output buffering and flush the buffer
ob_end_flush();
?>