<?php
// File name: product_list.php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
// This check must happen before ANY output is sent to the browser
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

// Process status toggle if submitted
if(isset($_POST['toggle_status'])) {
    $product_id = $_POST['product_id'];
    $new_status = $_POST['new_status'];
    $user_id = $_SESSION['user_id']; // Get the current user's ID from session
    $product_name = ''; // Initialize product name variable
    
    // First, get the product name for the log
    $productQuery = "SELECT name FROM products WHERE id = ?";
    $stmt = $conn->prepare($productQuery);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $productResult = $stmt->get_result();
    
    if ($productResult->num_rows > 0) {
        $productData = $productResult->fetch_assoc();
        $product_name = $productData['name'];
    }
    $stmt->close();
    
    // Use prepared statement to prevent SQL injection
    $updateQuery = "UPDATE products SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $new_status, $product_id);
    
    if($stmt->execute()) {
        // Set success message based on the new status
        $action = $new_status == 'active' ? 'activated' : 'deactivated';
        $_SESSION['success_message'] = "Product successfully $action!";
        
        // Log the action to user_logs table
        $action_type = $new_status == 'active' ? 'activate_product' : 'deactivate_product';
        $details = "Product ID #$product_id ($product_name) was $action by user ID #$user_id";
        
        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $inquiry_id = 0; // Not applicable for product actions
        $logStmt->bind_param("isis", $user_id, $action_type, $inquiry_id, $details);
        $logStmt->execute();
        $logStmt->close();
    } else {
        $_SESSION['error_message'] = "Error updating product status: " . $conn->error;
    }
    
    $stmt->close();
}

// Fetch products
$sql = "SELECT * FROM products ORDER BY id ASC";
$result = $conn->query($sql);

// Count total products
$countQuery = "SELECT COUNT(*) as total FROM products";
$countResult = $conn->query($countQuery);
$totalproducts = $countResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>All Products </title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
</head>

<body class="sb-nav-fixed">
<?php include 'navbar.php'; ?>

<div id="layoutSidenav">
    <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <!-- Success Message Display with SweetAlert -->
                    <?php
                    // Check if there's a success message in the session
                    if (isset($_SESSION['success_message'])) {
                        echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    title: "Success!",
                                    text: "' . addslashes($_SESSION['success_message']) . '",
                                    icon: "success",
                                    confirmButtonColor: "#4CAF50"
                                });
                            });
                        </script>';
                        
                        // Clear the success message from the session
                        unset($_SESSION['success_message']);
                    }
                    
                    // Check if there's an error message in the session
                    if (isset($_SESSION['error_message'])) {
                        echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    title: "Error!",
                                    text: "' . addslashes($_SESSION['error_message']) . '",
                                    icon: "error",
                                    confirmButtonColor: "#dc3545"
                                });
                            });
                        </script>';
                        
                        // Clear the error message from the session
                        unset($_SESSION['error_message']);
                    }
                    ?>

                    <h1 class="mt-3">Products</h1>
                    <ol class="breadcrumb mb-4">
                        <!-- Total Product Count -->
                        <div class="alert alert-info">
                            <strong>Total Products:</strong> <?= $totalproducts ?>
                        </div>
                    </ol>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="productsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Price (LKR)</th>
                                            <th>Created At</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id']) ?></td>
                                                <td><?= htmlspecialchars($row['name']) ?></td>
                                                <td>
                                                    <?php 
                                                        $description = htmlspecialchars($row['description']);
                                                        echo strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description; 
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (isset($row['lkr_price'])) {
                                                        echo number_format($row['lkr_price'], 2) . ' LKR';
                                                    } elseif (isset($row['price']) && isset($row['currency']) && $row['currency'] == 'LKR') {
                                                        echo number_format($row['price'], 2) . ' LKR';
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                                                <td>
                                                    <?php
                                                    $status = isset($row['status']) ? $row['status'] : 'active';
                                                    $statusClass = $status == 'active' ? 'status-active' : 'status-inactive';
                                                    ?>
                                                    <span class="<?= $statusClass ?>"><?= ucfirst($status) ?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-info btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    
                                                    <?php
                                                    // Status toggle button
                                                    $status = isset($row['status']) ? $row['status'] : 'active';
                                                    $newStatus = $status == 'active' ? 'inactive' : 'active';
                                                    $btnClass = $status == 'active' ? 'btn-danger' : 'btn-success';
                                                    $btnText = $status == 'active' ? 'Deactivate' : 'Activate';
                                                    $btnIcon = $status == 'active' ? 'fa-ban' : 'fa-check';
                                                    ?>
                                                    <!-- SweetAlert Status Toggle Button -->
                                                    <button type="button" class="btn <?= $btnClass ?> btn-sm mb-1" 
                                                            onclick="confirmStatusChange(<?= $row['id'] ?>, '<?= $newStatus ?>', '<?= htmlspecialchars($row['name']) ?>')">
                                                        <i class="fas <?= $btnIcon ?>"></i> <?= $btnText ?>
                                                    </button>
                                                    
                                                    <!-- Hidden form for status toggle submission -->
                                                    <form id="toggleForm<?= $row['id'] ?>" action="" method="POST" style="display:none;">
                                                        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $newStatus ?>">
                                                        <input type="hidden" name="toggle_status" value="1">
                                                    </form>
                                                </td>
                                            </tr>

                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="viewModalLabel">Product Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>ID:</strong> <?= htmlspecialchars($row['id']) ?></p>
                                                            <p><strong>Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
                                                            <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
                                                            <p><strong>Price (LKR):</strong> 
                                                                <?php
                                                                if (isset($row['lkr_price'])) {
                                                                    echo number_format($row['lkr_price'], 2) . ' LKR';
                                                                } elseif (isset($row['price']) && isset($row['currency']) && $row['currency'] == 'LKR') {
                                                                    echo number_format($row['price'], 2) . ' LKR';
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                                ?>
                                                            </p>
                                                            <p><strong>Created At:</strong> <?= htmlspecialchars($row['created_at']) ?></p>
                                                            <p><strong>Status:</strong> <span class="<?= $statusClass ?>"><?= ucfirst($status) ?></span></p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <a href="edit_product.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-primary">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        // Initialize the datatable
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('productsTable');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });
        
        // SweetAlert confirmation function
        function confirmStatusChange(productId, newStatus, productName) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            const actionCapitalized = action.charAt(0).toUpperCase() + action.slice(1);
            
            Swal.fire({
                title: `${actionCapitalized} Product?`,
                text: `Are you sure you want to ${action} "${productName}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'active' ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action} it!`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, submit the form
                    document.getElementById(`toggleForm${productId}`).submit();
                    
                    // Show processing message
                    Swal.fire({
                        title: 'Processing...',
                        text: `${actionCapitalized} the product.`,
                        icon: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>