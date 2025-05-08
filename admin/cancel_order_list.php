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

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query with JOIN to customers table and filter by pending status
$countSql = "SELECT COUNT(*) as total FROM order_header i 
             LEFT JOIN customers c ON i.customer_id = c.customer_id
             LEFT JOIN payments p ON i.order_id = p.order_id
             LEFT JOIN users u ON p.pay_by = u.id
             WHERE i.status = 'cancel'";

$sql = "SELECT i.*, c.name as customer_name, 
               p.payment_id, p.amount_paid, p.payment_method, p.payment_date, p.pay_by,
               u.name as paid_by_name
        FROM order_header i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        LEFT JOIN payments p ON i.order_id = p.order_id
        LEFT JOIN users u ON p.pay_by = u.id
        WHERE i.status = 'cancel'";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " AND (
                        i.order_id LIKE '%$searchTerm%' OR 
                        c.name LIKE '%$searchTerm%' OR 
                        i.issue_date LIKE '%$searchTerm%' OR 
                        i.due_date LIKE '%$searchTerm%' OR 
                        i.total_amount LIKE '%$searchTerm%' OR
                        i.pay_status LIKE '%$searchTerm%' OR
                        p.payment_method LIKE '%$searchTerm%' OR
                        u.name LIKE '%$searchTerm%')";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY i.order_id DESC LIMIT $limit OFFSET $offset";

