<?php
session_start();
require_once '../config.php';

// Redirect if not admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle order actions (confirm/cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['action'];
    
    // Validate action
    if (in_array($action, ['confirm', 'cancel'])) {
        $new_status = ($action === 'confirm') ? 'confirmed' : 'cancelled';
        
        $stmt = $conn->prepare("UPDATE esales SET order_confirmation = ? WHERE order_id = ?");
        $stmt->bind_param("ss", $new_status, $order_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => "Order #$order_id has been " . ($action === 'confirm' ? 'confirmed' : 'cancelled') . " successfully!"
            ];
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => "Failed to update order status. Please try again."
            ];
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch all orders with user information
$orders_query = $conn->query("
    SELECT e.*, u.username 
    FROM esales e 
    JOIN users u ON e.user_id = u.id 
    ORDER BY e.created_at DESC
");
$orders = $orders_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders | Order Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #4d44db;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(108, 99, 255, 0.2);
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            min-width: 80px;
            display: inline-block;
            text-align: center;
        }
        
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 2px;
            transition: all 0.2s;
            border: none;
            color: white;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
            color: white;
        }
        
        .btn-view {
            background-color: var(--primary-color);
        }
        
        .btn-view:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-confirm {
            background-color: var(--success-color);
        }
        
        .btn-confirm:hover {
            background-color: #218838;
        }
        
        .btn-cancel {
            background-color: var(--danger-color);
        }
        
        .btn-cancel:hover {
            background-color: #c82333;
        }
        
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideIn 0.5s, fadeOut 0.5s 2.5s forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>
</head>
<body>
<?php include "navbar.php"?>
    
    <!-- Flash Message Display -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="flash-message alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    
    <div class="container py-4">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-cart-check"></i> Order Management</h1>
                    <p class="mb-0">Manage customer orders and status</p>
                </div>
                <div class="bg-white p-2 rounded-circle">
                    <i class="bi bi-shop text-primary fs-3"></i>
                </div>
            </div>
        </div>
        
        <div class="table-responsive table-container">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Order Status</th>
                        <th>Delivery</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        $orderStatusClass = 'badge-' . $order['order_confirmation'];
                        $deliveryStatusClass = 'badge-' . $order['delivery_confirmation'];
                        $orderDate = date('M d, Y h:i A', strtotime($order['created_at']));
                    ?>
                    <tr>
                        <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                        <td><?= htmlspecialchars($order['username']) ?></td>
                        <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <span class="status-badge <?= $orderStatusClass ?>">
                                <?= htmlspecialchars($order['order_confirmation']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= $deliveryStatusClass ?>">
                                <?= htmlspecialchars($order['delivery_confirmation']) ?>
                            </span>
                        </td>
                        <td><?= $orderDate ?></td>
                        <td>
                            <div class="d-flex">
                                <!-- View Button -->
                                <a href="view_order.php?order_id=<?= $order['order_id'] ?>" 
                                   class="action-btn btn-view" 
                                   title="View Order Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                
                                <!-- Confirm/Cancel Buttons (only show if order is pending) -->
                                <?php if ($order['order_confirmation'] === 'pending'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <input type="hidden" name="action" value="confirm">
                                    <button type="submit" class="action-btn btn-confirm" title="Confirm Order">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                                
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="action-btn btn-cancel" title="Cancel Order">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss flash message after 3 seconds
        setTimeout(() => {
            const flashMessage = document.querySelector('.flash-message');
            if (flashMessage) {
                flashMessage.style.display = 'none';
            }
        }, 3000);
        
        // Add animation to action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'scale(1.2)';
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>