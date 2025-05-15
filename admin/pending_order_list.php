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

// Build basic SQL query with JOIN to customers table and filter by pending status and interface=individual
$countSql = "SELECT COUNT(*) as total FROM order_header i 
             LEFT JOIN customers c ON i.customer_id = c.customer_id
             WHERE (i.status = 'pending' OR i.status IS NULL OR i.status = '') 
             AND i.interface = 'individual'";

$sql = "SELECT i.*, c.name as customer_name 
        FROM order_header i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        WHERE (i.status = 'pending' OR i.status IS NULL OR i.status = '') 
        AND i.interface = 'individual'";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " AND (
                        i.order_id LIKE '%$searchTerm%' OR 
                        c.name LIKE '%$searchTerm%' OR 
                        i.issue_date LIKE '%$searchTerm%' OR 
                        i.due_date LIKE '%$searchTerm%' OR 
                        i.total_amount LIKE '%$searchTerm%')";
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

// Process order cancellation
if (isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id']; // Current logged-in user ID
    $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : '';

    // Get order details for logging
    $order_sql = "SELECT c.name FROM order_header i 
                   LEFT JOIN customers c ON i.customer_id = c.customer_id
                   WHERE i.order_id = ?";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order_data = $order_result->fetch_assoc();
    $customer_name = isset($order_data['name']) ? $order_data['name'] : 'Unknown Customer';

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Update the order status to 'cancel' and add cancellation reason
        $update_order_sql = "UPDATE order_header SET status = 'cancel', cancellation_reason = ? WHERE order_id = ?";
        $stmt = $conn->prepare($update_order_sql);
        $stmt->bind_param("ss", $cancellation_reason, $order_id);
        $stmt->execute();

        // 2. Update all related order items to 'cancel' status
        $update_items_sql = "UPDATE order_items SET status = 'cancel' WHERE order_id = ?";
        $stmt_items = $conn->prepare($update_items_sql);
        $stmt_items->bind_param("s", $order_id);
        $stmt_items->execute();

        // 3. Log the action in user_logs table
        $action_type = "cancel_order";
        $details = "Order ID #$order_id for customer ($customer_name) was canceled by user ID #$user_id. Reason: $cancellation_reason";

        $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                   VALUES (?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isss", $user_id, $action_type, $order_id, $details);
        $log_stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Set success message
        $_SESSION['message'] = "Order #$order_id has been canceled successfully.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback the transaction if something fails
        $conn->rollback();

        // Set error message
        $_SESSION['message'] = "Failed to cancel order. Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    // Build the redirect URL with parameters to maintain the current page state
    $redirect_url = "order_list.php";
    // [redirect URL building code...]
    
    // Ensure the redirect works
    if (headers_sent()) {
        echo "<script>window.location.href='$redirect_url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$redirect_url'></noscript>";
    } else {
        header("Location: $redirect_url");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Pending Orders - Individual</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
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
                        <h4>Pending Individual Orders</h4>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form method="get" class="d-flex">
                                        <input type="text" name="search" class="form-control me-2"
                                            placeholder="Order ID/Customer Name/Status/Pay Status"
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="pending_order_list.php" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        <?php endif; ?>
                                        <!-- Preserve other GET parameters -->
                                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                        <input type="hidden" name="page" value="1">
                                        <!-- Reset to page 1 when searching -->
                                    </form>
                                </div>
                                <div class="col-md-6 text-end">
                                    <form method="get" class="d-inline">
                                        <!-- Preserve search term when changing limit -->
                                        <?php if (!empty($search)): ?>
                                            <input type="hidden" name="search"
                                                value="<?php echo htmlspecialchars($search); ?>">
                                        <?php endif; ?>
                                        <div class="d-inline-block">
                                            <label>Show</label>
                                            <select name="limit" class="form-select d-inline-block w-auto ms-1"
                                                onchange="this.form.submit()">
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

                            <h5 class="mb-3">Manage Pending Individual Orders</h5>
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
                                                        <span class="badge bg-warning">Pending</span>
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
                                                        <div class="btn-group">
                                                            <a href="#" class="btn btn-sm btn-info text-white view-order"
                                                                title="View"
                                                                data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($payStatus == 'paid'): ?>
                                                            <?php else: ?>
                                                                <a href="#" class="btn btn-sm btn-primary text-white mark-paid"
                                                                    title="Mark as Paid"
                                                                    data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>">
                                                                    <i class=""></i> Paid
                                                                </a>
                                                                <a href="#" class="btn btn-sm btn-success text-white mark-dispatch"
                                                                    title="Mark as Dispatch"
                                                                    data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>">
                                                                    <i class="fas fa-truck"></i>
                                                                </a>
                                                                <a href="#" class="btn btn-sm btn-danger cancel-order"
                                                                    title="Cancel Order"
                                                                    data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
        data-customer="<?php echo htmlspecialchars($customerName); ?>"
        data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
        <i class="fas fa-times-circle"></i>
                                                                </a>
                                                                
                                                            <?php endif; ?>
                                                            
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No pending individual orders found</td>
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
                                            <li class="page-item <?php if ($page <= 1)
                                                echo 'disabled'; ?>">
                                                <a class="page-link"
                                                    href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                            </li>

                                            <?php
                                            // Display a limited number of page links
                                            $maxPagesToShow = 5;
                                            $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                                            $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

                                            // Show "..." before the first page link if needed
                                            if ($startPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">1</a>
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
                                                        href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
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
                                                        href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $totalPages; ?></a>
                                                </li>
                                            <?php endif; ?>

                                            <li class="page-item <?php if ($page >= $totalPages)
                                                echo 'disabled'; ?>">
                                                <a class="page-link"
                                                    href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Next</a>
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
            </div>
        </div>
    </div>

    <!-- Modal for Marking Order as Paid -->
    <div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="markPaidModalLabel">Payment
                        Sheet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="markPaidForm" enctype="multipart/form-data">
                        <input type="hidden" name="order_id" id="order_id">

                        <div class="mb-4">
                            <div class="text-center mb-3">
                                <i class="fas fa-file-order fa-3x text-primary"></i>
                            </div>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>Please upload your payment slip to mark this
                                order as paid.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="payment_slip" class="form-label fw-bold">
                                <i class="fas fa-upload me-2"></i>Upload Payment Slip
                            </label>
                            <input type="file" class="form-control" id="payment_slip" name="payment_slip"
                                accept=".jpg,.jpeg,.png,.pdf" required>
                            <div class="form-text mt-2">
                                <i class="fas fa-file-alt me-1"></i>Supported formats: JPG, JPEG, PNG, PDF
                            </div>
                            <div class="form-text">
                                <i class="fas fa-exclamation-circle me-1"></i>Maximum file size: 2MB
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i>Mark as Paid
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<!-- Modified Dispatch Modal -->
<div class="modal fade" id="dispatchOrderModal" tabindex="-1" aria-labelledby="dispatchOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fs-5" id="dispatchOrderModalLabel">Dispatch Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_dispatch.php" method="post" id="dispatch-order-form">
                <input type="hidden" name="order_id" id="dispatch_order_id">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Dispatching this order will assign a tracking number and update the order status.
                    </div>
                    
                    <div class="mb-3">
                        <label for="carrier" class="form-label">Courier Service <span class="text-danger">*</span></label>
                        <select class="form-select" id="carrier" name="carrier" required>
                            <option value="" selected disabled>Select courier service</option>
                            <?php
                            // Fetch active couriers from the database
                            $courier_query = "SELECT courier_id, courier_name FROM couriers WHERE status = 'active' ORDER BY courier_name";
                            $courier_result = $conn->query($courier_query);
                            
                            if ($courier_result && $courier_result->num_rows > 0) {
                                while($courier = $courier_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $courier['courier_id']; ?>"><?php echo htmlspecialchars($courier['courier_name']); ?></option>
                            <?php 
                                endwhile;
                            } else {
                                echo '<option value="" disabled>No couriers available</option>';
                            }
                            ?>
                        </select>
                        <div class="form-text">Select the courier service that will deliver this order</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tracking Number</label>
                        <div class="bg-light rounded p-3" id="tracking_number_display">
                            <span class="text-muted small">Will be generated when you confirm dispatch</span>
                        </div>
                        <div class="form-text">An available tracking number will be assigned from the selected courier</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="dispatch_notes" class="form-label">Dispatch Notes</label>
                        <textarea class="form-control" id="dispatch_notes" name="dispatch_notes" rows="3" 
                                  placeholder="Enter additional notes about this dispatch (optional)"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="dispatch-submit-btn" disabled>
                        <i class="fas fa-truck me-1"></i>Confirm Dispatch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Cancel Order Confirmation -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
                <p><strong>Order ID: </strong><span id="cancel_order_id"></span></p>
                <p><strong>Customer: </strong><span id="cancel_customer_name"></span></p>

                <!-- Add cancellation reason field -->
                <div class="mb-3">
                    <label for="cancellation_reason" class="form-label">Cancellation Reason <span
                            class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3"
                        placeholder="Please provide a reason for cancellation..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form method="post" id="cancelOrderForm">
                    <input type="hidden" name="order_id" id="confirm_cancel_order_id">
                    <input type="hidden" name="cancellation_reason" id="confirm_cancellation_reason">
                    <input type="hidden" name="cancel_order" value="1">
                    <!-- Add hidden fields to preserve current page state -->
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                    <input type="hidden" name="page" value="<?php echo $page; ?>">
                    <button type="submit" class="btn btn-danger" id="confirm_cancel_btn">Confirm Cancel</button>
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

                        // IMPORTANT: Remove any download buttons or unnecessary elements if they exist
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

                // Get additional information about the order
                var orderRow = $(this).closest('tr');
                var orderAmount = orderRow.find('td:eq(4)').text().trim();
                var customerName = orderRow.find('td:eq(1)').text().trim();

                // Directly set the order ID in the form
                $('#order_id').val(orderId);

                // Optionally, you could update the modal with order details
                $('#markPaidModalLabel').html('<i class="fas fa-money-bill-wave me-2"></i>Payment Sheet - Order #' + orderId);

                // Show the modal
                $('#markPaidModal').modal('show');
            });

            // Handle form submission with validation
            $('#markPaidForm').submit(function (e) {
                e.preventDefault(); // Prevent default form submission

                // Simple validation
                var fileInput = $('#payment_slip')[0];

                if (fileInput.files.length === 0) {
                    alert('Please select a file to upload.');
                    return false;
                }

                var fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 2) {
                    alert('File size exceeds 2MB. Please choose a smaller file.');
                    return false;
                }

                // Show loading state
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                submitBtn.prop('disabled', true);

                var formData = new FormData(this);

                $.ajax({
                    url: 'mark_paid.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        // Check if SweetAlert2 is available
                        if (typeof Swal !== 'undefined') {
                            // Show success message with SweetAlert2
                            Swal.fire({
                                title: 'Success!',
                                text: 'Order has been marked as paid successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                $('#markPaidModal').modal('hide');
                                location.reload(); // Reload the page to reflect changes
                            });
                        } else {
                            // Fallback to alert
                            alert('Order marked as paid successfully.');
                            $('#markPaidModal').modal('hide');
                            location.reload(); // Reload the page to reflect changes
                        }
                    },
                    error: function (xhr, status, error) {
                        // Reset button state
                        submitBtn.html(originalText);
                        submitBtn.prop('disabled', false);

                        if (typeof Swal !== 'undefined') {
                            // Show error message with SweetAlert2
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to mark order as paid. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            // Fallback to alert
                            alert('Failed to mark order as paid.');
                        }
                    }
                });
            });
            
            // Reset form when modal is hidden
            $('#markPaidModal').on('hidden.bs.modal', function () {
                $('#markPaidForm')[0].reset();
            });

            // Handle Cancel Order button click
            $('.cancel-order').click(function () {
                var orderId = $(this).data('id');
                var customerName = $(this).data('customer');
                $('#cancel_order_id').text(orderId);
                $('#cancel_customer_name').text(customerName);
                $('#confirm_cancel_order_id').val(orderId);
            });

            // Fade out alert messages after 5 seconds
            setTimeout(function () {
                $(".alert-dismissible").fadeOut("slow");
            }, 5000);
        });


        // Open the modal when clicking the dispatch button
        $(document).on('click', '.mark-dispatch', function (e) {
            e.preventDefault();
            var orderId = $(this).data('id');
            $('#dispatch_order_id').val(orderId);
            $('#dispatchOrderModal').modal('show');
        });

       // Script for handling the dispatch functionality
