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

// Function to generate a unique bill number
function generateBillNumber($conn) {
    $prefix = "BL";
    $query = "SELECT MAX(CAST(SUBSTRING(bill_no, 3) AS UNSIGNED)) as last_num FROM bills";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $last_number = $row['last_num'] ? $row['last_num'] : 100;
    $new_number = $last_number + 1;
    return $prefix . $new_number;
}

// Function to get all products for dropdown
function getAllProducts($conn) {
    $query = "SELECT product_id, product_name, selling_price, product_quantity 
              FROM products 
              WHERE product_quantity > 0
              ORDER BY product_name";
    $result = $conn->query($query);
    
    $products = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

// Process bill creation
if (isset($_POST['create_bill'])) {
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];
    $customer_email = $_POST['customer_email'];
    $total_amount = $_POST['total_amount'];
    $gst_percentage = $_POST['gst_percentage'];
    $gst_amount = $_POST['gst_amount'];
    $grand_total = $_POST['grand_total'];
    
    $bill_no = generateBillNumber($conn);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert bill
        $bill_query = "INSERT INTO bills (bill_no, customer_name, customer_phone, customer_email, 
                      total_amount, gst_percentage, gst_amount, grand_total) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($bill_query);
        $stmt->bind_param("ssssdddd", $bill_no, $customer_name, $customer_phone, $customer_email, 
                        $total_amount, $gst_percentage, $gst_amount, $grand_total);
        $stmt->execute();
        
        $bill_id = $conn->insert_id;
        
        // Insert bill items
        if(isset($_POST['product_id']) && is_array($_POST['product_id'])) {
            $product_ids = $_POST['product_id'];
            $product_names = $_POST['product_name'];
            $quantities = $_POST['quantity'];
            $prices = $_POST['price'];
            $totals = $_POST['item_total'];
            
            for($i = 0; $i < count($product_ids); $i++) {
                // Insert bill item
                $item_query = "INSERT INTO bill_items (bill_id, product_id, product_name, quantity, price, total) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($item_query);
                $stmt->bind_param("issids", $bill_id, $product_ids[$i], $product_names[$i], 
                                $quantities[$i], $prices[$i], $totals[$i]);
                $stmt->execute();
                
                // Update product quantity
                $update_query = "UPDATE products SET product_quantity = product_quantity - ? 
                               WHERE product_id = ?";
                
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("is", $quantities[$i], $product_ids[$i]);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to print bill
        header("Location: print_bill.php?bill_id=" . $bill_id);
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error creating bill: " . $e->getMessage();
    }
}

