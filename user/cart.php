<?php
session_start();
require_once '../config.php';

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit;
}
include 'navbar.php';
$user_id = $_SESSION['user_id'];

// Count items in cart
$cart_count = 0;
$cart_query = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE c.user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();
$cart_data = $cart_result->fetch_assoc();
$cart_count = $cart_data['total'] ?? 0;

// Fetch cart items
$cart_query = $conn->prepare("SELECT ci.*, p.product_name, p.selling_price, p.product_image 
                              FROM cart_items ci 
                              JOIN products p ON ci.product_id = p.id 
                              JOIN cart c ON ci.cart_id = c.id 
                              WHERE c.user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #4d44db;
            --accent-color: #ff6584;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }
        
        body {
            background-color: #f5f7ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .page-title {
            color: var(--secondary-color);
            font-weight: 700;
            position: relative;
            display: inline-block;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60%;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: none;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
            border: 1px solid #eee;
        }
        
        .product-name {
            font-weight: 500;
            color: #333;
        }
        
        .quantity-control .input-group {
            width: 120px;
        }
        
        .quantity-control .form-control {
            text-align: center;
        }
        
        .total-section {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .total-amount {
            font-size: 1.2rem;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-cart h3 {
            color: #555;
            font-weight: 600;
        }
        
        .modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        #qrcode {
            margin: 0 auto;
            padding: 15px;
            background: white;
            border-radius: 8px;
            display: inline-block;
            border: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                border-radius: 12px 12px 0 0;
                overflow: hidden;
            }
            
            .product-img {
                width: 40px;
                height: 40px;
            }
            
            .total-section {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
  

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col">
                <h2 class="page-title">Your Shopping Cart</h2>
                <p class="text-muted mt-2">Review and manage your items before checkout</p>
            </div>
        </div>
        
        <?php if($cart_result->num_rows > 0): ?>
        <div class="card mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Product</th>
                                <th style="width: 15%;">Price</th>
                                <th style="width: 15%;">Quantity</th>
                                <th style="width: 15%;">Total</th>
                                <th style="width: 15%;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_amount = 0;
                            while($cart_item = $cart_result->fetch_assoc()):
                                $total_amount += $cart_item['selling_price'] * $cart_item['quantity'];
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($cart_item['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($cart_item['product_name']); ?>" 
                                             class="product-img">
                                        <span class="product-name"><?php echo htmlspecialchars($cart_item['product_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">
                                        ₹ <?php echo number_format($cart_item['selling_price'], 2); ?>
                                    </div>
                                </td>
                                <td>
                                    <form action="update_cart.php" method="post" class="quantity-control">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $cart_item['id']; ?>">
                                        <div class="input-group">
                                            <input type="number" name="quantity" value="<?php echo $cart_item['quantity']; ?>" 
                                                min="1" class="form-control form-control-sm" style="max-width: 70px;">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <div class="fw-bold">
                                        ₹ <?php echo number_format($cart_item['selling_price'] * $cart_item['quantity'], 2); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="remove_from_cart.php?cart_item_id=<?php echo $cart_item['id']; ?>" 
                                       class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash-alt me-1"></i> Remove
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4">
                <div class="total-section p-4">
                    <h5 class="mb-4 fw-bold text-uppercase" style="color: var(--secondary-color);">Order Summary</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-uppercase text-secondary">Subtotal:</span>
                        <span class="fw-bold">₹ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-uppercase text-secondary">Shipping:</span>
                        <span class="fw-bold text-success">FREE</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-uppercase">Total Amount:</span>
                        <span class="total-amount">₹ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <a href="confirm_purchase.php" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-lock me-2"></i> Proceed to Checkout
                    </a>
                   
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any products to your cart yet.</p>
            <a href="userdashboard.php" class="btn btn-primary px-4 py-2">
                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="fas fa-qrcode me-2"></i> UPI Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div id="qrcode"></div>
                        <p class="mt-3 mb-1">Scan the QR code to pay via UPI</p>
                        <h4 class="fw-bold">₹ <?php echo number_format($total_amount, 2); ?></h4>
                    </div>
                    <form id="transactionForm" class="mt-4">
                        <div class="mb-3">
                            <label for="transactionId" class="form-label fw-bold">
                                <i class="fas fa-receipt me-1"></i> Transaction ID
                            </label>
                            <input type="text" class="form-control" id="transactionId" name="transactionId" 
                                   placeholder="Enter UPI transaction ID" required>
                            <div class="form-text">Enter the transaction ID received after completing payment</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check-circle me-1"></i> Verify Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate UPI QR Code
        const totalAmount = <?php echo $total_amount; ?>;
        const upiId = 'anandha11k@ybl'; // UPI ID
        const upiLink = `upi://pay?pa=${upiId}&pn=Your%20Name&am=${totalAmount}&cu=INR`;

        // Generate QR Code
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById("qrcode")) {
                const qrcode = new QRCode(document.getElementById("qrcode"), {
                    text: upiLink,
                    width: 200,
                    height: 200,
                    colorDark: "#3a86ff",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });

        // Handle Transaction Verification
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('transactionForm');
            if (form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    const transactionId = document.getElementById('transactionId').value;

                    // Simulate verification (replace with actual API call)
                    fetch('verify_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ transactionId: transactionId, amount: totalAmount }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Payment verified successfully!');
                            window.location.href = 'order_confirmation.php'; // Redirect to order confirmation page
                        } else {
                            alert('Payment verification failed. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            }
        });
    </script>
</body>
</html>