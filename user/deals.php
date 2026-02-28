<?php
include 'navbar.php';
include '../config.php';

// Get only visible products
$query = "SELECT p.*, a.agency_name 
          FROM products p 
          LEFT JOIN agencies a ON p.agency_id = a.id 
          WHERE p.is_visible = 1 
          ORDER BY p.created_at DESC";
$result = $conn->query($query);

$products = array();
if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        $products[] = $product;
    }
}

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
    <title>Our Products - Kitchen Accessories Stores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
:root {
    --primary-color: #5D3FD3;
    --secondary-color: #f39c12;
    --accent-color: #2ecc71;
    --dark-color: #222831;
    --light-color: #f5f5f5;
}
        
        body {
            background-color: var(--light-color);
            font-family: 'Montserrat', sans-serif;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            color: var(--dark-color);
            position: relative;
            display: inline-block;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            width: 60%;
            height: 4px;
            background: var(--primary-color);
            bottom: -10px;
            left: 20%;
            border-radius: 10px;
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
        
        .card-body {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 0 0 10px 10px;
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
        
        .btn-details {
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.8rem;
            background-color: transparent;
            transition: all 0.3s ease;
            color: var(--dark-color);
            border: 2px solid var(--dark-color);
        }
        
        .btn-details:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: rgba(41, 47, 54, 0.05);
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

    <!-- Products Section -->
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="page-title display-4 mb-4">Kitchen Accessories Stores</h2>
            <p class="lead text-muted">Discover premium Kitchen Accessories Stores for your culinary adventures</p>
        </div>

        <div class="row">
            <?php if (!empty($products)):
                foreach ($products as $index => $product):
                    $product_image = (!empty($product['product_image']) && file_exists($product['product_image']))
                        ? $product['product_image']
                        : '../uploads/products/default-product.jpg';

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

                    // Animation delay for staggered effect
                    $delay = $index * 100;
            ?>
                    <div class="col-6 col-md-4 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                        <div class="card h-100 product-card">
                            <div class="position-relative overflow-hidden">
                                <img src="<?= htmlspecialchars($product_image); ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($product['product_name']); ?>"
                                     onerror="this.src='../uploads/products/default-product.jpg';">
                                <?php if ($discount > 0): ?>
                                    <span class="discount-badge"><?= $discount; ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold mb-3"><?= htmlspecialchars($product['product_name']); ?></h5>
                                
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
                                            <i class="bi bi-cart-plus-fill me-2"></i> Add to Cart
                                        </button>
                                        <a href="product_detail.php?id=<?= $product['id']; ?>" class="btn btn-details">
                                            <i class="bi bi-eye me-2"></i> View Details
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
            <?php
                endforeach;
            else:
                echo '<div class="col-12"><div class="alert alert-info text-center">No products available at the moment. Please check back later.</div></div>';
            endif;
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS animations
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
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