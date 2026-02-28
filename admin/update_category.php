<?php 
include "navbar.php";


// Database Connection
$conn = new mysqli("localhost", "root", "", "final");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        // Add new category
        $name = $conn->real_escape_string($_POST['name']);
        $conn->query("INSERT INTO categories (category_name) VALUES ('$name')");
        
    } elseif ($action == 'update') {
        // Update existing category
        $id = intval($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        $conn->query("UPDATE categories SET category_name='$name' WHERE id=$id");
        
    } elseif ($action == 'delete') {
        // Delete category with password verification
        $id = intval($_POST['id']);
        $password = $conn->real_escape_string($_POST['password']);
        
        // Verify admin password
        $admin_id = $_SESSION['user_id'];
        $check = $conn->query("SELECT * FROM users WHERE id='$admin_id' AND password='".md5($password)."'");
        
        if ($check->num_rows > 0) {
            // First check if category is used in products
            $product_check = $conn->query("SELECT COUNT(*) as count FROM products WHERE product_category = (SELECT category_name FROM categories WHERE id=$id)");
            $product_count = $product_check->fetch_assoc()['count'];
            
            if ($product_count > 0) {
                echo "Cannot delete - category is in use by $product_count products";
            } else {
                $conn->query("DELETE FROM categories WHERE id=$id");
                echo "Category deleted";
            }
        } else {
            echo "Incorrect Password";
        }
    }
    exit;
}

// Handle product deletion (new functionality)
if (isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    $password = $conn->real_escape_string($_POST['password']);
    
    // Verify admin password
    $admin_id = $_SESSION['user_id'];
    $check = $conn->query("SELECT * FROM users WHERE id='$admin_id' AND password='".md5($password)."'");
    
    if ($check->num_rows > 0) {
        // Soft delete the product (set status to 'inactive')
        $conn->query("UPDATE products SET status='inactive' WHERE id=$product_id");
        $_SESSION['message'] = "Product deleted successfully";
    } else {
        $_SESSION['error'] = "Incorrect password";
    }
    header("Location: update_category.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category & Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .action-btns { white-space: nowrap; }
        .tab-content { padding: 20px 0; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <h2 class="text-center mb-4">Category & Product Management</h2>
        
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">Categories</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">Products</button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Categories Tab -->
            <div class="tab-pane fade show active" id="categories" role="tabpanel">
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Add Category</button>
                <table id="categoryTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM categories";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                <td><?= $row['created_at'] ?></td>
                                <td class="action-btns">
                                    <button class='btn btn-sm btn-success editBtn' 
                                            data-id='<?= $row['id'] ?>' 
                                            data-name='<?= htmlspecialchars($row['category_name']) ?>'
                                            data-bs-toggle='modal' 
                                            data-bs-target='#editModal'>
                                        Edit
                                    </button>
                                    <button class='btn btn-sm btn-danger deleteBtn' data-id='<?= $row['id'] ?>'>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Products Tab -->
            <div class="tab-pane fade" id="products" role="tabpanel">
                <table id="productTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT p.*, c.category_name 
                                FROM products p 
                                LEFT JOIN categories c ON p.product_category = c.category_name";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['product_id']) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= number_format($row['selling_price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['status'] == 'active' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td class="action-btns">
                                    <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success">Edit</a>
                                    <button class="btn btn-sm btn-danger deleteProductBtn" 
                                            data-id="<?= $row['id'] ?>" 
                                            data-name="<?= htmlspecialchars($row['product_name']) ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteProductModal">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm">
                        <div class="mb-3">
                            <label for="newCategory" class="form-label">Category Name</label>
                            <input type="text" id="newCategory" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="addCategory">Add</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm">
                        <input type="hidden" id="editId">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Category Name</label>
                            <input type="text" id="editName" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="updateCategory">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="productToDelete"></strong>?</p>
                    <form method="POST" id="deleteProductForm">
                        <input type="hidden" name="product_id" id="deleteProductId">
                        <div class="mb-3">
                            <label for="password" class="form-label">Admin Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteProductForm" name="delete_product" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#categoryTable, #productTable').DataTable();
            
            // Add Category
            $('#addCategory').click(function() {
                var name = $('#newCategory').val();
                if (!name) {
                    alert('Please enter a category name');
                    return;
                }
                
                $.post('', { action: 'add', name: name }, function() {
                    location.reload();
                });
            });
            
            // Edit Button Click
            $(document).on('click', '.editBtn', function() {
                $('#editId').val($(this).data('id'));
                $('#editName').val($(this).data('name'));
            });
            
            // Update Category
            $('#updateCategory').click(function() {
                var id = $('#editId').val();
                var name = $('#editName').val();
                
                if (!name) {
                    alert('Please enter a category name');
                    return;
                }
                
                $.post('', { action: 'update', id: id, name: name }, function() {
                    location.reload();
                });
            });
            
            // Delete Category
            $(document).on('click', '.deleteBtn', function() {
                if (!confirm('Are you sure you want to delete this category?')) return;
                
                var id = $(this).data('id');
                var password = prompt("Enter your admin password to confirm deletion:");
                
                if (password) {
                    $.post('', { 
                        action: 'delete', 
                        id: id, 
                        password: password 
                    }, function(response) {
                        alert(response);
                        if (response === 'Category deleted') {
                            location.reload();
                        }
                    });
                }
            });
            
            // Delete Product Button Click
            $(document).on('click', '.deleteProductBtn', function() {
                var productId = $(this).data('id');
                var productName = $(this).data('name');
                
                $('#deleteProductId').val(productId);
                $('#productToDelete').text(productName);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>