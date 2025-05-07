
<?php
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

// Fetch products
$sql = "SELECT * FROM products ORDER BY id ASC";
$result = $conn->query($sql);


// Count total inquiries
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
    <title></title>
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
            <div class="alert-container"></div>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Products</h1>
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
                                        <th>Price</th>
                                        <th>Created At</th>
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
                                            <td><?= number_format($row['price'], 2) ?></td>
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                            <td>
                                                <a href="edit_product.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-primary btn-sm">Edit</a>
                                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">View</button>
                                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">Delete</button>
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
                                                        <p><strong>Price:</strong> <?= number_format($row['price'], 2) ?></p>
                                                        <p><strong>Created At:</strong> <?= htmlspecialchars($row['created_at']) ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel">Delete Product</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete <strong><?= htmlspecialchars($row['name']) ?></strong>? This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="delete_product.php" method="POST">
                                                            <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                        </form>
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
</body>
</html>

<?php
$conn->close();
?>
