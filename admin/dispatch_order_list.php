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
    $redirect_url = "dispatch_order_list.php";
    if (!empty($search)) {
        $redirect_url .= "?search=" . urlencode($search);
        if (isset($limit)) {
            $redirect_url .= "&limit=" . $limit;
        }
        if (isset($page)) {
            $redirect_url .= "&page=" . $page;
        }
    } else if (isset($limit) || isset($page)) {
        $redirect_url .= "?";
        if (isset($limit)) {
            $redirect_url .= "limit=" . $limit;
        }
        if (isset($page)) {
            $redirect_url .= (isset($limit) ? "&" : "") . "page=" . $page;
        }
    }

    // Ensure the redirect works
    if (headers_sent()) {
        echo "<script>window.location.href='$redirect_url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$redirect_url'></noscript>";
    } else {
        header("Location: $redirect_url");
    }
    exit();
}

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query with JOIN to customers table and filter by dispatch status
$countSql = "SELECT COUNT(*) as total FROM order_header i 
             LEFT JOIN customers c ON i.customer_id = c.customer_id
             LEFT JOIN payments p ON i.order_id = p.order_id
             LEFT JOIN users u ON p.pay_by = u.id
             LEFT JOIN couriers co ON i.courier_id = co.courier_id
             WHERE i.status = 'dispatch'";

