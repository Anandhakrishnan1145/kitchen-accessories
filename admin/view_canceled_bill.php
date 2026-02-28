<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if bill ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: sales_report.php");
    exit();
}

$bill_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// Database connection
$conn = new mysqli("localhost", "root", "", "final");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get bill details
$billQuery = "SELECT * FROM bills WHERE id = ?";
$stmt = $conn->prepare($billQuery);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$billResult = $stmt->get_result();

if ($billResult->num_rows == 0) {
    header("Location: sales_report.php");
    exit();
}

$bill = $billResult->fetch_assoc();

// Get bill items
$itemsQuery = "SELECT * FROM bill_items WHERE bill_id = ?";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$itemsResult = $stmt->get_result();

$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify user password
    $password = $_POST['password'];
    $reason = $_POST['reason'];
    
    if (empty($password)) {
        $error_message = "Password is required to cancel the bill.";
    } elseif (empty($reason)) {
        $error_message = "Please provide a reason for cancellation.";
    } else {
        // Check user password
        $userQuery = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $userResult = $stmt->get_result();
        $user = $userResult->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Add to canceled_bills table with discount_amount field
                $cancelQuery = "INSERT INTO canceled_bills (bill_no, customer_name, total_amount, 
                grand_total, reason, canceled_by, canceled_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
$stmt = $conn->prepare($cancelQuery);
$canceledBy = $user['username'];
$stmt->bind_param("ssddss", $bill['bill_no'], $bill['customer_name'], $bill['total_amount'], 
               $bill['grand_total'], $reason, $canceledBy);
                $stmt->execute();
                $canceled_bill_id = $conn->insert_id;
                
                // Add items to canceled_bill_items
                foreach ($items as $item) {
                    $cancelItemQuery = "INSERT INTO canceled_bill_items 
                                      (canceled_bill_id, product_id, product_name, quantity, price, total) 
                                      VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($cancelItemQuery);
                    $stmt->bind_param("issids", $canceled_bill_id, $item['product_id'], $item['product_name'], 
                                     $item['quantity'], $item['price'], $item['total']);
                    $stmt->execute();
                    
                    // Update product inventory - restore quantities
                    $updateProductQuery = "UPDATE products SET product_quantity = product_quantity + ? 
                                          WHERE product_id = ?";
                    $stmt = $conn->prepare($updateProductQuery);
                    $stmt->bind_param("is", $item['quantity'], $item['product_id']);
                    $stmt->execute();
                }
                
                // Delete from original bills tables
                $deleteBillItemsQuery = "DELETE FROM bill_items WHERE bill_id = ?";
                $stmt = $conn->prepare($deleteBillItemsQuery);
                $stmt->bind_param("i", $bill_id);
                $stmt->execute();
                
                $deleteBillQuery = "DELETE FROM bills WHERE id = ?";
                $stmt = $conn->prepare($deleteBillQuery);
                $stmt->bind_param("i", $bill_id);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                $success_message = "Bill has been successfully canceled. Redirecting to sales report...";
                // Redirect after 3 seconds
                header("refresh:3;url=sales_report.php");
                
            } catch (Exception $e) {
                // Roll back transaction on error
                $conn->rollback();
                $error_message = "Error canceling bill: " . $e->getMessage();
            }
        } else {
            $error_message = "Incorrect password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Bill - <?php echo $bill['bill_no']; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cancel-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
        .bill-summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .warning-text {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Cancel Bill</h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="sales_report.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Sales Report</a>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
        <?php else: ?>
        
        <div class="cancel-warning">
            <h5><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h5>
            <p>You are about to cancel bill <strong><?php echo $bill['bill_no']; ?></strong>. This action cannot be undone and the products will be returned to inventory.</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="bill-summary">
                    <h5>Bill Summary</h5>
                    <p><strong>Bill No:</strong> <?php echo $bill['bill_no']; ?></p>
                    <p><strong>Customer:</strong> <?php echo $bill['customer_name']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('d-m-Y h:i A', strtotime($bill['created_at'])); ?></p>
                    <p><strong>Total Items:</strong> <?php echo count($items); ?></p>
                    <p><strong>Grand Total:</strong> ₹<?php echo number_format($bill['grand_total'], 2); ?></p>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-trash"></i> Cancel Bill</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Cancellation <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Confirm Your Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Please enter your password to confirm cancellation.</div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="confirmCheck" required>
                                <label class="form-check-label" for="confirmCheck">I confirm that I want to cancel this bill</label>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger" id="cancelBtn" disabled>
                                    <i class="fas fa-trash"></i> Cancel Bill
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive mt-4">
            <h5>Items to be Canceled:</h5>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo $item['product_id']; ?></td>
                        <td><?php echo $item['product_name']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td>₹<?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">Sub Total:</th>
                        <th colspan="2">₹<?php echo number_format($bill['total_amount'], 2); ?></th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-end">Grand Total:</th>
                        <th colspan="2">₹<?php echo number_format($bill['grand_total'], 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable the cancel button only when checkbox is checked
        document.getElementById('confirmCheck').addEventListener('change', function() {
            document.getElementById('cancelBtn').disabled = !this.checked;
        });
    </script>
</body>
</html>