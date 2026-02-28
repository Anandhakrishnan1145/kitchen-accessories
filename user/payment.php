<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch cart items to calculate total amount
$cart_query = $conn->prepare("SELECT SUM(p.selling_price * c.quantity) as total 
                              FROM cart c 
                              JOIN products p ON c.product_id = p.id 
                              WHERE c.user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();
$cart_data = $cart_result->fetch_assoc();
$total_amount = $cart_data['total'] ?? 0;

// Generate a unique transaction ID
$transaction_id = uniqid('TXN_');

// UPI payment details
$upi_id = "anandha11@ybl"; // Replace with your UPI ID
$qr_data = "upi://pay?pa=$upi_id&pn=Your%20Name&am=$total_amount&tn=$transaction_id";

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    // Save payment details and update order status
    $order_query = $conn->prepare("INSERT INTO orders (user_id, transaction_id, payment_id, total_amount, status) VALUES (?, ?, ?, ?, 'pending')");
    $order_query->bind_param("issd", $user_id, $transaction_id, $payment_id, $total_amount);
    $order_query->execute();
    // Clear the cart
    $clear_cart_query = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart_query->bind_param("i", $user_id);
    $clear_cart_query->execute();
    // Redirect to order confirmation page
    header("Location: order_confirmation.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include qrcode.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">E-commerce Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="userdashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">Cart</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Payment</h2>
        <div class="row">
            <div class="col-md-6">
                <h4>Scan QR Code to Pay</h4>
                <!-- QR Code will be generated here -->
                <div id="qrcode" style="width: 300px; height: 300px;"></div>
                <p>Transaction ID: <?php echo $transaction_id; ?></p>
            </div>
            <div class="col-md-6">
                <h4>Payment Confirmation</h4>
                <form action="payment.php" method="post">
                    <div class="mb-3">
                        <label for="payment_id" class="form-label">Payment ID</label>
                        <input type="text" class="form-control" id="payment_id" name="payment_id" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Confirm Payment</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate QR code using qrcode.js
        const qrData = "<?php echo $qr_data; ?>";
        const qrcode = new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 300,
            height: 300,
        });
    </script>
</body>
</html>