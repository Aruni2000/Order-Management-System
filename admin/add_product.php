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

// Variable to track product addition status
$product_added = false;
$error_message = null;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs to prevent SQL injection
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $lkr_price = floatval($_POST['lkr_price']); // Remove NULL check
    $usd_price = floatval($_POST['usd_price']); // Remove NULL check
    
    // Both prices are now required, so no need for NULL check
    // Insert the new product into the database with both price fields
    $sql = "INSERT INTO products (name, description, lkr_price, usd_price, created_at, status) 
            VALUES (?, ?, ?, ?, NOW(), 'active')";
    
    // Use prepared statement to avoid SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdd", $name, $description, $lkr_price, $usd_price);
    
    if ($stmt->execute()) {
        // Set flag to show success message
        $product_added = true;
        
        // Clear form inputs after successful submission
        $_POST = array();
    } else {
        // Store error message
        $error_message = 'Error: ' . $stmt->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add New Product</title>
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
                    <h1 class="mt-3">Create New Product</h1>

                    <?php
                    // Display error messages if any
                    if (isset($error_message) && $error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' 
                             . htmlspecialchars($error_message) . 
                             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                             </div>';
                    }
                    ?>

                    <div class="col-12">
                        <div class="form-container shadow">
                            <form method="POST" action="add_product.php" id="addProductForm">
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
                                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                        </div>

                                        <!-- Description Field -->
                                        <div class="mb-3">
                                            <label for="description" class="form-label">
                                                <i class="fas fa-info-circle"></i> Product Description
                                            </label>
                                            <textarea class="form-control" id="description" name="description"
                                                placeholder="Enter Product Description" rows="3" required><?php 
                                                echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
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
                                                value="<?php echo isset($_POST['lkr_price']) ? htmlspecialchars($_POST['lkr_price']) : ''; ?>">
                                        </div>

                                        <!-- USD Price Field -->
                                        <div class="mb-3">
                                            <label for="usd_price" class="form-label">
                                                <i class="fas fa-dollar-sign"></i> USD Price
                                            </label>
                                            <input type="number" step="0.01" class="form-control" id="usd_price" 
                                                name="usd_price" placeholder="Enter USD Price" required
                                                value="<?php echo isset($_POST['usd_price']) ? htmlspecialchars($_POST['usd_price']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary save-btn">
                                            <i class="fas fa-plus-circle"></i> Add Product
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
            <h3>Product Added Successfully!</h3>
            <p>The product has been added to product list.</p>
            <button class="btn btn-primary" onclick="closePopup()">Close</button>
            <a href="product_list.php" class="btn btn-secondary">View Products</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(event) {
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

        // Show popup if product was added successfully
        <?php if ($product_added): ?>
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