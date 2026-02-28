<?php
session_start();
require_once '../config.php'; // Include your database connection

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Set default date range (current month)
$startDate = date('Y-m-01');
$endDate = date('Y-m-t');

// If date range is provided by form
if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
}

// Get GST summary data
$gstSummaryQuery = "SELECT 
                      SUM(total_amount) as net_amount,
                      SUM(gst_amount) as total_gst,
                      SUM(grand_total) as total_with_gst,
                      AVG(gst_percentage) as avg_gst_rate
                    FROM 
                      bills
                    WHERE 
                      created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
$gstSummaryResult = $conn->query($gstSummaryQuery);
$gstSummary = $gstSummaryResult->fetch_assoc();

// Get daily GST collection data
$dailyGstQuery = "SELECT 
                    DATE(created_at) as bill_date,
                    SUM(total_amount) as daily_amount,
                    SUM(gst_amount) as daily_gst,
                    COUNT(*) as bill_count
                  FROM 
                    bills
                  WHERE 
                    created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                  GROUP BY 
                    DATE(created_at)
                  ORDER BY 
                    bill_date";
$dailyGstResult = $conn->query($dailyGstQuery);

// Get product category GST distribution
$categoryGstQuery = "SELECT 
                       p.product_category,
                       SUM(bi.quantity * bi.price) as category_amount,
                       SUM(bi.quantity * bi.price * b.gst_percentage / 100) as category_gst
                     FROM 
                       bill_items bi
                     JOIN 
                       bills b ON bi.bill_id = b.id
                     JOIN 
                       products p ON bi.product_id = p.product_id
                     WHERE 
                       b.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                     GROUP BY 
                       p.product_category
                     ORDER BY 
                       category_gst DESC";
$categoryGstResult = $conn->query($categoryGstQuery);

// Get top bills by GST amount
$topGstBillsQuery = "SELECT 
                       bill_no,
                       customer_name,
                       total_amount,
                       gst_percentage,
                       gst_amount,
                       grand_total,
                       DATE(created_at) as bill_date
                     FROM 
                       bills
                     WHERE 
                       created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                     ORDER BY 
                       gst_amount DESC
                     LIMIT 10";
$topGstBillsResult = $conn->query($topGstBillsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Analysis</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            height: 100%;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .gst-card {
            background-color: #e2f0fb;
            border-left: 5px solid #17a2b8;
        }
        .amount-card {
            background-color: #d4edda;
            border-left: 5px solid #28a745;
        }
        .rate-card {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
        }
        .total-card {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
        }
        .card-icon {
            font-size: 2.5rem;
            color: rgba(0, 0, 0, 0.15);
        }
        .table-header {
            background-color: #343a40;
            color: white;
        }
    </style>
</head>
<body>
<?php include "navbar.php"?>
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-file-invoice-dollar mr-2"></i>GST Analysis</h2>
            </div>
            <div class="col-md-4 text-right">
                <a href="admin_dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-home mr-2"></i>Dashboard</a>
                <a href="profit.php" class="btn btn-outline-success ml-2"><i class="fas fa-chart-line mr-2"></i>Profit Analysis</a>
            </div>
        </div>

        <!-- Date Range Selector -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="POST" class="form-inline justify-content-center">
                    <div class="form-group mr-3">
                        <label for="start_date" class="mr-2">From:</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="form-group mr-3">
                        <label for="end_date" class="mr-2">To:</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-2"></i>Filter</button>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="dashboard-card gst-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total GST Collected</h6>
                            <h3 class="mb-0">₹<?php echo number_format($gstSummary['total_gst'] ?? 0, 2); ?></h3>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card amount-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Net Amount (Before GST)</h6>
                            <h3 class="mb-0">₹<?php echo number_format($gstSummary['net_amount'] ?? 0, 2); ?></h3>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card rate-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Average GST Rate</h6>
                            <h3 class="mb-0"><?php echo number_format($gstSummary['avg_gst_rate'] ?? 0, 2); ?>%</h3>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card total-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Grand Total (With GST)</h6>
                            <h3 class="mb-0">₹<?php echo number_format($gstSummary['total_with_gst'] ?? 0, 2); ?></h3>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Daily GST Collection -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Daily GST Collection</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-header">
                                <tr>
                                        <th>Date</th>
                                        <th>Bill Count</th>
                                        <th>Amount (Before GST)</th>
                                        <th>GST Collected</th>
                                        <th>% of Total GST</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalGst = $gstSummary['total_gst'] ?? 0;
                                    while ($row = $dailyGstResult->fetch_assoc()) {
                                        $gstPercentage = ($totalGst > 0) ? ($row['daily_gst'] / $totalGst * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($row['bill_date'])); ?></td>
                                        <td><?php echo $row['bill_count']; ?></td>
                                        <td>₹<?php echo number_format($row['daily_amount'], 2); ?></td>
                                        <td>₹<?php echo number_format($row['daily_gst'], 2); ?></td>
                                        <td><?php echo number_format($gstPercentage, 2); ?>%</td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Category GST Distribution -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-tags mr-2"></i>GST by Product Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-header">
                                    <tr>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>GST</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $categoryGstResult->fetch_assoc()) { ?>
                                    <tr>
                                        <td><?php echo $row['product_category']; ?></td>
                                        <td>₹<?php echo number_format($row['category_amount'], 2); ?></td>
                                        <td>₹<?php echo number_format($row['category_gst'], 2); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Bills by GST Amount -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-trophy mr-2"></i>Top Bills by GST Amount</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-header">
                            <tr>
                                <th>Bill No.</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Amount (Before GST)</th>
                                <th>GST Rate</th>
                                <th>GST Amount</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $topGstBillsResult->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['bill_no']; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['bill_date'])); ?></td>
                                <td><?php echo $row['customer_name']; ?></td>
                                <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td><?php echo number_format($row['gst_percentage'], 2); ?>%</td>
                                <td>₹<?php echo number_format($row['gst_amount'], 2); ?></td>
                                <td>₹<?php echo number_format($row['grand_total'], 2); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>