<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit;
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$product_id = $_GET['id'];

// Get product details
$stmt = $conn->prepare("SELECT p.*, a.agency_name 
                        FROM products p 
                        LEFT JOIN agencies a ON p.agency_id = a.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Product not found
    header("Location: index.php");
    exit;
}

$product = $result->fetch_assoc();
$product_image = !empty($product['product_image']) && file_exists($product['product_image']) 
    ? $product['product_image'] 
    : '../uploads/products/default-product.jpg';

// Get user info
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT username, profile_image FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Fix the profile image path
$profile_image = "../uploads/profilephoto/default-avatar.png"; // Default image
if (!empty($user['profile_image'])) {
    if (strpos($user['profile_image'], '../uploads/profilephoto/') !== false) {
        $profile_image = "../" . $user['profile_image'];
    } else {
        $profile_image = "../uploads/profilephoto/" . $user['profile_image'];
    }
    
    if (!file_exists($profile_image)) {
        $profile_image = "../uploads/profilephoto/default-avatar.png";
    }
}

// Handle Add to Cart functionality
if (isset($_POST['add_to_cart']) || isset($_POST['buy_now'])) {
    $quantity = intval($_POST['quantity']);
    
    // Validate quantity
    if ($quantity < 1) {
        $error_message = "❌ Invalid quantity!";
    } else {
        // Check stock availability
        $check_stock = $conn->prepare("SELECT product_quantity FROM products WHERE id = ?");
        $check_stock->bind_param("i", $product_id);
        $check_stock->execute();
        $stock_result = $check_stock->get_result();
        $stock = $stock_result->fetch_assoc();

        if ($stock && $stock['product_quantity'] >= $quantity) {
            // Check if user already has a cart
            $cart_check = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
            $cart_check->bind_param("i", $_SESSION['user_id']);
            $cart_check->execute();
            $cart_result = $cart_check->get_result();
            $cart = $cart_result->fetch_assoc();

            if (!$cart) {
                // Create a new cart
                $create_cart = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
                $create_cart->bind_param("i", $_SESSION['user_id']);
                $create_cart->execute();
                $cart_id = $conn->insert_id;
            } else {
                $cart_id = $cart['id'];
            }

            // Check if product already exists in cart_items
            $check_cart_item = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
            $check_cart_item->bind_param("ii", $cart_id, $product_id);
            $check_cart_item->execute();
            $cart_item_result = $check_cart_item->get_result();
            $cart_item = $cart_item_result->fetch_assoc();

            if ($cart_item) {
                // Update the existing cart item
                $new_quantity = $cart_item['quantity'] + $quantity;

                // Ensure we don't exceed available stock
                if ($new_quantity > $stock['product_quantity']) {
                    $error_message = "⚠️ Not enough stock available for this product!";
                } else {
                    $update_cart_item = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                    $update_cart_item->bind_param("ii", $new_quantity, $cart_item['id']);
                    $update_cart_item->execute();
                    $success_message = "✅ Cart updated successfully!";
                }
            } else {
                // Add a new product to cart_items
                $insert_cart_item = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
                $insert_cart_item->bind_param("iii", $cart_id, $product_id, $quantity);
                $insert_cart_item->execute();
                $success_message = "🎉 Product added to cart successfully!";
            }
            
            // If Buy Now was clicked, redirect to checkout
            if (isset($_POST['buy_now']) && !isset($error_message)) {
                header("Location: confirm_purchase.php");
                exit;
            }
        } else {
            $error_message = "⚠️ Sorry! Not enough stock available.";
        }
    }
}

// Count items in cart
$cart_count = 0;
$cart_count_query = "SELECT COUNT(*) as count FROM cart_items ci 
                    JOIN cart c ON ci.cart_id = c.id 
                    WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_count_result = $stmt->get_result();
