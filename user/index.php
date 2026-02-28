
<?php
// dashboard.php
include 'navbar.php';
require_once '../config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get recent orders
$orders_query = "SELECT * FROM esales WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

// Get cart items count
$cart_count_query = "SELECT COUNT(*) as count FROM cart_items ci 
                    JOIN cart c ON ci.cart_id = c.id 
                    WHERE c.user_id = ?";
$stmt = $conn->prepare($cart_count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_count_result = $stmt->get_result();
$cart_count = $cart_count_result->fetch_assoc()['count'];

// Handle Add to Cart functionality
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
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
        } else {
            $error_message = "⚠️ Sorry! Not enough stock available.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Traditional Kitchenware</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5D3FD3;
            --secondary-color: #f39c12;
            --dark-color: #222831;
            --light-color: #f5f5f5;
        }
        
        .product-card {
            transition: all 0.4s ease;
            border: none;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-bottom: 3px solid var(--primary-color);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .product-card img {
            transition: transform 0.8s ease;
            height: 200px;
            width: 100%;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        
        .product-card:hover img {
            transform: scale(1.05);
        }
        
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .selling-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .btn-cart {
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #ff5252;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: var(--primary-color);
            color: white;
        }
        
        .quantity-btn:hover {
            background-color: #ff5252;
        }
        
        .quantity-input {
            width: 50px;
            height: 30px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: bold;
            margin: 0 10px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .stock-badge {
            font-weight: 600;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.7rem;
            display: inline-block;
        }
        
        .in-stock {
            background-color: #28a745;
            color: white;
        }
        
        .low-stock {
            background-color: #ffc107;
            color: black;
        }
        
        .out-of-stock {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Alerts -->
    <div class="container mt-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="container">
        <h2 class="mb-4"></h2>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <p class="card-text fs-4">
                            <?php 
                            $total_orders_query = "SELECT COUNT(*) as count FROM esales WHERE user_id = ?";
                            $stmt = $conn->prepare($total_orders_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $total_orders_result = $stmt->get_result();
                            echo $total_orders_result->fetch_assoc()['count'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Completed Orders</h5>
                        <p class="card-text fs-4">
                            <?php 
                            $completed_orders_query = "SELECT COUNT(*) as count FROM esales 
                                                    WHERE user_id = ? AND delivery_confirmation = 'confirmed'";
                            $stmt = $conn->prepare($completed_orders_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $completed_orders_result = $stmt->get_result();
                            echo $completed_orders_result->fetch_assoc()['count'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Pending Orders</h5>
                        <p class="card-text fs-4">
                            <?php 
                            $pending_orders_query = "SELECT COUNT(*) as count FROM esales 
                                                  WHERE user_id = ? AND delivery_confirmation = 'pending'";
                            $stmt = $conn->prepare($pending_orders_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $pending_orders_result = $stmt->get_result();
                            echo $pending_orders_result->fetch_assoc()['count'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Recent Orders</h5>
            </div>
            <div class="card-body">
                <?php if ($orders_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($order['delivery_confirmation'] == 'confirmed'): ?>
                                            <span class="badge bg-success">Delivered</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Processing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
                <?php else: ?>
                    <p>You haven't placed any orders yet.</p>
                    <a href="products.php" class="btn btn-primary">Browse Products</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recommended Products -->
        <div class="card">
            <div class="card-header">
                <h5>Recommended Traditional Kitchenware</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $products_query = "SELECT * FROM products ORDER BY RAND() LIMIT 4";
                    $products_result = $conn->query($products_query);
                    
                    if ($products_result->num_rows > 0):
                        while ($product = $products_result->fetch_assoc()):
                            // Calculate discount percentage
                            $discount = 0;
                            if ($product['product_price'] > 0) {
                                $discount = round((($product['product_price'] - $product['selling_price']) / $product['product_price'] * 100));
                            }

                            // Stock status
                            if ($product['product_quantity'] < 1) {
                                $stock_class = "out-of-stock";
                                $stock_text = "Out of Stock";
                            } elseif ($product['product_quantity'] < 5) {
                                $stock_class = "low-stock";
                                $stock_text = "Low Stock";
                            } else {
                                $stock_class = "in-stock";
                                $stock_text = "In Stock";
                            }
                    ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 product-card">
                            <div class="position-relative overflow-hidden">
                                <img src="<?php echo htmlspecialchars($product['product_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php if ($discount > 0): ?>
                                    <span class="discount-badge"><?= $discount; ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                
                                <?php if ($discount > 0): ?>
                                    <div class="mb-2">
                                        <span class="original-price">₹<?= number_format($product['product_price'], 2); ?></span>
                                        <span class="selling-price">₹<?= number_format($product['selling_price'], 2); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="selling-price mb-2">₹<?= number_format($product['selling_price'], 2); ?></div>
                                <?php endif; ?>
                                
                                <p class="mb-3"><span class="stock-badge <?= $stock_class; ?>"><?= $stock_text; ?></span></p>

                                <form method="post" action="" class="mt-auto">
                                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn" onclick="updateQuantity(this, -1)">-</button>
                                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['product_quantity']; ?>" class="quantity-input" readonly>
                                        <button type="button" class="quantity-btn" onclick="updateQuantity(this, 1, <?= $product['product_quantity']; ?>)">+</button>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="add_to_cart" class="btn btn-cart" <?= ($product['product_quantity'] < 1) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                        </button>
                                        <a href="product_detail.php?id=<?= $product['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-2"></i> View Details
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                        endwhile;
                    else:
                    ?>
                    <p>No products available at the moment.</p>
                    <?php endif; ?>
                </div>
                <a href="products.php" class="btn btn-outline-primary">View All Products</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQuantity(button, change, max = 999) {
            const input = button.parentElement.querySelector('.quantity-input');
            let value = parseInt(input.value) + change;

            if (value < 1) {
                value = 1;
            } else if (value > max) {
                value = max;
            }

            input.value = value;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss alerts after 3 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 3000);
        });
    </script>
</body>
</html>