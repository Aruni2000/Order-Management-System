<?php
// Start session at the very beginning
session_start();

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

// Fetch only unpaid invoices - FIXED JOIN CONDITION HERE
$sql = "SELECT i.*, c.name as customer_name 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id 
        WHERE i.status = 'Unpaid' 
        ORDER BY i.invoice_id DESC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Unpaid Invoices - SB Admin</title>
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
                <h1 class="mt-4">Unpaid Invoices</h1>
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="invoice_list.php">All Invoices</a></li>
                    <li class="breadcrumb-item active">Unpaid Invoices</li>
                </ol>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-invoice-dollar me-1"></i>
                        Unpaid Invoices
                        <a href="invoice_list.php" class="btn btn-outline-primary btn-sm float-end ms-2">View All Invoices</a>
                        <a href="invoice_create.php" class="btn btn-primary btn-sm float-end">Create New Invoice</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="unpaidInvoicesTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['invoice_id']) ?></td>
                                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                            <td><?= number_format($row['total_amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($row['issue_date']) ?></td>
                                            <td><?= htmlspecialchars($row['due_date']) ?></td>
                                            <td><?= htmlspecialchars($row['pay_by']) ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">Unpaid</span>
                                            </td>
                                            <td>
                                                <a href="view_invoice.php?id=<?= htmlspecialchars($row['invoice_id']) ?>" class="btn btn-info btn-sm">View</a>
                                                <a href="print_invoice.php?id=<?= htmlspecialchars($row['invoice_id']) ?>" class="btn btn-secondary btn-sm">Print</a>
                                                <a href="mark_as_paid.php?id=<?= htmlspecialchars($row['invoice_id']) ?>" class="btn btn-success btn-sm">Mark Paid</a>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                    ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No unpaid invoices found</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Card -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-pie me-1"></i>
                                Unpaid Invoices Summary
                            </div>
                            <div class="card-body">
                                <?php
                                // Calculate summary statistics
                                $conn->real_query("SELECT 
                                    COUNT(*) as total_invoices, 
                                    SUM(total_amount) as total_unpaid,
                                    AVG(total_amount) as average_invoice,
                                    MIN(issue_date) as earliest_issue,
                                    MAX(issue_date) as latest_issue
                                    FROM invoices 
                                    WHERE status = 'Unpaid'");
                                
                                $summary = $conn->store_result()->fetch_assoc();
                                ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h5>Total Unpaid Invoices:</h5>
                                        <h2><?= $summary['total_invoices'] ?></h2>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h5>Total Amount Due:</h5>
                                        <h2><?= number_format($summary['total_unpaid'], 2) ?></h2>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h5>Average Invoice Amount:</h5>
                                        <p><?= number_format($summary['average_invoice'], 2) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h5>Issue Date Range:</h5>
                                        <p><?= $summary['earliest_issue'] ?> to <?= $summary['latest_issue'] ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; Your Website 2025</div>
                </div>
            </div>
        </footer> -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
    // Initialize the DataTable
    window.addEventListener('DOMContentLoaded', event => {
        const unpaidInvoicesTable = document.getElementById('unpaidInvoicesTable');
        if (unpaidInvoicesTable) {
            new simpleDatatables.DataTable(unpaidInvoicesTable);
        }
    });
</script>
</body>
</html>

<?php
$conn->close();
?>