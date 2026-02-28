<?php
session_start();
require_once '../config.php';

// Redirect if not admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch statistics
$total_sales = $conn->query("SELECT SUM(total_amount) AS total FROM esales")->fetch_assoc()['total'];
$total_orders = $conn->query("SELECT COUNT(*) AS total FROM esales")->fetch_assoc()['total'];
$total_products = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

// Fetch weekly sales data for the bar chart (last 8 weeks)
$weekly_sales = $conn->query("
    SELECT 
        YEAR(created_at) AS year, 
        WEEK(created_at) AS week, 
        SUM(total_amount) AS total 
    FROM esales 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
    GROUP BY YEAR(created_at), WEEK(created_at)
    ORDER BY year, week
")->fetch_all(MYSQLI_ASSOC);

// Format week labels
$week_labels = [];
foreach ($weekly_sales as $sale) {
    $week_labels[] = 'Week ' . $sale['week'] . ', ' . $sale['year'];
}

// Fetch sales by product category for the pie chart
$category_sales = $conn->query("
    SELECT p.product_category, SUM(ei.total) AS total_sales 
    FROM esales_items ei 
    JOIN products p ON ei.product_id = p.id 
    GROUP BY p.product_category
")->fetch_all(MYSQLI_ASSOC);

// Fetch recent orders
$recent_orders = $conn->query("
    SELECT e.order_id, e.total_amount, e.created_at, u.username 
    FROM esales e 
    JOIN users u ON e.user_id = u.id 
    ORDER BY e.created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Fetch top-selling products
$top_products = $conn->query("
    SELECT p.product_name, SUM(ei.quantity) AS total_quantity 
    FROM esales_items ei 
    JOIN products p ON ei.product_id = p.id 
    GROUP BY p.product_name 
    ORDER BY total_quantity DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body>
<?php include "navbar.php"?>
    <div class="container-fluid">
        <div class="row mt-4">
            <!-- Statistics Cards -->
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <p class="card-text">₹ <?php echo number_format($total_sales, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <p class="card-text"><?php echo $total_orders; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <p class="card-text"><?php echo $total_products; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Weekly Sales (Last 8 Weeks)</h5>
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sales by Category</h5>
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders and Top Products -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Orders</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td>₹ <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Top Selling Products</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity Sold</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo $product['total_quantity']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($week_labels); ?>,
                datasets: [{
                    label: 'Weekly Sales',
                    data: <?php echo json_encode(array_column($weekly_sales, 'total')); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Pie Chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($category_sales, 'product_category')); ?>,
                datasets: [{
                    label: 'Sales by Category',
                    data: <?php echo json_encode(array_column($category_sales, 'total_sales')); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>