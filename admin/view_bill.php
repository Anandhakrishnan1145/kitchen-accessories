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

// Get bill items - Corrected query to remove p.product_type which doesn't exist
$itemsQuery = "SELECT bi.*, p.material, p.product_category 
               FROM bill_items bi 
               LEFT JOIN products p ON bi.product_id = p.product_id 
               WHERE bi.bill_id = ?";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$itemsResult = $stmt->get_result();

$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Details - <?php echo $bill['bill_no']; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .bill-header {
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .bill-details {
            margin-bottom: 30px;
        }
        .bill-total {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .print-section {
            margin-top: 30px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4 no-print">
            <div class="col-md-6">
                <h2>Bill Details</h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="sales_report.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Sales Report</a>
                <button onclick="window.print()" class="btn btn-primary ms-2"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <!-- Bill Header -->
                <div class="bill-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h3>Kitchen Supplies Invoice</h3>
                            <p>Authentic Kitchen Supplies and Accessories</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h4>Bill No: <?php echo $bill['bill_no']; ?></h4>
                            <p>Date: <?php echo date('d-m-Y h:i A', strtotime($bill['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Details -->
                <div class="bill-details">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Customer Details:</h5>
                            <p><strong>Name:</strong> <?php echo $bill['customer_name']; ?></p>
                            <p><strong>Phone:</strong> <?php echo $bill['customer_phone']; ?></p>
                            <p><strong>Email:</strong> <?php echo $bill['customer_email']; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Bill Items - Updated to use material instead of product_type -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Product ID</th>
                                <th>Product</th>
                                <th>Material</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            foreach ($items as $item): 
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo $item['product_id']; ?></td>
                                <td><?php echo $item['product_name']; ?></td>
                                <td><?php echo $item['material'] ?? 'N/A'; ?></td>
                                <td><?php echo $item['product_category'] ?? 'N/A'; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td class="text-end">₹<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-end">₹<?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Bill Total -->
                <div class="bill-total">
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-end">₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>GST (<?php echo $bill['gst_percentage']; ?>%):</strong></td>
                                    <td class="text-end">₹<?php echo number_format($bill['gst_amount'], 2); ?></td>
                                </tr>
                                <tr class="table-dark">
                                    <td><strong>Grand Total:</strong></td>
                                    <td class="text-end"><strong>₹<?php echo number_format($bill['grand_total'], 2); ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Thank You Note -->
                <div class="mt-4 text-center">
                    <p>Thank you for your purchase!</p>
                </div>
            </div>
        </div>
        
        <!-- Action Button (Cancel Bill) -->
        <div class="mt-4 text-end no-print">
            <a href="cancel_bill.php?id=<?php echo $bill_id; ?>" class="btn btn-danger"><i class="fas fa-trash"></i> Cancel Bill</a>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>