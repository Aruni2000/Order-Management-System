<?php
// Start session at the very beginning only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Force redirect to login page
    header("Location: signin.php");
    exit(); // Stop execution immediately
}

// Include the database connection file
include 'db_connection.php';
include 'functions.php'; // Include helper functions

// Check if the ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to products page if no ID is provided
    header("Location: product_list.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch the product data
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Product not found, redirect to products page
    header("Location: product_list.php");
    exit();
}

$product = $result->fetch_assoc();
$original_product = $product; // Store original values for comparison

// Variable to track product update status
$product_updated = false;
$error_message = null;

// Check if form is submitted for update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    
    // Both price fields are now required
    $lkr_price = floatval($_POST['lkr_price']);
    $usd_price = floatval($_POST['usd_price']);
    
    // Use a simple approach that works correctly with values
    $updateSql = "UPDATE products SET 
                 name = ?, 
                 description = ?, 
                 lkr_price = ?, 
                 usd_price = ? 
                 WHERE id = ?";
                 
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ssddi", $name, $description, $lkr_price, $usd_price, $product_id);
    
    if ($stmt->execute()) {
        // Set flag to show success message
        $product_updated = true;
        
        // Refresh product data after update
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        // Log the changes to user_logs table
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_name'] ?? 'Unknown User';
        $action_type = "edit_product";
        
        // Create detailed changes log
        $changes = array();
        
        // Check each field for changes
        if ($original_product['name'] != $name) {
            $changes[] = "Name changed from '" . htmlspecialchars($original_product['name']) . "' to '" . htmlspecialchars($name) . "'";
        }
        
        if ($original_product['description'] != $description) {
            // For longer text, truncate if needed
            $old_desc = strlen($original_product['description']) > 30 ? 
                substr(htmlspecialchars($original_product['description']), 0, 30) . '...' : 
                htmlspecialchars($original_product['description']);
                
            $new_desc = strlen($description) > 30 ? 
                substr(htmlspecialchars($description), 0, 30) . '...' : 
                htmlspecialchars($description);
                
            $changes[] = "Description was updated from '$old_desc' to '$new_desc'";
        }
        
        // Handle price changes
        $original_lkr = $original_product['lkr_price'];
        $original_usd = $original_product['usd_price'];
        
        // Check for price changes
        if ($original_lkr != $lkr_price) {
            $old_price = is_null($original_lkr) ? 'not set' : number_format($original_lkr, 2) . ' LKR';
            $new_price = number_format($lkr_price, 2) . ' LKR';
            $changes[] = "LKR Price changed from $old_price to $new_price";
        }
        
        if ($original_usd != $usd_price) {
            $old_price = is_null($original_usd) ? 'not set' : number_format($original_usd, 2) . ' USD';
            $new_price = number_format($usd_price, 2) . ' USD';
            $changes[] = "USD Price changed from $old_price to $new_price";
        }
        
        // If no changes were detected
        if (empty($changes)) {
            $changes[] = "No changes were made to the product";
        }
        
        // Format the changes list with bullet points
        $changes_text = "Product ID #$product_id (" . htmlspecialchars($name) . ") was updated by user $user_name($user_id). Changes:\n* " . implode("\n* ", $changes);
        
        // Insert into user_logs table
        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $inquiry_id = 0; // Not applicable for product editing
        $logStmt->bind_param("isis", $user_id, $action_type, $inquiry_id, $changes_text);
        $logStmt->execute();
        $logStmt->close();
        
    } else {
        // Store error message 
        $error_message = 'Error: ' . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Product</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .form-container {
            padding: 25px;
            background-color: #fff;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .section-header {
            border-left: 4px solid #1565C0;
            padding-left: 10px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 500;
        }

        .save-btn {
            background-color: #1565C0;
            float: right;
            padding: 8px 25px;
        }

        /* Popup modal styles */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-3">Edit Product</h1>

                    <?php
                    // Display error messages if any
                    if (isset($error_message) && $error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' 
                             . htmlspecialchars($error_message) . 
                             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                             </div>';
                    }
                    ?>

                     <!-- Breadcrumb Navigation -->
                     <nav aria-label="breadcrumb" >
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="product_list.php">Product List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Product</li>
                        </ol>
                    </nav>

                   
                        <div class="col-12">
                            <div class="form-container shadow">
                                <form method="POST" action="edit_product.php?id=<?= $product_id ?>" id="editProductForm">
                                    <div class="row">
                                        <!-- Product Details Section -->
                                        <div class="col-md-6">
                                            <div class="section-header">Product Details</div>

                                            <!-- Name Field -->
                                            <div class="mb-3">
                                                <label for="name" class="form-label">
                                                    <i class="fas fa-tag"></i> Product Name
                                                </label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                    placeholder="Enter Product Name" required
                                                    value="<?= htmlspecialchars($product['name']) ?>">
                                            </div>

                                            <!-- Description Field -->
                                            <div class="mb-3">
                                                <label for="description" class="form-label">
                                                    <i class="fas fa-info-circle"></i> Product Description
                                                </label>
                                                <textarea class="form-control" id="description" name="description"
                                                    placeholder="Enter Product Description" rows="3" required><?= 
                                                    htmlspecialchars($product['description']) 
                                                    ?></textarea>
                                            </div>
                                        </div>

                                        <!-- Pricing Details Section -->
                                        <div class="col-md-6">
                                            <div class="section-header">Pricing Details</div>
                                            
                                            <!-- LKR Price Field -->
                                            <div class="mb-3">
                                                <label for="lkr_price" class="form-label">
                                                    <i class="fas fa-money-bill-wave"></i> LKR Price
                                                </label>
                                                <input type="number" step="0.01" class="form-control" id="lkr_price" 
                                                    name="lkr_price" placeholder="Enter LKR Price" required
                                                    value="<?= ($product['lkr_price'] !== NULL) ? htmlspecialchars($product['lkr_price']) : '' ?>">
                                            </div>

                                            <!-- USD Price Field -->
                                            <div class="mb-3">
                                                <label for="usd_price" class="form-label">
                                                    <i class="fas fa-dollar-sign"></i> USD Price
                                                </label>
                                                <input type="number" step="0.01" class="form-control" id="usd_price" 
                                                    name="usd_price" placeholder="Enter USD Price" required
                                                    value="<?= ($product['usd_price'] !== NULL) ? htmlspecialchars($product['usd_price']) : '' ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary save-btn">
                                                <i class="fas fa-save"></i> Update Product
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Success Popup -->
    <div id="successPopup" class="popup-overlay">
        <div class="popup-content">
            <h3>Product Updated Successfully!</h3>
            <p>The product details have been updated.</p>
            <button class="btn btn-primary" onclick="closePopup()">Close</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        // Form validation
        document.getElementById('editProductForm').addEventListener('submit', function(event) {
            const lkrPrice = document.getElementById('lkr_price').value.trim();
            const usdPrice = document.getElementById('usd_price').value.trim();
            
            // Check if both price fields are filled
            if (lkrPrice === '' || usdPrice === '') {
                event.preventDefault();
                alert('Both LKR and USD prices are required.');
                return false;
            }
            
            // Validate LKR price
            if (parseFloat(lkrPrice) < 0 || parseFloat(lkrPrice) > 1000000) {
                event.preventDefault();
                alert('Please enter a valid LKR price between 0 and 1,000,000');
                return false;
            }
            
            // Validate USD price
            if (parseFloat(usdPrice) < 0 || parseFloat(usdPrice) > 10000) {
                event.preventDefault();
                alert('Please enter a valid USD price between 0 and 10,000');
                return false;
            }
        });

        // Product Name Validation
        document.getElementById('name').addEventListener('blur', function () {
            const nameRegex = /^[a-zA-Z0-9\s\-_.,()]{3,100}$/;
            if (!nameRegex.test(this.value)) {
                this.setCustomValidity('Product name must be 3-100 characters long and can contain letters, numbers, spaces, and some special characters');
            } else {
                this.setCustomValidity('');
            }
        });

        // Show popup if product was updated successfully
        <?php if ($product_updated): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('successPopup').style.display = 'flex';
        });
        <?php endif; ?>

        // Function to close popup
        function closePopup() {
            document.getElementById('successPopup').style.display = 'none';
        }
    </script>

    <?php
    // Close the connection at the end of the script
    $conn->close();
    ?>
</body>
</html>