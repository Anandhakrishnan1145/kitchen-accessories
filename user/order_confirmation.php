<?php
session_start();
require_once '../config.php';

// Redirect if not logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];


// Fetch order details from esales table
$order = [];
$order_query = $conn->prepare("SELECT e.id, e.order_id, e.created_at as order_date, e.total_amount, 
                              e.transaction_id, e.order_confirmation as payment_status,
                              u.address_line1 as shipping_address, u.city, u.state, u.postal_code
                              FROM esales e
                              JOIN users u ON e.user_id = u.id
                              WHERE e.order_id = ? AND e.user_id = ?");
if ($order_query) {
    $order_query->bind_param("si", $order_id, $user_id);
    $order_query->execute();
    $order_result = $order_query->get_result();
    $order = $order_result->fetch_assoc() ?? [];
    $order_query->close();
    
    if (empty($order)) {
        header("Location: orders.php");
        exit;
    }
}

// Fetch order items from esales_items table
$order_items = [];
$items_query = $conn->prepare("SELECT ei.quantity, ei.price, ei.product_name, p.product_image 
                              FROM esales_items ei
                              JOIN products p ON ei.product_id = p.id 
                              WHERE ei.esales_id = (SELECT id FROM esales WHERE order_id = ?)");
if ($items_query) {
    $items_query->bind_param("s", $order_id);
    $items_query->execute();
    $items_result = $items_query->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
    $items_query->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .order-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-shipped {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .timeline {
            position: relative;
            padding-left: 50px;
            margin: 30px 0;
        }
        .timeline:before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-step {
            position: relative;
            margin-bottom: 30px;
        }
        .timeline-step:last-child {
            margin-bottom: 0;
        }
        .timeline-icon {
            position: absolute;
            left: 0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #6c63ff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        .timeline-content {
            padding-left: 30px;
        }
        .address-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .btn-primary {
            background-color: #6c63ff;
            border-color: #6c63ff;
        }
        .btn-primary:hover {
            background-color: #4d44db;
            border-color: #4d44db;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold">Order Confirmation</h2>
                <p class="text-muted">Thank you for your purchase!</p>
            </div>
        </div>

        <div class="row">
            <!-- Order Summary -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-receipt me-2"></i> Order #<?php echo htmlspecialchars($order['order_id']); ?></h4>
                        <span class="order-status status-<?php echo strtolower($order['payment_status']); ?>">
                            <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <!-- Order Timeline -->
                        <div class="timeline">
                            <div class="timeline-step">
                                <div class="timeline-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="timeline-content">
                                    <h5>Order Placed</h5>
                                    <p class="text-muted mb-0"><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></p>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="timeline-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h5>Payment Confirmed</h5>
                                    <p class="text-muted mb-0">Transaction ID: <?php echo htmlspecialchars($order['transaction_id']); ?></p>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="timeline-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="timeline-content">
                                    <h5>Order Processed</h5>
                                    <p class="text-muted mb-0">Your items are being prepared for shipment</p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <h5 class="mb-3"><i class="fas fa-box-open me-2"></i> Order Items</h5>
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
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['product_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="Product" class="product-img me-3">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                            </div>
                                        </td>
                                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <h5 class="mb-0">Total Amount:</h5>
                            <h4 class="mb-0">₹<?php echo number_format($order['total_amount'], 2); ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Details Sidebar -->
            <div class="col-lg-4">
                <!-- Shipping Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-truck me-2"></i> Shipping Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="address-box">
                            <h5>Shipping Address</h5>
                            <p class="mb-1"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($order['city'] . ', ' . $order['state']); ?></p>
                            <p class="mb-0">Postal Code: <?php echo htmlspecialchars($order['postal_code']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i> Payment Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Payment Method:</span>
                            <span>UPI Payment</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Transaction ID:</span>
                            <span><?php echo htmlspecialchars($order['transaction_id']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Payment Status:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-cog me-2"></i> Order Actions</h4>
                    </div>
                    <div class="card-body">
                        <a href="contact.php" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-headset me-2"></i> Contact Support
                        </a>
                        <a href="orders.php" class="btn btn-primary w-100">
                            <i class="fas fa-list me-2"></i> View All Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>