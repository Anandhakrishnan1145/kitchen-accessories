<?php
// Database connection
$db_host = "localhost";
$db_name = "final";
$db_user = "root";
$db_pass = "";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if bill_id is provided
if (!isset($_GET['bill_id']) || empty($_GET['bill_id'])) {
    header("Location: index.php");
    exit();
}

$bill_id = $_GET['bill_id'];

// Get bill details
$bill_query = "SELECT * FROM bills WHERE id = ?";
$stmt = $conn->prepare($bill_query);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill_result = $stmt->get_result();

if ($bill_result->num_rows === 0) {
    echo "Bill not found";
    exit();
}

$bill = $bill_result->fetch_assoc();

// Get bill items
$items_query = "SELECT * FROM bill_items WHERE bill_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$items_result = $stmt->get_result();

$bill_items = array();
while ($item = $items_result->fetch_assoc()) {
    $bill_items[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill #<?php echo $bill['bill_no']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .bill-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .bill-header {
            border-bottom: 2px solid #343a40;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .bill-title {
            font-size: 24px;
            font-weight: bold;
        }
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .customer-info, .bill-details {
            flex: 1;
        }
        .bill-footer {
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
            margin-top: 20px;
            text-align: center;
        }
        .total-row {
            font-weight: bold;
        }
        .grand-total-row {
            font-weight: bold;
            font-size: 18px;
            background-color: #f8f9fa;
        }
        .bill-actions {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background-color: white;
            }
            .bill-container {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <div class="bill-header">
            <div class="row">
                <div class="col-md-6">
                    <div class="bill-title">
                        <i class="fas fa-store me-2"></i>Kitchen Accessories Stores 
                    </div>
                    <div>Premium Kitchen Supplies</div>
                    <div>123 Main Street, sivakasi</div>
                    <div>Phone: +91 8637639525</div>
                </div>
                <div class="col-md-6 text-md-end">
                    <h1>INVOICE</h1>
                    <div><strong>Bill No:</strong> <?php echo $bill['bill_no']; ?></div>
                    <div><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($bill['created_at'])); ?></div>
                    <div><strong>Time:</strong> <?php echo date('h:i A', strtotime($bill['created_at'])); ?></div>
                </div>
            </div>
        </div>
        
        <div class="bill-info">
            <div class="customer-info">
                <h5>Customer Information</h5>
                <div><strong>Name:</strong> <?php echo $bill['customer_name']; ?></div>
                <div><strong>Phone:</strong> <?php echo $bill['customer_phone']; ?></div>
                <?php if(!empty($bill['customer_email'])): ?>
                <div><strong>Email:</strong> <?php echo $bill['customer_email']; ?></div>
                <?php endif; ?>
            </div>
            <div class="bill-details">
                <h5>Payment Information</h5>
                <div><strong>Payment Method:</strong> Cash</div>
                <div><strong>Status:</strong> <span class="badge bg-success">Paid</span></div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Product ID</th>
                        <th>Product</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; foreach($bill_items as $item): ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo $item['product_id']; ?></td>
                        <td><?php echo $item['product_name']; ?></td>
                        <td class="text-end">₹<?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end">₹<?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="5" class="text-end">Subtotal:</td>
                        <td class="text-end">₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-end">GST (<?php echo $bill['gst_percentage']; ?>%):</td>
                        <td class="text-end">₹<?php echo number_format($bill['gst_amount'], 2); ?></td>
                    </tr>
                    <tr class="grand-total-row">
                        <td colspan="5" class="text-end">Grand Total:</td>
                        <td class="text-end">₹<?php echo number_format($bill['grand_total'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="bill-footer">
            <p>Thank you for your purchase!</p>
            <p><small>For returns and exchanges, please contact us within 7 days of purchase along with the original bill.</small></p>
        </div>
        
        <div class="bill-actions no-print">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print Bill
            </button>
            <a href="blling.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Billing
            </a>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>