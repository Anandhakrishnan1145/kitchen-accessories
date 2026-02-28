<?php
// Database connection
$con = new mysqli("127.0.0.1", "root", "", "final");

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Fetch all agencies
$agencyQuery = "SELECT * FROM agencies";
$agencyResult = $con->query($agencyQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agency Products</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .agency {
            margin-bottom: 20px;
        }
        .agency-name {
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
        }
        .agency-name:hover {
            background-color: #0056b3;
        }
        .products {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .product {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .product:last-child {
            border-bottom: none;
        }
    </style>
    <script>
        // JavaScript to toggle product visibility
        function toggleProducts(agencyId) {
            var productsDiv = document.getElementById('products-' + agencyId);
            if (productsDiv.style.display === "none") {
                productsDiv.style.display = "block";
            } else {
                productsDiv.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <h1>Agency Products</h1>
    <?php
    if ($agencyResult->num_rows > 0) {
        while ($agency = $agencyResult->fetch_assoc()) {
            $agencyId = $agency['id'];
            $agencyName = $agency['agency_name'];
            echo "<div class='agency'>";
            echo "<div class='agency-name' onclick='toggleProducts($agencyId)'>$agencyName</div>";
            echo "<div class='products' id='products-$agencyId'>";

            // Fetch products for this agency
            $productQuery = "SELECT * FROM products WHERE agency_id = $agencyId";
            $productResult = $con->query($productQuery);

            if ($productResult->num_rows > 0) {
                while ($product = $productResult->fetch_assoc()) {
                    $productName = $product['product_name'];
                    $productPrice = $product['selling_price'];
                    echo "<div class='product'>";
                    echo "<strong>$productName</strong> - ₹$productPrice";
                    echo "</div>";
                }
            } else {
                echo "<div class='product'>No products found for this agency.</div>";
            }

            echo "</div></div>";
        }
    } else {
        echo "<p>No agencies found.</p>";
    }
    ?>
</body>
</html>

<?php
// Close the database connection
$con->close();
?>