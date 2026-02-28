<?php
// Start session and database connection
session_start();
include '../config.php';

// Check if user is logged in (optional - remove if not needed)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
include 'navbar.php';
$user_id = $_SESSION['user_id'] ?? 1; // Default to 1 if not set

// Get selected material from URL
$selectedMaterial = isset($_GET['material']) ? $_GET['material'] : '';

// Build the product query based on selected material
if (!empty($selectedMaterial)) {
    $query = "SELECT p.*, a.agency_name 
              FROM products p 
              LEFT JOIN agencies a ON p.agency_id = a.id 
              WHERE p.material = ? AND p.status = 'active' AND p.is_visible = 1
              ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $selectedMaterial);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT p.*, a.agency_name 
              FROM products p 
              LEFT JOIN agencies a ON p.agency_id = a.id 
              WHERE p.status = 'active' AND p.is_visible = 1
              ORDER BY p.created_at DESC";
    $result = $conn->query($query);
}

// Store products in array
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
            $cart_check->bind_param("i", $user_id);
            $cart_check->execute();
            $cart_result = $cart_check->get_result();
            $cart = $cart_result->fetch_assoc();

            if (!$cart) {
                // Create a new cart
                $create_cart = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
                $create_cart->bind_param("i", $user_id);
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
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= !empty($selectedMaterial) ? htmlspecialchars($selectedMaterial) . ' - ' : '' ?>Kitchen Accessories Stores</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- AOS Animation Library -->
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
    overflow-x: hidden;
}

.page-title {
    font-family: 'Playfair Display', serif;
    color: var(--dark-color);
    letter-spacing: -0.5px;
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
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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

.product-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--primary-color);
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
    box-shadow: 0 5px 15px rgba(93, 63, 211, 0.3);
    background-color: #4a2fc1;
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
    background-color: rgba(34, 40, 49, 0.05);
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
    background-color: #4a2fc1;
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

.alert-success {
    background-color: rgba(46, 204, 113, 0.2);
    color: var(--accent-color);
    border-left: 4px solid var(--accent-color);
}

.alert-danger {
    background-color: rgba(243, 156, 18, 0.2);
    color: var(--secondary-color);
    border-left: 4px solid var(--secondary-color);
}

.stock-badge {
    font-weight: 600;
    padding: 0.3rem 0.8rem;
    border-radius: 50px;
    font-size: 0.7rem;
    display: inline-block;
}

footer {
    background-color: var(--dark-color);
    color: white;
    padding: 2rem 0;
    margin-top: 3rem;
    position: relative;
    overflow: hidden;
}

footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(to right, var(--primary-color), var(--secondary-color), var(--accent-color));
}

.material-breadcrumb {
    background-color: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

/* Responsive grid adjustments */
@media (max-width: 575.98px) {
    .product-card img {
        height: 150px;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    .product-card img {
        height: 180px;
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    .product-card img {
        height: 220px;
    }
}

@media (min-width: 992px) {
    .product-card img {
        height: 250px;
    }
}
    </style>
</head>

<body>
    <!-- Include your navbar here -->
  

    <!-- Alerts -->
    <div class="container mt-4">
        <?php if (isset($success_message)) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Products Section -->
    <div class="container py-5">
        <!-- Material breadcrumb -->
        <?php if (!empty($selectedMaterial)) : ?>
            <div class="material-breadcrumb">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="products.php">All Products</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($selectedMaterial) ?></li>
                    </ol>
                </nav>
            </div>
        <?php endif; ?>

        <div class="text-center mb-5">
            <h2 class="page-title display-4 mb-4">
                <?= !empty($selectedMaterial) ? htmlspecialchars($selectedMaterial) . ' Products' : 'Kitchen Accessories Stores' ?>
            </h2>
            <p class="lead text-muted">
                <?= !empty($selectedMaterial) 
                    ? "Premium Kitchen Accessories Stores made with $selectedMaterial" 
                    : "Discover premium Kitchen Accessories Stores for your culinary adventures" ?>
            </p>
        </div>

        <div class="row">
            <?php if (!empty($products)) :
                foreach ($products as $index => $product) :
                    $product_image = (!empty($product['product_image']) && file_exists($product['product_image']))
                        ? $product['product_image']
                        : '../uploads/products/default-product.jpg';

                    // Stock badge
                    if ($product['product_quantity'] < 1) {
                        $stock_status = "<span class='stock-badge bg-danger'>Out of Stock</span>";
                    } elseif ($product['product_quantity'] < 5) {
                        $stock_status = "<span class='stock-badge bg-warning text-dark'>Limited Stock</span>";
                    } else {
                        $stock_status = "<span class='stock-badge bg-success'>In Stock</span>";
                    }

                    // Animation delay for staggered effect
                    $delay = $index * 100;
            ?>
                    <div class="col-6 col-md-4 col-lg-3 mb-4" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                        <div class="card h-100 product-card">
                            <div class="overflow-hidden">
                                <img src="<?= htmlspecialchars($product_image); ?>" class="card-img-top" alt="<?= htmlspecialchars($product['product_name']); ?>" onerror="this.src='../uploads/products/default-product.jpg';">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold mb-3"><?= htmlspecialchars($product['product_name']); ?></h5>
                                <p class="product-price mb-2">₹ <?= number_format($product['selling_price'], 2); ?></p>
                                <p class="mb-3"><?= $stock_status; ?></p>
                                <p class="small text-muted mb-2">Material: <?= htmlspecialchars($product['material']); ?></p>

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
            else :
                echo '<div class="col-12"><div class="alert alert-info text-center">No products available' . (!empty($selectedMaterial) ? ' made with this material' : '') . ' at the moment.</div></div>';
            endif;
            ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container py-4">
            <div class="mb-4">
                <h3 class="h5 mb-3">Kitchen Accessories Stores</h3>
                <p class="mb-0">Elevating your kitchen experience since 2023</p>
            </div>
            <div class="social-icons mb-4">
                <a href="#" class="text-white mx-2"><i class="bi bi-facebook"></i></a>
                <a href="#" class="text-white mx-2"><i class="bi bi-instagram"></i></a>
                <a href="#" class="text-white mx-2"><i class="bi bi-twitter"></i></a>
                <a href="#" class="text-white mx-2"><i class="bi bi-pinterest"></i></a>
            </div>
            <p class="mb-0">© 2025 Kitchen Accessories Stores. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <!-- Scripts -->
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