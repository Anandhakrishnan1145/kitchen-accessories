<?php
// Start session and include config
session_start();
include '../config.php';

// Check admin status
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get all bills from database
$query = "SELECT * FROM bills ORDER BY created_at DESC";
$result = $conn->query($query);

$bills = array();
if ($result->num_rows > 0) {
    while ($bill = $result->fetch_assoc()) {
        $bills[] = $bill;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Management</title>
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
            <h1 class="h3">Bill Management</h1>
            <a href="billing.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Bill
            </a>
        </div>
        
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Total Amount</th>
                            <th>GST Amount</th>
                            <th>Grand Total</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><?= htmlspecialchars($bill['bill_no']); ?></td>
                                <td><?= htmlspecialchars($bill['customer_name']); ?></td>
                                <td><?= htmlspecialchars($bill['customer_phone']); ?></td>
                                <td>₹<?= number_format($bill['total_amount'], 2); ?></td>
                                <td>₹<?= number_format($bill['gst_amount'], 2); ?></td>
                                <td class="grand-total">₹<?= number_format($bill['grand_total'], 2); ?></td>
                                <td><?= date('d M Y', strtotime($bill['created_at'])); ?></td>
                                <td class="actions">
                                    <div class="d-flex gap-2">
                                        <a href="view_bill.php?id=<?= $bill['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="View Bill">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="view_canceled_bill.php?id=<?= $bill['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           title="Cancel Bill"
                                           onclick="return confirm('Are you sure you want to cancel this bill?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
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