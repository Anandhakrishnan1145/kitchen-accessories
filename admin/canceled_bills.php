<?php
// Start session and include config
session_start();
include '../config.php';

// Check admin status
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get all canceled bills from database
$query = "SELECT * FROM canceled_bills ORDER BY canceled_at DESC";
$result = $conn->query($query);

$canceled_bills = array();
if ($result->num_rows > 0) {
    while ($bill = $result->fetch_assoc()) {
        $canceled_bills[] = $bill;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canceled Bills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            padding-top: 80px;
            background-color: #f8f9fa;
        }
        .table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .grand-total {
            color: #dc3545;
            font-weight: bold;
        }
        .actions .btn {
            margin-right: 5px;
        }
        .canceled-row {
            background-color: #fff3f3;
        }
    </style>
</head>
<body>
    <?php include "navbar.php"; ?>
    
    <div class="container py-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Canceled Bills</h1>
            <a href="bills.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Active Bills
            </a>
        </div>
        
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Customer Name</th>
                            <th>Total Amount</th>
                            <th>Grand Total</th>
                            <th>Cancel Reason</th>
                            <th>Canceled By</th>
                            <th>Cancel Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($canceled_bills as $bill): ?>
                            <tr class="canceled-row">
                                <td><?= htmlspecialchars($bill['bill_no']); ?></td>
                                <td><?= htmlspecialchars($bill['customer_name']); ?></td>
                                <td>₹<?= number_format($bill['total_amount'], 2); ?></td>
                                <td class="grand-total">₹<?= number_format($bill['grand_total'], 2); ?></td>
                                <td><?= htmlspecialchars($bill['reason']); ?></td>
                                <td><?= htmlspecialchars($bill['canceled_by']); ?></td>
                                <td><?= date('d M Y h:i A', strtotime($bill['canceled_at'])); ?></td>
                                <td class="actions">
                                    <a href="view_cancel_bill.php?id=<?= $bill['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="View Canceled Bill">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>