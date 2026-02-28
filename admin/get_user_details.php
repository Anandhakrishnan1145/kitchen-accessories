<?php
// Include your database connection file
include '../config.php';

// Get the user ID from the query parameter
$user_id = $_GET['user_id'];

// Fetch user details from the users table
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_sql);

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
} else {
    die("User not found.");
}

// Fetch cart items for the user
$cart_sql = "SELECT ci.*, p.product_name, p.selling_price 
             FROM cart_items ci 
             JOIN products p ON ci.product_id = p.id 
             WHERE ci.cart_id = (SELECT id FROM cart WHERE user_id = $user_id)";
$cart_result = $conn->query($cart_sql);

$cart_items = [];
if ($cart_result->num_rows > 0) {
    while ($row = $cart_result->fetch_assoc()) {
        $cart_items[] = $row;
    }
}

// Fetch previous orders for the user
$orders_sql = "SELECT e.*, ei.product_name, ei.quantity, ei.price, ei.total 
               FROM esales e 
               JOIN esales_items ei ON e.id = ei.esales_id 
               WHERE e.user_id = $user_id";
$orders_result = $conn->query($orders_sql);

$orders = [];
if ($orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$conn->close();
?>

<div>
    <h4>User Information</h4>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
    <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address_line1']); ?></p>
    <p><strong>City:</strong> <?php echo htmlspecialchars($user['city']); ?></p>
    <p><strong>State:</strong> <?php echo htmlspecialchars($user['state']); ?></p>
    <p><strong>Postal Code:</strong> <?php echo htmlspecialchars($user['postal_code']); ?></p>
    <p><strong>User Type:</strong> <?php echo htmlspecialchars($user['user_type']); ?></p>
    <p><strong>Created At:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>

    <h4>Cart Items</h4>
    <?php if (!empty($cart_items)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($item['selling_price']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No items in the cart.</p>
    <?php endif; ?>

    <h4>Previous Orders</h4>
    <?php if (!empty($orders)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($order['price']); ?></td>
                    <td><?php echo htmlspecialchars($order['total']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No previous orders.</p>
    <?php endif; ?>
</div>