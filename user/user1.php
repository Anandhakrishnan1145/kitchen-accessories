<?php
session_start();
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
    } else {
        $error_message = "Failed to update profile. Please try again.";
    }
}

// Get all products
$query = "SELECT p.*, a.agency_name 
          FROM products p 
          LEFT JOIN agencies a ON p.agency_id = a.id 
          ORDER BY p.created_at DESC";
$result = $conn->query($query);

// Handle Add to Cart functionality
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    // Check if product is available in stock
    $check_stock = $conn->prepare("SELECT product_quantity FROM products WHERE id = ?");
    $check_stock->bind_param("i", $product_id);
    $check_stock->execute();
    $stock_result = $check_stock->get_result();
    $stock = $stock_result->fetch_assoc();
    
    if ($stock['product_quantity'] >= $quantity) {
        // Check if user already has a cart
        $cart_check = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
        $cart_check->bind_param("i", $user_id);
        $cart_check->execute();
        $cart_result = $cart_check->get_result();
        $cart = $cart_result->fetch_assoc();
        
        if (!$cart) {
            // Create a new cart if it doesn't exist
            $create_cart = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
            $create_cart->bind_param("i", $user_id);
            $create_cart->execute();
            $cart_id = $conn->insert_id;
        } else {
            $cart_id = $cart['id'];
        }
        
        // Check if the product already exists in the cart
        $check_cart_item = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $check_cart_item->bind_param("ii", $cart_id, $product_id);
        $check_cart_item->execute();
        $cart_item_result = $check_cart_item->get_result();
        $cart_item = $cart_item_result->fetch_assoc();
        
        if ($cart_item) {
            // Update the quantity if the product already exists in the cart
            $new_quantity = $cart_item['quantity'] + $quantity;
            $update_cart_item = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $update_cart_item->bind_param("ii", $new_quantity, $cart_item['id']);
            $update_cart_item->execute();
        } else {
            // Insert into cart_items table if the product doesn't exist in the cart
            $insert_cart_item = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert_cart_item->bind_param("iii", $cart_id, $product_id, $quantity);
            $insert_cart_item->execute();
        }
        
        $success_message = "Product added to cart successfully!";
    } else {
        $error_message = "Not enough stock available!";
    }
}

// Count items in cart
$cart_count = 0;
$cart_query = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE c.user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();
$cart_data = $cart_result->fetch_assoc();
$cart_count = $cart_data['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-commerce Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
     .stock-status {
            font-size: 14px;
            font-weight: bold;
        }
        .out-of-stock {
            color: #dc3545; /* Red */
        }
        .limited-stock {
            color: #ffc107; /* Yellow */
        }
        .in-stock {
            color: #28a745; /* Green */
        }
        .profile-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .navbar-profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid #fff;
        }
        .product-card {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
            height: 100%;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .product-title {
            font-weight: 600;
            font-size: 16px;
            height: 48px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .product-price {
            font-weight: 700;
            color: #e74c3c;
            font-size: 18px;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .quantity-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
        }
        .quantity-input {
            width: 40px;
            text-align: center;
            border: 1px solid #dee2e6;
            height: 30px;
            margin: 0 5px;
        }
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">E-commerce Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Products</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="position-relative me-4">
                        <a href="cart.php" class="text-white">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <?php if($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="position-relative me-4">
                            <a href="orders.php" class="text-white">
                                <i class="fas fa-shopping-bag fa-lg"></i>
                            </a>
                        </div>

                        <span class="text-light me-3"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <div class="profile-dropdown" style="margin-right: 15px;">
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="navbar-profile-image" 
                                 data-bs-toggle="modal" data-bs-target="#profileModal" 
                                 onerror="this.src='../uploads/profilephoto/default-avatar.png';">
                        </div>
                        <a href="logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container profile-container">
        <?php if(isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <h2 class="mb-4">Our Products</h2>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php
            if ($result->num_rows > 0) {
                while($product = $result->fetch_assoc()) {
                    $product_image = !empty($product['product_image']) && file_exists($product['product_image']) 
                        ? $product['product_image'] 
                        : '../uploads/products/default-product.jpg';
                    
                    // Determine stock status
                    $stock_status = "";
                    if ($product['product_quantity'] < 1) {
                        $stock_status = "<span class='stock-status out-of-stock'>Out of Stock</span>";
                    } elseif ($product['product_quantity'] < 5) {
                        $stock_status = "<span class='stock-status limited-stock'>Limited Stock</span>";
                    } else {
                        $stock_status = "<span class='stock-status in-stock'>In Stock</span>";
                    }
            ?>
            <div class="col">
                <div class="card product-card">
                    <img src="<?php echo htmlspecialchars($product_image); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['product_name']); ?>" onerror="this.src='../uploads/products/default-product.jpg';">
                    <div class="card-body">
                        <h5 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                        <p class="product-price">₹ <?php echo number_format($product['selling_price'], 2); ?></p>
                        <p class="text-muted small">Available: <?php echo $stock_status; ?></p>
                        
                        <form method="post" action="">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="quantity-control">
                                <div class="quantity-btn minus" onclick="updateQuantity(this, -1)">-</div>
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['product_quantity']; ?>" class="quantity-input" readonly>
                                <div class="quantity-btn plus" onclick="updateQuantity(this, 1, <?php echo $product['product_quantity']; ?>)">+</div>
                            </div>
                            
                            <div class="action-buttons">
                                <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary" <?php echo ($product['product_quantity'] < 1) ? 'disabled' : ''; ?>>Add to Cart</button>
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-secondary">View Details</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-info">No products available at the moment.</div></div>';
            }
            ?>
        </div>
    </div>

    <!-- Profile Update Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">Update Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="address_line1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($user['address_line1']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code']); ?>" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_profile" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQuantity(button, change, max = 999) {
            const input = button.parentElement.querySelector('.quantity-input');
            let value = parseInt(input.value) + change;
            
            // Ensure value is within min and max
            value = Math.max(1, Math.min(max, value));
            
            input.value = value;
        }
        
        // Auto-close alerts after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 3000);
        });
    </script>
</body>
</html>