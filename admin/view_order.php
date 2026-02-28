<?php
session_start();
require_once '../config.php';

// Redirect if not admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$order_id = $_GET['order_id'];

// Fetch order details along with user's shipping information
$order_query = $conn->prepare("SELECT e.*, u.username, u.email, u.phone, u.address_line1, u.city, u.state, u.postal_code 
                               FROM esales e 
                               JOIN users u ON e.user_id = u.id 
                               WHERE e.order_id = ?");
$order_query->bind_param("s", $order_id);
$order_query->execute();
$order_result = $order_query->get_result();
$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = $conn->prepare("SELECT * FROM esales_items WHERE esales_id = (SELECT id FROM esales WHERE order_id = ?)");
$items_query->bind_param("s", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Order Details</h2>
        <div class="row">
            <div class="col-md-6">
                <h4>Order Information</h4>
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($order['transaction_id']); ?></p>
                <p><strong>Total Amount:</strong> ₹ <?php echo number_format($order['total_amount'], 2); ?></p>
                <p><strong>Order Confirmation:</strong> <?php echo htmlspecialchars($order['order_confirmation']); ?></p>
                <p><strong>Delivery Confirmation:</strong> <?php echo htmlspecialchars($order['delivery_confirmation']); ?></p>
            </div>
            <div class="col-md-6">
                <h4>Shipping Details</h4>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address_line1']); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($order['state']); ?></p>
                <p><strong>Postal Code:</strong> <?php echo htmlspecialchars($order['postal_code']); ?></p>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <h4>Order Items</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₹ <?php echo number_format($item['price'], 2); ?></td>
                            <td>₹ <?php echo number_format($item['total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>