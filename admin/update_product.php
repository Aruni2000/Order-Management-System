<?php
// update_product.php
session_start();

// Include necessary files
include 'db_connection.php';
include 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: signin.php");
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID.";
    header("Location: product_list.php");
    exit();
}

$product_id = intval($_GET['id']);

// Check if form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $name = $conn->real_escape_string(trim($_POST['name']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $status = $conn->real_escape_string($_POST['status']);
    
    // Validate prices (handle empty inputs properly)
    $lkr_price = !empty($_POST['lkr_price']) ? floatval($_POST['lkr_price']) : null;
    $usd_price = !empty($_POST['usd_price']) ? floatval($_POST['usd_price']) : null;
    
    // Validate required fields
    if (empty($name) || empty($description)) {
        $_SESSION['error_message'] = "Name and description are required.";
        header("Location: update_product.php?id=$product_id");
        exit();
    }
    
    // Check if at least one price is provided
    if ($lkr_price === null && $usd_price === null) {
        $_SESSION['error_message'] = "At least one price (LKR or USD) is required.";
        header("Location: update_product.php?id=$product_id");
        exit();
    }
    
    // Prepare SQL statement for update
    // Note: We need to handle NULL values correctly for the database
    $sql = "UPDATE products SET 
            name = ?, 
            description = ?, 
            status = ?, 
            updated_at = NOW() ";
    
    // Add price fields conditionally
    if ($lkr_price !== null) {
        $sql .= ", lkr_price = ? ";
    } else {
        $sql .= ", lkr_price = NULL ";
    }
    
    if ($usd_price !== null) {
        $sql .= ", usd_price = ? ";
    } else {
        $sql .= ", usd_price = NULL ";
    }
    
    $sql .= "WHERE id = ?";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    // Bind parameters dynamically based on which prices are provided
    if ($lkr_price !== null && $usd_price !== null) {
        $stmt->bind_param("ssddsi", $name, $description, $status, $lkr_price, $usd_price, $product_id);
    } elseif ($lkr_price !== null) {
        $stmt->bind_param("ssdsi", $name, $description, $status, $lkr_price, $product_id);
    } elseif ($usd_price !== null) {
        $stmt->bind_param("ssdsi", $name, $description, $status, $usd_price, $product_id);
    } else {
        // This shouldn't happen due to validation above, but just in case
        $stmt->bind_param("sssi", $name, $description, $status, $product_id);
    }
    
    // Execute the statement
    try {
        if ($stmt->execute()) {
            // Successful update
            $_SESSION['success_message'] = "Product updated successfully!";
            header("Location: product_list.php"); // Redirect to product list page
            exit();
        } else {
            // Error in update
            $_SESSION['error_message'] = "Error updating product: " . $stmt->error;
            header("Location: update_product.php?id=$product_id");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        header("Location: update_product.php?id=$product_id");
        exit();
    }
    
    // Close statement
    $stmt->close();
} else {
    // Fetch product details for pre-filling the form
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Product not found.";
        header("Location: product_list.php");
        exit();
    }
    
    $product = $result->fetch_assoc();
    $stmt->close();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Update Product</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Update Product</h1>

                    <?php
                    // Display success or error messages
                    if (isset($_SESSION['success_message'])) {
                        echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                        unset($_SESSION['success_message']);
                    }
                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                        unset($_SESSION['error_message']);
                    }
                    ?>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="form-container shadow">
                                <form method="POST" action="update_product.php?id=<?php echo $product_id; ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Product Name</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                    value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="description" class="form-label">Product Description</label>
                                                <textarea class="form-control" id="description" name="description" 
                                                    rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="lkr_price" class="form-label">LKR Price</label>
                                                <input type="number" step="0.01" class="form-control" id="lkr_price" 
                                                    name="lkr_price" value="<?php echo $product['lkr_price'] !== null ? $product['lkr_price'] : ''; ?>">
                                                <small class="form-text text-muted">Leave empty to remove LKR price (ensure at least one price is provided)</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="usd_price" class="form-label">USD Price</label>
                                                <input type="number" step="0.01" class="form-control" id="usd_price" 
                                                    name="usd_price" value="<?php echo $product['usd_price'] !== null ? $product['usd_price'] : ''; ?>">
                                                <small class="form-text text-muted">Leave empty to remove USD price (ensure at least one price is provided)</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-control" id="status" name="status">
                                                    <option value="active" <?php echo ($product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo ($product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary float-end">Update Product</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>
</html>