$sql = "SELECT i.*, c.name as customer_name, 
               p.payment_id, p.amount_paid, p.payment_method, p.payment_date, p.pay_by,
               u.name as paid_by_name,
               co.courier_name
        FROM order_header i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        LEFT JOIN payments p ON i.order_id = p.order_id
        LEFT JOIN users u ON p.pay_by = u.id
        LEFT JOIN couriers co ON i.courier_id = co.courier_id
        WHERE i.status = 'dispatch'";

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
                        i.tracking_number LIKE '%$searchTerm%' OR
                        co.courier_name LIKE '%$searchTerm%' OR
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
    <title>Dispatch Orders</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
                        <h4>Dispatch Orders</h4>
                    </div>

                    <!-- Display alerts for cancel order operation -->
                    <?php if (isset($_SESSION['message']) && isset($_SESSION['message_type'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show"
                            role="alert">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php
                        // Clear the message after displaying
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                        ?>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form method="get" class="d-flex" id="searchForm">
                                        <input type="text" name="search" class="form-control me-2"
                                            placeholder="Order ID/Customer Name/Tracking Number/Courier"
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="dispatch_order_list.php" class="btn btn-outline-secondary ms-2">
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

                            <h5 class="mb-3">Manage Dispatch Orders</h5>
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
                                            <th>Courier</th>
                                            <th>Tracking Number</th>
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
                                                    <td>
                                                        <?php echo isset($row['courier_name']) ? htmlspecialchars($row['courier_name']) : 'N/A'; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo isset($row['tracking_number']) && !empty($row['tracking_number']) ? htmlspecialchars($row['tracking_number']) : 'N/A'; ?>
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
                                                        <span class="badge bg-primary">Dispatch</span>
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
                                                                data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
                                                                data-paystatus="<?php echo $payStatus; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($payStatus != 'paid'): ?>
                                                                <a href="#" class="btn btn-sm btn-primary text-white mark-paid"
                                                                    title="Mark as Paid"
                                                                    data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>">
                                                                    <i class=""></i> Paid
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($payStatus != 'paid'): // Only show cancel button if order is not paid ?>
                                                                <a href="#" class="btn btn-sm btn-danger cancel-order"
                                                                    title="Cancel Order"
                                                                    data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
                                                                    data-customer="<?php echo $customerName; ?>">
                                                                    <i class="fas fa-times-circle"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No dispatch orders found</td>
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
                <!-- Dispatch information section -->
                <div class="modal-body border-top" id="dispatchInfoSection" style="display:none;">
                    <h5><i class="fas fa-shipping-fast me-2"></i>Dispatch Information</h5>
                    <div id="dispatchDetails" class="mt-3">
                        <!-- Dispatch details will be loaded here -->
                    </div>
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
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="markPaidModalLabel">Payment Sheet</h5>
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
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function () {
            // Handle "View" button click
            $('.view-order').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var orderId = $(this).data('id'); // Get the order ID
                var payStatus = $(this).data('paystatus'); // Get the payment status

                // Show loading message in the modal
                $('#orderDetails').html('Loading...');

                // Hide info sections initially
                $('#dispatchInfoSection').hide();
                $('#dispatchDetails').html('');
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
                            '<i class="fas fa-print me-1"></i>Print Order</button>'
                        '<i class="fas fa-print me-1"></i>Print Order</button>' +
                            '</div>';
                        $('#orderDetails').append(printButton);

                        // Fetch dispatch information
                        $.ajax({
                            url: 'get_dispatch_info.php',
                            type: 'GET',
                            data: { order_id: orderId },
                            dataType: 'json',
                            success: function (dispatchData) {
                                if (dispatchData && dispatchData.success) {
                                    // Populate dispatch details
                                    var dispatchHtml = '<div class="row">';
                                    dispatchHtml += '<div class="col-md-6">';
                                    dispatchHtml += '<p><strong>Courier:</strong> ' + (dispatchData.courier_name || 'N/A') + '</p>';
                                    dispatchHtml += '<p><strong>Tracking Number:</strong> ' + (dispatchData.tracking_number || 'N/A') + '</p>';
                                    dispatchHtml += '</div>';
                                    dispatchHtml += '<div class="col-md-6">';
                                    dispatchHtml += '<p><strong>Dispatch Date:</strong> ' + (dispatchData.dispatch_date || 'N/A') + '</p>';
                                    dispatchHtml += '<p><strong>Expected Delivery:</strong> ' + (dispatchData.expected_delivery || 'N/A') + '</p>';
                                    dispatchHtml += '</div>';
                                    dispatchHtml += '</div>';

                                    if (dispatchData.notes) {
                                        dispatchHtml += '<div class="row mt-2">';
                                        dispatchHtml += '<div class="col-12">';
                                        dispatchHtml += '<p><strong>Dispatch Notes:</strong></p>';
                                        dispatchHtml += '<p class="border p-2 bg-light">' + dispatchData.notes + '</p>';
                                        dispatchHtml += '</div>';
                                        dispatchHtml += '</div>';
                                    }

                                    $('#dispatchDetails').html(dispatchHtml);
                                    $('#dispatchInfoSection').show();
                                }
                            },
                            error: function () {
                                $('#dispatchDetails').html('<div class="alert alert-warning">Failed to load dispatch information.</div>');
                                $('#dispatchInfoSection').show();
                            }
                        });

                        // If order is paid, fetch payment information
                        if (payStatus === 'paid') {
                            $.ajax({
                                url: 'get_payment_info.php',
                                type: 'GET',
                                data: { order_id: orderId },
                                dataType: 'json',
                                success: function (paymentData) {
                                    if (paymentData && paymentData.success) {
                                        // Populate payment details
                                        var paymentHtml = '<div class="row">';
                                        paymentHtml += '<div class="col-md-6">';
                                        paymentHtml += '<p><strong>Payment ID:</strong> ' + (paymentData.payment_id || 'N/A') + '</p>';
                                        paymentHtml += '<p><strong>Payment Method:</strong> ' + (paymentData.payment_method || 'N/A') + '</p>';
                                        paymentHtml += '<p><strong>Amount Paid:</strong> ' + (paymentData.currency_symbol || 'Rs') + ' ' + (paymentData.amount_paid || '0.00') + '</p>';
                                        paymentHtml += '</div>';
                                        paymentHtml += '<div class="col-md-6">';
                                        paymentHtml += '<p><strong>Payment Date:</strong> ' + (paymentData.payment_date || 'N/A') + '</p>';
                                        paymentHtml += '<p><strong>Processed By:</strong> ' + (paymentData.processed_by || 'N/A') + '</p>';
                                        paymentHtml += '</div>';
                                        paymentHtml += '</div>';

                                        if (paymentData.payment_slip) {
                                            paymentHtml += '<div class="row mt-3">';
                                            paymentHtml += '<div class="col-12">';
                                            paymentHtml += '<p><strong>Payment Slip:</strong></p>';
                                            paymentHtml += '<div class="text-center">';
                                            paymentHtml += '<a href="' + paymentData.payment_slip + '" target="_blank" class="btn btn-sm btn-outline-primary">';
                                            paymentHtml += '<i class="fas fa-file-image me-1"></i>View Payment Slip</a>';
                                            paymentHtml += '</div>';
                                            paymentHtml += '</div>';
                                            paymentHtml += '</div>';
                                        }

                                        $('#paymentDetails').html(paymentHtml);
                                        $('#paymentInfoSection').show();
                                    }
                                },
                                error: function () {
                                    $('#paymentDetails').html('<div class="alert alert-warning">Failed to load payment information.</div>');
                                    $('#paymentInfoSection').show();
                                }
                            });
                        }
                    },
                    error: function () {
                        // Display error message if AJAX request fails
                        $('#orderDetails').html('<div class="alert alert-danger">Failed to load order details. Please try again.</div>');
                    }
                });

                // Show the modal
                $('#viewOrderModal').modal('show');
            });

            // Handle "Print Order" button click
            $(document).on('click', '.print-order', function () {
                var orderId = $(this).data('id');
                // Redirect to download_order.php for printing/downloading
                window.open('download_order.php?id=' + orderId, '_blank');
            });

            // Handle "Mark as Paid" button click
            $('.mark-paid').click(function (e) {
                e.preventDefault();
                var orderId = $(this).data('id');
                $('#order_id').val(orderId);
                $('#markPaidModal').modal('show');
            });

            // Handle form submission for marking an order as paid
            $('#markPaidForm').submit(function (e) {
                e.preventDefault();
                var formData = new FormData(this);

                // Add current page state parameters
                formData.append('search', '<?php echo htmlspecialchars($search); ?>');
                formData.append('limit', '<?php echo $limit; ?>');
                formData.append('page', '<?php echo $page; ?>');

                $.ajax({
                    url: 'process_payment.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        var result = JSON.parse(response);
                        if (result.status === 'success') {
                            // Close modal
                            $('#markPaidModal').modal('hide');

                            // Show success message and reload the page
                            alert(result.message);
                            window.location.reload();
                        } else {
                            // Show error message
                            alert('Error: ' + result.message);
                        }
                    },
                    error: function () {
                        alert('An error occurred while processing the payment. Please try again.');
                    }
                });
            });

            // Handle "Cancel Order" button click
            $('.cancel-order').click(function (e) {
                e.preventDefault();
                var orderId = $(this).data('id');
                var customerName = $(this).data('customer');

                $('#cancel_order_id').text(orderId);
                $('#cancel_customer_name').text(customerName);
                $('#confirm_cancel_order_id').val(orderId);

                $('#cancelOrderModal').modal('show');
            });

            // Validate and update the cancellation reason when submitting the form
            $('#cancelOrderForm').submit(function (e) {
                var reason = $('#cancellation_reason').val().trim();

                if (reason === '') {
                    e.preventDefault();
                    alert('Please provide a reason for cancellation.');
                    $('#cancellation_reason').focus();
                    return false;
                }

                // Update the hidden reason field before submitting
                $('#confirm_cancellation_reason').val(reason);
                return true;
            });

            // Transfer cancellation reason from modal textarea to hidden form field
            $('#cancelOrderModal').on('shown.bs.modal', function () {
                $('#cancellation_reason').focus();
            });

            $('#cancellation_reason').on('input', function () {
                $('#confirm_cancellation_reason').val($(this).val());
            });
        });
    </script>

    <script src="js/scripts.js"></script>
</body>

</html>