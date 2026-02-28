<?php
// Start output buffering and session
ob_start();
session_start();
require_once '../config.php';
include 'navbar.php';

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Get the order ID from the URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get order details
$order_query = $conn->prepare("SELECT esales.*, 
                               (SELECT COUNT(*) FROM esales_items WHERE esales_id = esales.id) as item_count,
                               DATEDIFF(CURRENT_DATE(), esales.created_at) as days_since_order
                               FROM esales 
                               WHERE id = ? AND user_id = ?");
$user_id = $_SESSION['user_id'];
$order_query->bind_param("ii", $order_id, $user_id);
$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows === 0) {
    // No order found
    header("Location: orders.php");
    exit();
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = $conn->prepare("SELECT * FROM esales_items WHERE esales_id = ?");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();

// Process delivery confirmation
if (isset($_POST['confirm_delivery'])) {
    $update_query = $conn->prepare("UPDATE esales SET delivery_confirmation = 'confirmed' WHERE id = ? AND user_id = ?");
    $update_query->bind_param("ii", $order_id, $user_id);
    $update_query->execute();
    
    // Clear buffer and redirect
    ob_end_clean();
    header("Location: order-details.php?id=" . $order_id);
    exit();
}

// Process order cancellation
if (isset($_POST['cancel_order'])) {
    // Add cancellation logic here
    
    // Clear buffer and redirect
    ob_end_clean();
    header("Location: order-details.php?id=" . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Order #<?php echo htmlspecialchars($order['order_id']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .order-details-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: #6c63ff;
            border-radius: 2px;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .order-header {
            background: linear-gradient(135deg, #6c63ff, #4d44db);
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .order-id {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .order-meta-item {
            margin-bottom: 0.5rem;
        }
        
        .order-meta-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.2rem;
        }
        
        .order-meta-value {
            font-weight: 500;
            font-size: 1rem;
        }
        
        .order-status {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: white;
            color: #4d44db;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .order-body {
            padding: 1.5rem;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        .items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .total-amount {
            font-size: 1.2rem;
            font-weight: 600;
            text-align: right;
            margin-top: 1.5rem;
            padding: 0.75rem;
        }
        
        .order-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .btn-back {
            background: #6c63ff;
            color: white;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: #4d44db;
            color: white;
        }
        
        .btn-action-group {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn-action {
            padding: 0.6rem 1.25rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            font-size: 0.9rem;
        }
        
        .btn-confirm {
            background: #28a745;
            color: white;
        }
        
        .btn-confirm:hover {
            background: #218838;
            color: white;
        }
        
        .btn-cancel {
            background: #dc3545;
            color: white;
        }
        
        .btn-cancel:hover {
            background: #c82333;
            color: white;
        }
        
        @media (max-width: 768px) {
            .order-meta {
                flex-direction: column;
                gap: 1rem;
            }
            
            .order-status {
                position: static;
                display: inline-block;
                margin-top: 1rem;
            }
            
            .order-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn-action-group {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="order-details-container">
        <h1 class="page-title">Order Details</h1>
        
        <div class="order-card">
            <div class="order-header">
                <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                <div class="order-meta">
                    <div class="order-meta-item">
                        <div class="order-meta-label">Order Date</div>
                        <div class="order-meta-value"><?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="order-meta-item">
                        <div class="order-meta-label">Transaction ID</div>
                        <div class="order-meta-value"><?php echo htmlspecialchars($order['transaction_id']); ?></div>
                    </div>
                    <div class="order-meta-item">
                        <div class="order-meta-label">Items</div>
                        <div class="order-meta-value"><?php echo $order['item_count']; ?> items</div>
                    </div>
                </div>
                <div class="order-status">
                    <?php if ($order['order_confirmation'] == 'pending'): ?>
                        Pending
                    <?php elseif ($order['delivery_confirmation'] == 'pending'): ?>
                        Confirmed
                    <?php else: ?>
                        Delivered
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="order-body">
                <h4 class="mb-4">Order Items</h4>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="total-amount">
                    Total Amount: ₹<?php echo number_format($order['total_amount'], 2); ?>
                </div>
                
                <div class="order-actions">
                    <a href="orders.php" class="btn-back">
                        <i class="fas fa-arrow-left me-1"></i> Back to Orders
                    </a>
                    
                    <?php if ($order['order_confirmation'] == 'confirmed' && $order['delivery_confirmation'] == 'pending'): ?>
                        <div class="btn-action-group">
                            <form method="post">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="confirm_delivery" class="btn-action btn-confirm">
                                    <i class="fas fa-check me-1"></i> Confirm Delivery
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['days_since_order'] <= 15 && $order['delivery_confirmation'] == 'pending'): ?>
                        <div class="btn-action-group">
                            <form method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="cancel_order" class="btn-action btn-cancel">
                                    <i class="fas fa-times me-1"></i> Cancel Order
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>