$(document).ready(function() {
    // When courier selection changes
    $('#carrier').change(function() {
        var courierId = $(this).val();
        var $trackingDisplay = $('#tracking_number_display');
        var $submitBtn = $('#dispatch-submit-btn');
        
        if (!courierId) {
            $trackingDisplay.html('<span class="text-muted small">Will be generated when you confirm dispatch</span>');
            $submitBtn.prop('disabled', true);
            return;
        }
        
        $trackingDisplay.html('<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Checking available tracking numbers...');
        $submitBtn.prop('disabled', true);
        
        $.ajax({
            url: 'get_tracking_number.php',
            type: 'GET',
            data: { courier_id: courierId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $trackingDisplay.html('<span class="fw-medium">' + response.tracking_number + '</span>' +
                                         '<div class="text-success small mt-1"><i class="fas fa-check-circle me-1"></i>Tracking number is available</div>');
                    $submitBtn.prop('disabled', false);
                } else {
                    $trackingDisplay.html('<div class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>' + 
                                         response.message + '</div>');
                    $submitBtn.prop('disabled', true);
                }
            },
            error: function() {
                $trackingDisplay.html('<div class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i>Error loading tracking numbers</div>');
                $submitBtn.prop('disabled', true);
            }
        });
    });

    // Handle the dispatch form submission
    $('#dispatch-order-form').submit(function(e) {
        e.preventDefault();
        var $form = $(this);
        var $submitBtn = $('#dispatch-submit-btn');
        var originalBtnText = $submitBtn.html();
        
        // Disable the submit button and show loading state
        $submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
        $submitBtn.prop('disabled', true);
        
        $.ajax({
            url: 'process_dispatch.php',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: 'Order has been dispatched successfully with tracking number: ' + response.tracking_number,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(function() {
                        // Reload the page or redirect
                        location.reload();
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    
                    // Reset button state
                    $submitBtn.html(originalBtnText);
                    $submitBtn.prop('disabled', false);
                }
            },
            error: function() {
                // Show error message for AJAX failure
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing your request. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                
                // Reset button state
                $submitBtn.html(originalBtnText);
                $submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Reset the form when the modal is closed
    $('#dispatchOrderModal').on('hidden.bs.modal', function() {
        $('#dispatch-order-form')[0].reset();
        $('#tracking_number_display').html('<span class="text-muted small">Will be generated when you confirm dispatch</span>');
        $('#dispatch-submit-btn').prop('disabled', true);
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