$cart_count = $cart_count_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Product Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --accent-color: #f59e0b;
            --light-bg: #f8fafc;
            --dark-text: #1e293b;
            --light-text: #f1f5f9;
        }
        
        body {
            background-color: #f3f4f6;
            color: var(--dark-text);
            font-family: 'Poppins', sans-serif;
        }
        
        .product-container {
            max-width: 1200px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 0;
        }
        
        .product-image-container {
            height: 500px;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        .product-image-large {
            max-height: 450px;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        
        .product-image-large:hover {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 2rem;
        }
        
        .product-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }
        
        .product-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 2rem;
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .quantity-btn {
            background: var(--light-bg);
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
            border: none;
            color: var(--primary-color);
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .quantity-input {
            width: 70px;
            text-align: center;
            border: none;
            height: 45px;
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn-add-cart, .btn-buy-now {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .btn-add-cart {
            background-color: #fff;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            flex: 1;
        }
        
        .btn-add-cart:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-buy-now {
            background-color: var(--accent-color);
            color: white;
            border: none;
            flex: 1;
        }
        
        .btn-buy-now:hover {
            background-color: #e97e06;
            transform: translateY(-2px);
        }
        
        .product-badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-right: 0.5rem;
        }
        
        .badge-type {
            background-color: #10b981;
            color: white;
        }
        
        .badge-category {
            background-color: var(--primary-color);
            color: white;
        }
        
        .product-details {
            margin-top: 2rem;
            background-color: var(--light-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
        }
        
        .details-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-text);
            display: flex;
            align-items: center;
        }
        
        .details-title i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .breadcrumb {
            padding: 1rem 2rem;
            background-color: transparent;
            margin-bottom: 0;
        }
        
        .breadcrumb-item a {
            color: #6b7280;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }
        
        .breadcrumb-item.active {
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .breadcrumb-item+.breadcrumb-item::before {
            content: "›";
            color: #9ca3af;
        }
        
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
            border-radius: 0.5rem;
            padding: 1rem 1.5rem;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
            border-radius: 0.5rem;
            padding: 1rem 1.5rem;
            border-left: 4px solid #ef4444;
        }
        
        .availability {
            display: inline-flex;
            align-items: center;
            font-weight: 600;
            margin: 1rem 0;
            padding: 0.5rem 1rem;
            background-color: #f3f4f6;
            border-radius: 0.5rem;
        }
        
        .availability-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #10b981;
            margin-right: 0.5rem;
        }
        
        .out-of-stock {
            background-color: #fef2f2;
            color: #991b1b;
        }
        
        .out-of-stock .availability-dot {
            background-color: #ef4444;
        }
        
        @media (max-width: 768px) {
            .product-container {
                margin: 1rem;
            }
            
            .product-image-container {
                height: 350px;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php
    include 'navbar.php';
    ?>
    
    <div class="container product-container">
        <?php if(isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['product_name']); ?></li>
            </ol>
        </nav>
        
        <div class="row g-0">
            <div class="col-md-6">
                <div class="product-image-container">
                    <img src="<?php echo htmlspecialchars($product_image); ?>" class="product-image-large" alt="<?php echo htmlspecialchars($product['product_name']); ?>" onerror="this.src='../uploads/products/default-product.jpg';">
                </div>
            </div>
            <div class="col-md-6">
                <div class="product-info">
                    <h2 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h2>
                    
                    <div>
                        <span class="product-badge badge-type"><?php echo htmlspecialchars($product['material']); ?></span>
                        <span class="product-badge badge-category"><?php echo htmlspecialchars($product['product_category']); ?></span>
                    </div>
                    
                    <div class="my-3">
                        <p class="product-price">₹ <?php echo number_format($product['selling_price'], 2); ?></p>
                        <?php if ($product['product_price'] > $product['selling_price']): ?>
                            <small class="text-muted"><del>₹ <?php echo number_format($product['product_price'], 2); ?></del></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="availability <?php echo ($product['product_quantity'] < 1) ? 'out-of-stock' : ''; ?>">
                        <span class="availability-dot"></span>
                        <?php if ($product['product_quantity'] > 0): ?>
                            Available Quantity: <strong class="ms-1"><?php echo $product['product_quantity']; ?></strong>
                        <?php else: ?>
                            <strong>Out of Stock</strong>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post">
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn minus" onclick="updateQuantity(this, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['product_quantity']; ?>" class="quantity-input" readonly>
                            <button type="button" class="quantity-btn plus" onclick="updateQuantity(this, 1, <?php echo $product['product_quantity']; ?>)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" name="add_to_cart" class="btn btn-add-cart" <?php echo ($product['product_quantity'] < 1) ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus me-2"></i>Add to Cart
                            </button>
                            <button type="submit" name="buy_now" class="btn btn-buy-now" <?php echo ($product['product_quantity'] < 1) ? 'disabled' : ''; ?>>
                                <i class="fas fa-bolt me-2"></i>Buy Now
                            </button>
                        </div>
                    </form>
                    
                    <div class="product-details">
                        <h4 class="details-title"><i class="fas fa-info-circle"></i> Product Details</h4>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Product ID</span>
                                <span class="detail-value"><?php echo htmlspecialchars($product['product_id']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Brand/Agency</span>
                                <span class="detail-value"><?php echo htmlspecialchars($product['agency_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Type</span>
                                <span class="detail-value"><?php echo htmlspecialchars($product['material']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Category</span>
                                <span class="detail-value"><?php echo htmlspecialchars($product['product_category']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQuantity(button, change, max = 999) {
            const input = button.closest('.quantity-control').querySelector('.quantity-input');
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