$all_products = getAllProducts($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Accessories Stores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .header {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .form-section, .products-section, .summary-section {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-title {
            border-bottom: 2px solid #343a40;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .product-search-container {
            margin-bottom: 15px;
        }
        .product-item {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .remove-product {
            color: #dc3545;
            cursor: pointer;
        }
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 9999 !important;
        }
        .product-list {
            max-height: 400px;
            overflow-y: auto;
        }
        #billing-table th, #billing-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include "navbar.php"?>
    <div class="header text-center">
        <h1><i class="fas fa-cash-register me-2"></i>Kitchen Accessories Stores</h1>
    </div>
    
    <div class="container">
        <?php if(isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="billing-form">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-section">
                        <h3 class="section-title">Customer Information</h3>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="customer_name" class="form-label">Customer Name*</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="customer_phone" class="form-label">Phone Number*</label>
                                <input type="text" class="form-control" id="customer_phone" name="customer_phone" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="customer_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="products-section">
                        <h3 class="section-title">Product Selection</h3>
                        <div class="product-search-container">
                            <label for="product_search" class="form-label">Search Products</label>
                            <input type="text" class="form-control" id="product_search" placeholder="Type to search products...">
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="billing-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="product-items">
                                    <!-- Product items will be added here dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="summary-section">
                        <h3 class="section-title">Bill Summary</h3>
                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control" id="total_amount" name="total_amount" readonly value="0.00">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gst_percentage" class="form-label">GST Percentage</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="gst_percentage" name="gst_percentage" value="18">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gst_amount" class="form-label">GST Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control" id="gst_amount" name="gst_amount" readonly value="0.00">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="grand_total" class="form-label">Grand Total</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control" id="grand_total" name="grand_total" readonly value="0.00">
                            </div>
                        </div>
                        
                        <button type="submit" name="create_bill" class="btn btn-primary w-100 btn-lg">
                            <i class="fas fa-save me-2"></i>Create Bill
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Convert PHP array to JavaScript array
            const allProducts = <?php echo json_encode($all_products); ?>;
            
            // Set up autocomplete for product search
            $("#product_search").autocomplete({
                source: function(request, response) {
                    const term = request.term.toLowerCase();
                    const matches = allProducts.filter(function(product) {
                        return product.product_name.toLowerCase().indexOf(term) !== -1 ||
                               product.product_id.toLowerCase().indexOf(term) !== -1;
                    });
                    response(matches.map(function(product) {
                        return {
                            label: product.product_name + " (" + product.product_id + ")",
                            value: product.product_name,
                            product: product
                        };
                    }));
                },
                minLength: 1,
                select: function(event, ui) {
                    addProductToList(ui.item.product);
                    $(this).val("");
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append("<div><strong>" + item.product.product_name + "</strong><br>ID: " + 
                            item.product.product_id + " | Price: ₹" + 
                            parseFloat(item.product.selling_price).toFixed(2) + 
                            " | Available: " + item.product.product_quantity + "</div>")
                    .appendTo(ul);
            };
            
            // Function to add product to the bill
            function addProductToList(product) {
                // Check if product already exists in the list
                const existingProduct = $("#product-" + product.product_id);
                
                if (existingProduct.length > 0) {
                    // Update quantity if product already exists
                    const qtyInput = existingProduct.find(".product-quantity");
                    const currentQty = parseInt(qtyInput.val());
                    const newQty = currentQty + 1;
                    
                    // Check if new quantity exceeds available quantity
                    if (newQty > parseInt(product.product_quantity)) {
                        alert("Cannot add more units. Available quantity: " + product.product_quantity);
                        return;
                    }
                    
                    qtyInput.val(newQty);
                    updateProductTotal(existingProduct);
                } else {
                    // Add new product row
                    const productHtml = `
                        <tr id="product-${product.product_id}" class="product-row">
                            <td>
                                ${product.product_id}
                                <input type="hidden" name="product_id[]" value="${product.product_id}">
                            </td>
                            <td>
                                ${product.product_name}
                                <input type="hidden" name="product_name[]" value="${product.product_name}">
                            </td>
                            <td>
                                ${parseFloat(product.selling_price).toFixed(2)}
                                <input type="hidden" name="price[]" class="product-price" value="${product.selling_price}">
                            </td>
                            <td>
                                <input type="number" name="quantity[]" class="form-control product-quantity" 
                                    value="1" min="1" max="${product.product_quantity}">
                            </td>
                            <td>
                                <span class="product-total">${parseFloat(product.selling_price).toFixed(2)}</span>
                                <input type="hidden" name="item_total[]" class="product-total-input" 
                                    value="${product.selling_price}">
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-product">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    $("#product-items").append(productHtml);
                }
                
                updateBillTotals();
            }
            
            // Handle quantity change
            $(document).on("change", ".product-quantity", function() {
                const row = $(this).closest(".product-row");
                updateProductTotal(row);
                updateBillTotals();
            });
            
            // Handle remove product
            $(document).on("click", ".remove-product", function() {
                $(this).closest(".product-row").remove();
                updateBillTotals();
            });
            
            // Handle GST percentage change
            $("#gst_percentage").on("change", function() {
                updateBillTotals();
            });
            
            // Function to update a single product's total
            function updateProductTotal(row) {
                const price = parseFloat(row.find(".product-price").val());
                const quantity = parseInt(row.find(".product-quantity").val());
                const total = price * quantity;
                
                row.find(".product-total").text(total.toFixed(2));
                row.find(".product-total-input").val(total.toFixed(2));
            }
            
            // Function to update bill totals
            function updateBillTotals() {
                let totalAmount = 0;
                
                // Calculate sum of all products
                $(".product-total-input").each(function() {
                    totalAmount += parseFloat($(this).val());
                });
                
                // Update total amount
                $("#total_amount").val(totalAmount.toFixed(2));
                
                // Calculate GST
                const gstPercentage = parseFloat($("#gst_percentage").val());
                const gstAmount = (totalAmount * gstPercentage) / 100;
                $("#gst_amount").val(gstAmount.toFixed(2));
                
                // Calculate grand total
                const grandTotal = totalAmount + gstAmount;
                $("#grand_total").val(grandTotal.toFixed(2));
            }
            
            // Form validation
            $("#billing-form").on("submit", function(e) {
                if ($("#product-items").children().length === 0) {
                    alert("Please add at least one product to the bill");
                    e.preventDefault();
                    return false;
                }
                
                if (!$("#customer_name").val() || !$("#customer_phone").val()) {
                    alert("Please enter customer name and phone number");
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>