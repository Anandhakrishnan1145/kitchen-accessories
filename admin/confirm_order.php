<?php
session_start();
require_once '../config.php';

// Redirect if not admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$order_id = $_GET['order_id'];

// Update order confirmation status
$confirm_query = $conn->prepare("UPDATE esales SET order_confirmation = 'confirmed' WHERE order_id = ?");
$confirm_query->bind_param("s", $order_id);
$confirm_query->execute();

// Fetch order items to update product stock
$items_query = $conn->prepare("SELECT product_id, quantity FROM esales_items WHERE esales_id = (SELECT id FROM esales WHERE order_id = ?)");
$items_query->bind_param("s", $order_id);
$items_query->execute();
$items_result = $items_query->get_result();

while ($item = $items_result->fetch_assoc()) {
    $update_stock_query = $conn->prepare("UPDATE products SET product_quantity = product_quantity - ? WHERE id = ?");
    $update_stock_query->bind_param("ii", $item['quantity'], $item['product_id']);
    $update_stock_query->execute();
}

header("Location: admin_orders.php");
exit;