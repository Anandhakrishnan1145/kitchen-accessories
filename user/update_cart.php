<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_item_id = $_POST['cart_item_id'];
    $quantity = $_POST['quantity'];
    
    // Update cart item quantity
    $update_query = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
    $update_query->bind_param("ii", $quantity, $cart_item_id);
    $update_query->execute();
    
    header("Location: cart.php");
    exit;
}
?>