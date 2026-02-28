<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "final");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get bill ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Request.");
}

$canceled_bill_id = $_GET['id'];

// Fetch canceled bill details
$detailsQuery = "SELECT * FROM canceled_bills WHERE id = ?";
$stmt = $conn->prepare($detailsQuery);
$stmt->bind_param("i", $canceled_bill_id);
$stmt->execute();
$detailsResult = $stmt->get_result();

if ($detailsResult->num_rows == 0) {
    die("No details found for this canceled bill.");
}

$canceledBillDetails = $detailsResult->fetch_assoc();

// Fetch canceled bill items
$itemsQuery = "SELECT * FROM canceled_bill_items WHERE canceled_bill_id = ?";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $canceled_bill_id);
$stmt->execute();
$itemsResult = $stmt->get_result();

$canceledBillItems = [];
while ($item = $itemsResult->fetch_assoc()) {
    $canceledBillItems[] = $item;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canceled Bill Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <h2><i class="fas fa-ban text-danger"></i> Canceled Bill Details</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="view_canceled_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            <a href="javascript:window.print();" class="btn btn-primary"><i class="fas fa-print"></i> Print</a>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Bill Information</h5>
        </div>
        <div class="card-body">
            <p><strong>Bill No:</strong> <?php echo htmlspecialchars($canceledBillDetails['bill_no']); ?></p>
            <p><strong>Customer:</strong> <?php echo htmlspecialchars($canceledBillDetails['customer_name']); ?></p>
            <p><strong>Total Amount:</strong> ₹<?php echo number_format($canceledBillDetails['total_amount'], 2); ?></p>
            <p><strong>Grand Total:</strong> ₹<?php echo number_format($canceledBillDetails['grand_total'], 2); ?></p>
            <p><strong>Canceled By:</strong> <?php echo htmlspecialchars($canceledBillDetails['canceled_by']); ?></p>
            <p><strong>Canceled At:</strong> <?php echo date('d-m-Y h:i A', strtotime($canceledBillDetails['canceled_at'])); ?></p>
            <?php if (!empty($canceledBillDetails['reason'])): ?>
                <p><strong>Reason:</strong> <?php echo htmlspecialchars($canceledBillDetails['reason']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Canceled Items</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($canceledBillItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td>₹<?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
