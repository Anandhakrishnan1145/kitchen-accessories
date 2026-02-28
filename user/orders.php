
<?php
// Start output buffering at the very top
ob_start();
session_start();
require_once '../config.php';

// Redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all orders for this user
$orders_query = $conn->prepare("SELECT esales.*, 
                               (SELECT COUNT(*) FROM esales_items WHERE esales_id = esales.id) as item_count,
                               DATEDIFF(CURRENT_DATE(), esales.created_at) as days_since_order
                               FROM esales 
                               WHERE user_id = ? 
                               ORDER BY created_at DESC");
$orders_query->bind_param("i", $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();

// Process delivery confirmation
if (isset($_POST['confirm_delivery'])) {
    $order_id = $_POST['order_id'];
    $update_query = $conn->prepare("UPDATE esales SET delivery_confirmation = 'confirmed' WHERE id = ? AND user_id = ?");
    $update_query->bind_param("ii", $order_id, $user_id);
    $update_query->execute();
    
    // Clear buffer and redirect
    ob_end_clean();
    header("Location: orders.php");
    exit();
}

// Process order cancellation
if (isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    
    // Add cancellation logic here
    
    // Clear buffer and redirect
    ob_end_clean();
    header("Location: orders.php");
    exit();
}

// Now you can output HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .orders-container {
            padding: 2rem;
            max-width: 1400px;
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
        
        .order-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .order-header {
            background: linear-gradient(135deg, #6c63ff, #4d44db);
            color: white;
            padding: 1rem 1.5rem;
            position: relative;
        }
        
        .order-id {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.2rem;
        }
        
        .order-date {
            opacity: 0.9;
            font-size: 0.85rem;
        }
        
        .order-status {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: white;
            color: #4d44db;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
        }
        
        .order-body {
            padding: 1.5rem;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .order-meta-item {
            margin-bottom: 0.5rem;
        }
        
        .order-meta-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.2rem;
        }
        
        .order-meta-value {
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .order-actions {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-details {
            background: #6c63ff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.85rem;
        }
        
        .btn-details:hover {
            background: #4d44db;
            color: white;
        }
        
        .btn-action-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .empty-state-icon {
            font-size: 3.5rem;
            color: #adb5bd;
            margin-bottom: 1.5rem;
        }
        
        .empty-state-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .empty-state-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn-shop {
            background: #6c63ff;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-shop:hover {
            background: #4d44db;
            color: white;
        }
        
        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
            
            .order-meta {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .btn-action-group {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
<?php include 'navbar.php';?>
    <div class="orders-container">
        <h1 class="page-title">My Orders</h1>
        
        <div class="order-grid">
            <?php if ($orders_result->num_rows > 0): ?>
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                                <div class="order-date">Placed on <?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
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
                            <div class="order-meta">
                                <div class="order-meta-item">
                                    <div class="order-meta-label">Transaction ID</div>
                                    <div class="order-meta-value"><?php echo htmlspecialchars($order['transaction_id']); ?></div>
                                </div>
                                <div class="order-meta-item">
                                    <div class="order-meta-label">Items</div>
                                    <div class="order-meta-value"><?php echo $order['item_count']; ?> items</div>
                                </div>
                                <div class="order-meta-item">
                                    <div class="order-meta-label">Total Amount</div>
                                    <div class="order-meta-value">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                </div>
                                <div class="order-meta-item">
                                    <div class="order-meta-label">Order Status</div>
                                    <div class="order-meta-value">
                                        <?php if ($order['order_confirmation'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($order['delivery_confirmation'] == 'pending'): ?>
                                            <span class="badge bg-primary">Confirmed</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Delivered</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-details">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                            <div class="btn-action-group">
                                <?php if ($order['order_confirmation'] == 'confirmed' && $order['delivery_confirmation'] == 'pending'): ?>
                                    <form method="post">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="confirm_delivery" class="btn btn-success btn-sm">
                                            <i class="fas fa-check me-1"></i> Confirm
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($order['days_since_order'] <= 15 && $order['delivery_confirmation'] == 'pending'): ?>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="cancel_order" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 class="empty-state-title">No Orders Found</h3>
                    <p class="empty-state-text">You haven't placed any orders yet. Start shopping to see your orders here.</p>
                    <a href="userdashboard.php" class="btn-shop">
                        <i class="fas fa-shopping-bag me-1"></i> Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>