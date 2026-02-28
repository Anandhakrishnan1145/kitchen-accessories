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

// Calculate total profit from billed products
$profitQuery = "SELECT 
                    bi.product_id,
                    bi.product_name,
                    SUM(bi.quantity) as total_quantity,
                    p.profit_percent,
                    p.product_price,
                    p.selling_price,
                    SUM(bi.quantity * (p.selling_price - p.product_price)) as total_profit
                FROM 
                    bill_items bi
                JOIN 
                    bills b ON bi.bill_id = b.id
                JOIN 
                    products p ON bi.product_id = p.product_id
                WHERE 
                    b.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                GROUP BY 
                    bi.product_id
                ORDER BY 
                    total_profit DESC";

$profitResult = $conn->query($profitQuery);

// Calculate total sales amount
$totalSalesQuery = "SELECT SUM(grand_total) as total_sales 
                    FROM bills 
                    WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
$totalSalesResult = $conn->query($totalSalesQuery);
$totalSales = $totalSalesResult->fetch_assoc()['total_sales'] ?? 0;

// Count number of bills
$billCountQuery = "SELECT COUNT(*) as bill_count 
                   FROM bills 
                   WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
$billCountResult = $conn->query($billCountQuery);
$billCount = $billCountResult->fetch_assoc()['bill_count'] ?? 0;

// Count number of canceled bills
$canceledBillCountQuery = "SELECT COUNT(*) as canceled_count 
                           FROM canceled_bills 
                           WHERE canceled_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
$canceledBillCountResult = $conn->query($canceledBillCountQuery);
$canceledBillCount = $canceledBillCountResult->fetch_assoc()['canceled_count'] ?? 0;

// Calculate total amount lost from cancelations
$canceledAmountQuery = "SELECT SUM(grand_total) as canceled_amount 
                        FROM canceled_bills 
                        WHERE canceled_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
$canceledAmountResult = $conn->query($canceledAmountQuery);
$canceledAmount = $canceledAmountResult->fetch_assoc()['canceled_amount'] ?? 0;

// Calculate total profit across all products
$totalProfitQuery = "SELECT 
                        SUM(bi.quantity * (p.selling_price - p.product_price)) as overall_profit
                     FROM 
                        bill_items bi
                     JOIN 
                        bills b ON bi.bill_id = b.id
                     JOIN 
                        products p ON bi.product_id = p.product_id
                     WHERE 
                        b.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
$totalProfitResult = $conn->query($totalProfitQuery);
$totalProfit = $totalProfitResult->fetch_assoc()['overall_profit'] ?? 0;

// Get top selling products
$topProductsQuery = "SELECT 
                        bi.product_id,
                        bi.product_name,
                        SUM(bi.quantity) as total_quantity
                     FROM 
                        bill_items bi
                     JOIN 
                        bills b ON bi.bill_id = b.id
                     WHERE 
                        b.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                     GROUP BY 
                        bi.product_id
                     ORDER BY 
                        total_quantity DESC
                     LIMIT 5";
$topProductsResult = $conn->query($topProductsQuery);

// Get agencies' profit contribution
$agencyProfitQuery = "SELECT 
                        a.agency_name,
                        SUM(bi.quantity * (p.selling_price - p.product_price)) as agency_profit
                      FROM 
                        bill_items bi
                      JOIN 
                        bills b ON bi.bill_id = b.id
                      JOIN 
                        products p ON bi.product_id = p.product_id
                      JOIN 
                        agencies a ON p.agency_id = a.id
                      WHERE 
                        b.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
                      GROUP BY 
                        a.id
                      ORDER BY 
                        agency_profit DESC";
