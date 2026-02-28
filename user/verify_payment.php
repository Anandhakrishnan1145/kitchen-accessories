<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$transactionId = $data['transactionId'];
$amount = $data['amount'];
$user_id = $_SESSION['user_id'];

// Validate transaction ID (basic validation)
if (empty($transactionId)) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
    exit;
}

// In a real scenario, you would verify the transaction with a payment gateway
// For now, we'll simulate successful verification
$success = true; // Replace with actual verification logic in production

if ($success) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Generate a unique order ID
        $order_id = 'ORD' . date('YmdHis') . rand(1000, 9999);
        
        // Insert into esales table
        $esales_query = $conn->prepare("INSERT INTO esales (order_id, user_id, transaction_id, total_amount) VALUES (?, ?, ?, ?)");
        $esales_query->bind_param("siss", $order_id, $user_id, $transactionId, $amount);
        $esales_query->execute();
        $esales_id = $conn->insert_id;
        
        if ($esales_id <= 0) {
            throw new Exception("Failed to create order");
        }
        
        // Get user's cart
        $cart_query = $conn->prepare("SELECT c.id FROM cart c WHERE c.user_id = ?");
        $cart_query->bind_param("i", $user_id);
        $cart_query->execute();
        $cart_result = $cart_query->get_result();
        $cart = $cart_result->fetch_assoc();
        
        if (!$cart) {
            throw new Exception("Cart not found");
        }
        
        $cart_id = $cart['id'];
        
        // Fetch cart items
        $cart_items_query = $conn->prepare("SELECT ci.*, p.product_name, p.selling_price 
                                          FROM cart_items ci 
                                          JOIN products p ON ci.product_id = p.id 
                                          WHERE ci.cart_id = ?");
        $cart_items_query->bind_param("i", $cart_id);
        $cart_items_query->execute();
        $cart_items_result = $cart_items_query->get_result();
        
        if ($cart_items_result->num_rows == 0) {
            throw new Exception("Cart is empty");
        }
        
        // Insert into esales_items table
        while ($cart_item = $cart_items_result->fetch_assoc()) {
            $product_id = $cart_item['product_id'];
            $product_name = $cart_item['product_name'];
            $quantity = $cart_item['quantity'];
            $price = $cart_item['selling_price'];
            $total = $price * $quantity;
            
            $esales_items_query = $conn->prepare("INSERT INTO esales_items (esales_id, product_id, product_name, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
            $esales_items_query->bind_param("iisids", $esales_id, $product_id, $product_name, $quantity, $price, $total);
            $esales_items_query->execute();
        }
        
        // Clear the cart
        $clear_cart_query = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $clear_cart_query->bind_param("i", $cart_id);
        $clear_cart_query->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'order_id' => $order_id]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Transaction verification failed']);
}