// Execute the queries
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Cancel Orders</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <br>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Cancel Orders</h4>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form method="get" class="d-flex" id="searchForm">
                                        <input type="text" name="search" class="form-control me-2"
                                            placeholder="Order ID/Customer Name/Status/Pay Status"
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="cancel_order_list.php" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        <?php endif; ?>
                                        <!-- Preserve limit parameter when searching -->
                                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                        <input type="hidden" name="page" value="1">
                                        <!-- Reset to page 1 when searching -->
                                    </form>
                                </div>
                                <div class="col-md-6 text-end">
                                    <form method="get" id="limitForm">
                                        <!-- Preserve search parameter when changing limit -->
                                        <?php if (!empty($search)): ?>
                                            <input type="hidden" name="search"
                                                value="<?php echo htmlspecialchars($search); ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="page" value="1">
                                        <!-- Reset to page 1 when changing limit -->
                                        <div class="d-inline-block">
                                            <label>Show</label>
                                            <select name="limit" class="form-select d-inline-block w-auto ms-1"
                                                onchange="document.getElementById('limitForm').submit()">
                                                <option value="10" <?php if ($limit == 10)
                                                    echo 'selected'; ?>>10</option>
                                                <option value="25" <?php if ($limit == 25)
                                                    echo 'selected'; ?>>25</option>
                                                <option value="50" <?php if ($limit == 50)
                                                    echo 'selected'; ?>>50</option>
                                                <option value="100" <?php if ($limit == 100)
                                                    echo 'selected'; ?>>100
                                                </option>
                                            </select>
                                            <label>entries</label>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <h5 class="mb-3">Manage Complete Orders</h5>
                            <?php if (!empty($search)): ?>
                                <div class="alert alert-info">
                                    Showing search results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                                    (<?php echo $totalRows; ?> results found)
                                </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="order_table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer Name</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Total Amount</th>
                                            <th>Status</th>
                                            <th>Pay Status</th>
                                            <th>Processed By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo isset($row['order_id']) ? htmlspecialchars($row['order_id']) : ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $customerName = isset($row['customer_name']) ? htmlspecialchars($row['customer_name']) : 'N/A';
                                                        $customerId = isset($row['customer_id']) ? htmlspecialchars($row['customer_id']) : '';
                                                        echo $customerName . ($customerId ? " ($customerId)" : "");
                                                        ?>
                                                    </td>
                                                    <td><?php echo isset($row['issue_date']) ? htmlspecialchars(date('d/m/Y', strtotime($row['issue_date']))) : ''; ?>
                                                    </td>
                                                    <td><?php echo isset($row['due_date']) ? htmlspecialchars(date('d/m/Y', strtotime($row['due_date']))) : ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $amount = isset($row['total_amount']) ? htmlspecialchars(number_format((float) $row['total_amount'], 2)) : '0.00';
                                                        $currency = isset($row['currency']) ? $row['currency'] : 'lkr';
                                                        $currencySymbol = ($currency == 'usd') ? '$' : 'Rs';
                                                        echo $amount . ' (' . $currencySymbol . ')';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-danger">Cancel</span>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        $payStatus = isset($row['pay_status']) ? $row['pay_status'] : 'unpaid';
                                                        if ($payStatus == 'paid'): ?>
                                                            <span class="badge bg-success">Paid</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Unpaid</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if (isset($row['pay_by']) && isset($row['paid_by_name'])) {
                                                            echo htmlspecialchars($row['paid_by_name']) . ' (' . htmlspecialchars($row['pay_by']) . ')';
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="#" class="btn btn-sm btn-info text-white view-order"
                                                                title="View"
                                                                data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
                                                                data-paystatus="<?php echo $payStatus; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <!-- <a href="download_order.php?id=<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
                                                                class="btn btn-sm btn-success text-white" title="Download"
                                                                target="_blank">
                                                                <i class="fas fa-download"></i>
                                                            </a> -->
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No complete order found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        Showing <?php echo ($offset + 1); ?> to
                                        <?php echo min($offset + $limit, $totalRows); ?> of <?php echo $totalRows; ?>
                                        entries
                                    <?php else: ?>
                                        Showing 0 to 0 of 0 entries
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-end">
                                            <!-- Previous page link -->
                                            <li class="page-item <?php if ($page <= 1)
                                                echo 'disabled'; ?>">
                                                <a class="page-link"
                                                    href="?page=<?php echo ($page - 1); ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                                            </li>

                                            <?php
                                            // Display a limited number of page links
                                            $maxPagesToShow = 5;
                                            $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                                            $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

                                            // Ensure startPage is at least 1
                                            $startPage = max(1, $startPage);

                                            // Show "..." before the first page link if needed
                                            if ($startPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="?page=1&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">1</a>
                                                </li>
                                                <?php if ($startPage > 2): ?>
                                                    <li class="page-item disabled">
                                                        <span class="page-link">...</span>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                <li class="page-item <?php if ($page == $i)
                                                    echo 'active'; ?>">
                                                    <a class="page-link"
                                                        href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php
                                            // Show "..." after the last page link if needed
                                            if ($endPage < $totalPages): ?>
                                                <?php if ($endPage < $totalPages - 1): ?>
                                                    <li class="page-item disabled">
                                                        <span class="page-link">...</span>
                                                    </li>
                                                <?php endif; ?>
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $totalPages; ?></a>
                                                </li>
                                            <?php endif; ?>

                                            <!-- Next page link -->
                                            <li class="page-item <?php if ($page >= $totalPages)
                                                echo 'disabled'; ?>">
                                                <a class="page-link"
                                                    href="?page=<?php echo ($page + 1); ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal for Viewing Order -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOrderModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Order details will be loaded here -->
                    Loading...
                </div>
                <!-- Payment information section -->
                <div class="modal-body border-top" id="paymentInfoSection" style="display:none;">
                    <h5><i class="fas fa-money-bill-wave me-2"></i>Payment Information</h5>
                    <div id="paymentDetails" class="mt-3">
                        <!-- Payment details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Marking Order as Paid -->
    <div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="markPaidModalLabel"> Payment Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="markPaidForm" enctype="multipart/form-data">
                        <input type="hidden" name="order_id" id="order_id">

                        <div class="mb-3">
                            <label for="payment_slip" class="form-label">Please Select Your Payment Slip</label>
                            <input type="file" class="form-control" id="payment_slip" name="payment_slip"
                                accept=".jpg,.png,.pdf" required>
                            <small class="form-text text-muted">Supported File formats: .JPG / .PNG / .PDF</small>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success">Paid</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Handle "View" button click
            $('.view-order').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var orderId = $(this).data('id'); // Get the order ID
                var payStatus = $(this).data('paystatus'); // Get the payment status

                // Show loading message in the modal
                $('#orderDetails').html('Loading...');

                // Hide payment info section initially
                $('#paymentInfoSection').hide();
                $('#paymentDetails').html('');

                // Fetch order details via AJAX
                $.ajax({
                    url: 'download_order.php',
                    type: 'GET',
                    data: {
                        id: orderId,
                        format: 'html' // Request HTML format instead of PDF download
                    },
                    success: function (response) {
                        // Populate the modal with the fetched data
                        $('#orderDetails').html(response);

                        // IMPORTANT: Remove the Print Order and Open in New Tab buttons
                        $('#orderDetails').find('button:contains("Print Order")').remove();
                        $('#orderDetails').find('button:contains("Open in New Tab")').remove();
                        $('#orderDetails').find('button:contains("Download")').remove();

                        // Add print button after the order details
                        var printButton = '<div class="text-end mt-3">' +
                            '<button type="button" class="btn btn-primary print-order" ' +
                            'data-id="' + orderId + '">' +
                            '<i class="fas fa-print me-1"></i>Print Order</button>' +
                            '</div>';
                        $('#orderDetails').append(printButton);

                        // Fetch payment details for this order
                        fetchPaymentDetails(orderId, payStatus);

                        // Show the modal
                        $('#viewOrderModal').modal('show');
                    },
                    error: function () {
                        $('#orderDetails').html('Failed to load order details.');
                    }
                });
            });

            // Handle Print Order button click
            $(document).on('click', '.print-order', function () {
                var orderId = $(this).data('id');

                // Open the download_order.php in a new window for printing
                var printWindow = window.open('download_order.php?id=' + orderId + '&format=html&print=true', '_blank');

                // Trigger print when the new window is loaded
                printWindow.onload = function () {
                    printWindow.print();
                };
            });

            // Function to fetch payment details
            function fetchPaymentDetails(orderId, payStatus) {
                if (payStatus === 'paid') {
                    $.ajax({
                        url: 'get_payment_details.php',
                        type: 'GET',
                        data: { order_id: orderId },
                        success: function (data) {
                            if (data.success) {
                                // Create payment details HTML
                                var html = '<div class="card">' +
                                    '<div class="card-body">' +
                                    '<div class="row">' +
                                    '<div class="col-md-6">' +
                                    '<p><strong>Payment Method:</strong> ' + data.payment_method + '</p>' +
                                    '<p><strong>Amount Paid:</strong> ' + data.amount_paid + '</p>' +
                                    '</div>' +
                                    '<div class="col-md-6">' +
                                    '<p><strong>Payment Date:</strong> ' + data.payment_date + '</p>' +
                                    '<p><strong>Processed By:</strong> ' + data.processed_by + '</p>' +
                                    '</div>' +
                                    '</div>';

                                // Add payment slip if available
                                if (data.slip) {
                                    html += '<div class="text-center mt-3">' +
                                        '<p><strong>Payment Slip:</strong></p>' +
                                        '<a href="uploads/payments/' + data.slip + '" target="_blank">' +
                                        '<img src="uploads/payments/' + data.slip + '" class="img-fluid" style="max-height: 200px;">' +
                                        '</a>' +
                                        '</div>';
                                }

                                html += '</div></div>';

                                // Show the payment section
                                $('#paymentDetails').html(html);
                                $('#paymentInfoSection').show();
                            } else {
                                // If there's an error, show an error message
                                $('#paymentDetails').html('<div class="alert alert-warning">No payment details found for this order.</div>');
                                $('#paymentInfoSection').show();
                            }
                        },
                        error: function () {
                            // If AJAX fails, show an error
                            $('#paymentDetails').html('<div class="alert alert-danger">Failed to load payment details.</div>');
                            $('#paymentInfoSection').show();
                        }
                    });
                } else {
                    // If order is not paid, show appropriate message
                    $('#paymentDetails').html('<div class="alert alert-info">This order has not been paid yet.</div>');
                    $('#paymentInfoSection').show();
                }
            }

            // Handle "Paid" button click
            $('.mark-paid').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var orderId = $(this).data('id'); // Get the order ID

                // Directly set the order ID in the form without fetching other details
                $('#order_id').val(orderId);

                // Show the modal
                $('#markPaidModal').modal('show');
            });

            // Handle form submission
            $('#markPaidForm').submit(function (e) {
                e.preventDefault(); // Prevent default form submission

                var formData = new FormData(this);

                $.ajax({
                    url: 'mark_paid.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        alert('Order marked as paid successfully.');
                        $('#markPaidModal').modal('hide');
                        location.reload(); // Reload the page to reflect changes
                    },
                    error: function () {
                        alert('Failed to mark order as paid.');
                    }
                });
            });
        });
          // Sidebar Toggle Script
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

    // Check for stored state on page load
    const storedSidebarState = localStorage.getItem('sb|sidebar-toggle');
    if (storedSidebarState === 'true') {
        document.body.classList.add('sb-sidenav-toggled');
    }
});
    </script>
</body>

</html>