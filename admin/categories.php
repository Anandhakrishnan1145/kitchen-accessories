<?php
session_start();
require_once '../config.php';

// Redirect if not admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch offline sales data (bills and bill_items)
$offline_sales_query = $conn->query("
    SELECT p.product_category, SUM(bi.total) AS total_sales
    FROM bill_items bi
    JOIN products p ON bi.product_id = p.product_id
    GROUP BY p.product_category
");
$offline_sales = $offline_sales_query->fetch_all(MYSQLI_ASSOC);

// Fetch online sales data (esales and esales_items)
$online_sales_query = $conn->query("
    SELECT p.product_category, SUM(ei.total) AS total_sales
    FROM esales_items ei
    JOIN products p ON ei.product_id = p.id
    GROUP BY p.product_category
");
$online_sales = $online_sales_query->fetch_all(MYSQLI_ASSOC);

// Combine offline and online sales data
$category_sales = [];
foreach ($offline_sales as $sale) {
    $category = $sale['product_category'];
    $category_sales[$category]['offline'] = $sale['total_sales'];
    $category_sales[$category]['online'] = 0; // Initialize online sales to 0
}

foreach ($online_sales as $sale) {
    $category = $sale['product_category'];
    if (!isset($category_sales[$category])) {
        $category_sales[$category]['offline'] = 0; // Initialize offline sales to 0
    }
    $category_sales[$category]['online'] = $sale['total_sales'];
}

// Calculate total sales for each category
foreach ($category_sales as $category => $sales) {
    $category_sales[$category]['total'] = $sales['offline'] + $sales['online'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include "navbar.php"?>
    <div class="container mt-4">
        <h2>Category Sales Report</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Offline Sales (₹)</th>
                    <th>Online Sales (₹)</th>
                    <th>Total Sales (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($category_sales as $category => $sales): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category); ?></td>
                    <td><?php echo number_format($sales['offline'], 2); ?></td>
                    <td><?php echo number_format($sales['online'], 2); ?></td>
                    <td><?php echo number_format($sales['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>