$agencyProfitResult = $conn->query($agencyProfitQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit Analysis</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
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
        .profit-card {
            background-color: #d4edda;
            border-left: 5px solid #28a745;
        }
        .sales-card {
            background-color: #cce5ff;
            border-left: 5px solid #007bff;
        }
        .orders-card {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
        }
        .cancel-card {
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
                <h2><i class="fas fa-chart-line mr-2"></i>Profit Analysis</h2>
            </div>
            <div class="col-md-4 text-right">
                <a href="admin_dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-home mr-2"></i>Dashboard</a>
                <a href="gst.php" class="btn btn-outline-primary ml-2"><i class="fas fa-file-invoice-dollar mr-2"></i>GST Analysis</a>
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
                <div class="dashboard-card profit-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Profit</h6>
                            <h3 class="mb-0">₹<?php echo number_format($totalProfit, 2); ?></h3>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card sales-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Sales</h6>
                            <h3 class="mb-0">₹<?php echo number_format($totalSales, 2); ?></h3>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card orders-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Orders</h6>
                            <h3 class="mb-0"><?php echo $billCount; ?></h3>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="dashboard-card cancel-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Canceled Orders</h6>
                            <h3 class="mb-0"><?php echo $canceledBillCount; ?></h3>
                            <small class="text-danger">₹<?php echo number_format($canceledAmount, 2); ?></small>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Product-wise Profit Analysis -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Product-wise Profit Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-header">
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Qty Sold</th>
                                        <th>Cost Price</th>
                                        <th>Selling Price</th>
                                        <th>Profit %</th>
                                        <th>Total Profit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($profitResult->num_rows > 0): ?>
                                        <?php while ($row = $profitResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['product_id']; ?></td>
                                                <td><?php echo $row['product_name']; ?></td>
                                                <td><?php echo $row['total_quantity']; ?></td>
                                                <td>₹<?php echo number_format($row['product_price'], 2); ?></td>
                                                <td>₹<?php echo number_format($row['selling_price'], 2); ?></td>
                                                <td><?php echo $row['profit_percent']; ?>%</td>
                                                <td>₹<?php echo number_format($row['total_profit'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No data available for the selected period</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Side Metrics -->
            <div class="col-md-4">
                <!-- Top Selling Products -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy mr-2"></i>Top Selling Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($topProductsResult->num_rows > 0): ?>
                                        <?php while ($row = $topProductsResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['product_name']; ?></td>
                                                <td><?php echo $row['total_quantity']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Agency Profit Contribution -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-building mr-2"></i>Agency Profit Contribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Agency</th>
                                        <th>Profit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($agencyProfitResult->num_rows > 0): ?>
                                        <?php while ($row = $agencyProfitResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['agency_name']; ?></td>
                                                <td>₹<?php echo number_format($row['agency_profit'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profit Margin Distribution -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-percentage mr-2"></i>Products by Profit Margin</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            // Get profit margin ranges
                            $profitRangesQuery = "SELECT 
                                                    CASE 
                                                        WHEN profit_percent < 20 THEN 'Less than 20%'
                                                        WHEN profit_percent BETWEEN 20 AND 29.99 THEN '20-30%'
                                                        WHEN profit_percent BETWEEN 30 AND 39.99 THEN '30-40%'
                                                        WHEN profit_percent BETWEEN 40 AND 49.99 THEN '40-50%'
                                                        ELSE 'More than 50%'
                                                    END AS profit_range,
                                                    COUNT(*) as product_count
                                                  FROM 
                                                    products
                                                  GROUP BY 
                                                    profit_range
                                                  ORDER BY 
                                                    MIN(profit_percent)";
                            $profitRangesResult = $conn->query($profitRangesQuery);
                            
                            if ($profitRangesResult->num_rows > 0):
                                while ($row = $profitRangesResult->fetch_assoc()):
                                    // Determine card color based on profit range
                                    $cardClass = "";
                                    switch ($row['profit_range']) {
                                        case 'Less than 20%':
                                            $cardClass = "bg-light";
                                            break;
                                        case '20-30%':
                                            $cardClass = "bg-info text-white";
                                            break;
                                        case '30-40%':
                                            $cardClass = "bg-primary text-white";
                                            break;
                                        case '40-50%':
                                            $cardClass = "bg-success text-white";
                                            break;
                                        case 'More than 50%':
                                            $cardClass = "bg-danger text-white";
                                            break;
                                    }
                            ?>
                            <div class="col-md-2 mb-3">
                                <div class="card text-center <?php echo $cardClass; ?>">
                                    <div class="card-body">
                                        <h5 class="display-4"><?php echo $row['product_count']; ?></h5>
                                        <p class="mb-0"><?php echo $row['profit_range']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <div class="col-12 text-center">
                                <p>No product data available</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>