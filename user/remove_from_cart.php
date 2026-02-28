<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['cart_item_id'])) {
    $cart_item_id = $_GET['cart_item_id'];
    
    // Remove cart item
    $delete_query = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
    $delete_query->bind_param("i", $cart_item_id);
    $delete_query->execute();
    
    header("Location: cart.php");
    exit;
}
?>