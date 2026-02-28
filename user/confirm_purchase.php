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

// Fetch complete user details
$user = [];
$user_query = $conn->prepare("SELECT username, email, phone, address_line1, city, state, postal_code, profile_image 
                             FROM users WHERE id = ?");
if ($user_query) {
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user = $user_result->fetch_assoc() ?? [];
    $user_query->close();
}

// Fetch cart items
$cart_items = [];
$total_amount = 0;
$cart_query = $conn->prepare("SELECT ci.quantity, p.product_name, p.selling_price, p.product_image 
                             FROM cart_items ci 
                             JOIN products p ON ci.product_id = p.id 
                             JOIN cart c ON ci.cart_id = c.id 
                             WHERE c.user_id = ?");
if ($cart_query) {
    $cart_query->bind_param("i", $user_id);
    $cart_query->execute();
    $cart_result = $cart_query->get_result();
    
    while ($cart_item = $cart_result->fetch_assoc()) {
        $cart_items[] = $cart_item;
        if (isset($cart_item['selling_price']) && isset($cart_item['quantity'])) {
            $total_amount += $cart_item['selling_price'] * $cart_item['quantity'];
        }
    }
    $cart_query->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Purchase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #6c63ff 0%, #4d44db 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .user-profile-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .qrcode-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .total-amount {
            font-size: 1.5rem;
            color: #4d44db;
            font-weight: 700;
        }
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .btn-primary {
            background-color: #6c63ff;
            border-color: #6c63ff;
            padding: 10px 25px;
        }
        .btn-primary:hover {
            background-color: #4d44db;
            border-color: #4d44db;
        }
        .address-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
 

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold">Confirm Your Purchase</h2>
                <p class="text-muted">Review your order details before payment</p>
            </div>
        </div>

        <div class="row">
            <!-- User Details -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-user me-2"></i> Your Information</h4>
                    </div>
                    <div class="card-body text-center">
                      
                        
                        <h4><?php echo htmlspecialchars($user['username'] ?? 'Not available'); ?></h4>
                        <p class="text-muted mb-4">Customer</p>
                        
                        <div class="row text-start">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Email Address</label>
                                <p><?php echo htmlspecialchars($user['email'] ?? 'Not available'); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Phone Number</label>
                                <p><?php echo htmlspecialchars($user['phone'] ?? 'Not available'); ?></p>
                            </div>
                        </div>
                        
                        <div class="address-box">
                            <h5><i class="fas fa-map-marker-alt me-2"></i> Shipping Address</h5>
                            <p class="mb-1"><?php echo htmlspecialchars($user['address_line1'] ?? 'Not specified'); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars(($user['city'] ?? '') . ', ' . ($user['state'] ?? '')); ?></p>
                            <p class="mb-0">Postal Code: <?php echo htmlspecialchars($user['postal_code'] ?? 'Not specified'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-shopping-cart me-2"></i> Order Summary</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cart_items)): ?>
                                        <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['product_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="Product" class="product-img me-3">
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?>
                                                </div>
                                            </td>
                                            <td>₹<?php echo isset($item['selling_price']) ? number_format($item['selling_price'], 2) : '0.00'; ?></td>
                                            <td><?php echo $item['quantity'] ?? 0; ?></td>
                                            <td>₹<?php 
                                                if (isset($item['selling_price']) && isset($item['quantity'])) {
                                                    echo number_format($item['selling_price'] * $item['quantity'], 2);
                                                } else {
                                                    echo '0.00';
                                                }
                                            ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No items in cart</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <h5 class="mb-0">Total Amount:</h5>
                            <h4 class="mb-0 total-amount">₹<?php echo number_format($total_amount, 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-qrcode me-2"></i> Payment Method</h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="qrcode-container">
                            <div id="qrcode"></div>
                            <p class="mt-3 mb-0 text-muted">Scan this QR code to pay via UPI</p>
                        </div>
                        
                        <div class="row justify-content-center mt-4">
                            <div class="col-md-6">
                                <form id="transactionForm">
                                    <div class="mb-3">
                                        <label for="transactionId" class="form-label">Enter UPI Transaction ID</label>
                                        <input type="text" class="form-control form-control-lg" id="transactionId" name="transactionId" required placeholder="Example: 1234567890">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-check-circle me-2"></i> Verify Payment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate UPI QR Code
        const totalAmount = <?php echo $total_amount; ?>;
        const upiId = 'anandha11k@ybl'; // UPI ID
        const upiLink = `upi://pay?pa=${upiId}&pn=E-Commerce%20Store&am=${totalAmount}&cu=INR&tn=Order%20Payment`;

        // Generate QR Code
        const qrcode = new QRCode(document.getElementById("qrcode"), {
            text: upiLink,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Handle Transaction Verification
        document.getElementById('transactionForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const transactionId = document.getElementById('transactionId').value.trim();
            
            if (!transactionId) {
                alert('Please enter a valid transaction ID');
                return;
            }

            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            submitButton.disabled = true;

            // Send verification request
            fetch('verify_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    transactionId: transactionId, 
                    amount: totalAmount,
                    userId: <?php echo $user_id; ?>
                }),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Payment verified successfully! Your order has been placed.');
                    window.location.href = 'order_confirmation.php?order_id=' + data.order_id;
                } else {
                    alert('Payment verification failed: ' + (data.message || 'Please try again.'));
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during payment verification. Please try again.');
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            });
        });
    </script>
</body>
</html>