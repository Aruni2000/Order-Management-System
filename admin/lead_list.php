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

// Process lead status update
if (isset($_POST['update_status']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id']; // Current logged-in user ID
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : 'pending';
    $status_notes = isset($_POST['status_notes']) ? trim($_POST['status_notes']) : '';

    // Get lead details for logging
    $lead_sql = "SELECT full_name FROM order_header WHERE order_id = ?";
    $stmt = $conn->prepare($lead_sql);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $lead_result = $stmt->get_result();
    $lead_data = $lead_result->fetch_assoc();
    $customer_name = isset($lead_data['full_name']) ? $lead_data['full_name'] : 'Unknown Customer';

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update the lead status and add notes
        $update_lead_sql = "UPDATE order_header SET status = ?, notes = CONCAT(IFNULL(notes, ''), '\n', NOW(), ' - Status changed to ', ?, ' - ', ?) WHERE order_id = ? AND interface = 'leads'";
        $stmt = $conn->prepare($update_lead_sql);
        $stmt->bind_param("ssss", $new_status, $new_status, $status_notes, $order_id);
        $stmt->execute();

        // Log the action in user_logs table
        $action_type = "update_lead_status";
        $details = "Lead ID #$order_id for customer ($customer_name) status updated to $new_status by user ID #$user_id. Notes: $status_notes";

        $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                   VALUES (?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isss", $user_id, $action_type, $order_id, $details);
        $log_stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Set success message
        $_SESSION['message'] = "Lead #$order_id status has been updated successfully.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback the transaction if something fails
        $conn->rollback();

        // Set error message
        $_SESSION['message'] = "Failed to update lead status. Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    // Build the redirect URL
    $redirect_url = "lead_list.php";

    // Add search parameter if it exists
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $redirect_url .= "?search=" . urlencode($_POST['search']);

        // Add limit and page if they exist
        if (isset($_POST['limit']) && !empty($_POST['limit'])) {
            $redirect_url .= "&limit=" . (int) $_POST['limit'];
        }
        if (isset($_POST['page']) && !empty($_POST['page'])) {
            $redirect_url .= "&page=" . (int) $_POST['page'];
        }
    }
    // If no search but we have limit or page
    else if (
        (isset($_POST['limit']) && !empty($_POST['limit'])) ||
        (isset($_POST['page']) && !empty($_POST['page']))
    ) {
        $redirect_url .= "?";
        if (isset($_POST['limit']) && !empty($_POST['limit'])) {
            $redirect_url .= "limit=" . (int) $_POST['limit'];
        }
        if (isset($_POST['page']) && !empty($_POST['page'])) {
            $redirect_url .= (isset($_POST['limit']) && !empty($_POST['limit']) ? "&" : "") . "page=" . (int) $_POST['page'];
        }
    }

    // Make sure we're enforcing the redirect properly
    if (headers_sent()) {
        echo "<script>window.location.href='$redirect_url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$redirect_url'></noscript>";
    } else {
        header("Location: $redirect_url");
    }
    exit();
}

// Process lead conversion to order
if (isset($_POST['convert_to_order']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id']; // Current logged-in user ID

    // Get lead details for logging
    $lead_sql = "SELECT full_name FROM order_header WHERE order_id = ?";
    $stmt = $conn->prepare($lead_sql);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $lead_result = $stmt->get_result();
    $lead_data = $lead_result->fetch_assoc();
    $customer_name = isset($lead_data['full_name']) ? $lead_data['full_name'] : 'Unknown Customer';

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update the interface from 'leads' to 'individual'
        $update_lead_sql = "UPDATE order_header SET interface = 'individual', status = 'pending', 
                            notes = CONCAT(IFNULL(notes, ''), '\n', NOW(), ' - Converted to order') 
                            WHERE order_id = ? AND interface = 'leads'";
        $stmt = $conn->prepare($update_lead_sql);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();

        // Log the action in user_logs table
        $action_type = "convert_lead_to_order";
        $details = "Lead ID #$order_id for customer ($customer_name) was converted to an order by user ID #$user_id.";

        $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                   VALUES (?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isss", $user_id, $action_type, $order_id, $details);
        $log_stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Set success message
        $_SESSION['message'] = "Lead #$order_id has been successfully converted to an order.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback the transaction if something fails
        $conn->rollback();

        // Set error message
        $_SESSION['message'] = "Failed to convert lead to order. Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    // Redirect back to lead list page with same parameters
    $redirect_url = "lead_list.php";
    if (isset($_POST['search']) || isset($_POST['limit']) || isset($_POST['page'])) {
        $params = [];
        if (isset($_POST['search']) && !empty($_POST['search'])) {
            $params[] = "search=" . urlencode($_POST['search']);
        }
        if (isset($_POST['limit']) && !empty($_POST['limit'])) {
            $params[] = "limit=" . (int) $_POST['limit'];
        }
        if (isset($_POST['page']) && !empty($_POST['page'])) {
            $params[] = "page=" . (int) $_POST['page'];
        }
        if (!empty($params)) {
            $redirect_url .= "?" . implode("&", $params);
        }
    }

    if (headers_sent()) {
        echo "<script>window.location.href='$redirect_url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$redirect_url'></noscript>";
    } else {
        header("Location: $redirect_url");
    }
    exit();
}

// Delete a lead
if (isset($_POST['delete_lead']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id']; // Current logged-in user ID

    // Get lead details for logging
    $lead_sql = "SELECT full_name FROM order_header WHERE order_id = ?";
    $stmt = $conn->prepare($lead_sql);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $lead_result = $stmt->get_result();
    $lead_data = $lead_result->fetch_assoc();
    $customer_name = isset($lead_data['full_name']) ? $lead_data['full_name'] : 'Unknown Customer';

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete related order items first (foreign key constraint)
        $delete_items_sql = "DELETE FROM order_items WHERE order_id = ?";
        $stmt_items = $conn->prepare($delete_items_sql);
        $stmt_items->bind_param("s", $order_id);
        $stmt_items->execute();

        // Delete the lead record
        $delete_lead_sql = "DELETE FROM order_header WHERE order_id = ? AND interface = 'leads'";
        $stmt = $conn->prepare($delete_lead_sql);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();

        // Log the action in user_logs table
        $action_type = "delete_lead";
        $details = "Lead ID #$order_id for customer ($customer_name) was deleted by user ID #$user_id.";

        $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isss", $user_id, $action_type, $order_id, $details);
        $log_stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Set success message
        $_SESSION['message'] = "Lead #$order_id has been deleted successfully.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback the transaction if something fails
        $conn->rollback();

        // Set error message
        $_SESSION['message'] = "Failed to delete lead. Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    // Redirect back
    $redirect_url = "lead_list.php";
    if (isset($_POST['search']) || isset($_POST['limit']) || isset($_POST['page'])) {
        $params = [];
        if (isset($_POST['search']) && !empty($_POST['search'])) {
            $params[] = "search=" . urlencode($_POST['search']);
        }
        if (isset($_POST['limit']) && !empty($_POST['limit'])) {
            $params[] = "limit=" . (int) $_POST['limit'];
        }
        if (isset($_POST['page']) && !empty($_POST['page'])) {
            $params[] = "page=" . (int) $_POST['page'];
        }
        if (!empty($params)) {
            $redirect_url .= "?" . implode("&", $params);
        }
    }

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

// Build basic SQL query with direct columns from order_header
$countSql = "SELECT COUNT(*) as total FROM order_header i 
             LEFT JOIN users u ON i.created_by = u.id 
             WHERE i.interface = 'leads'";

$sql = "SELECT i.*, u.name as creator_name
        FROM order_header i 
        LEFT JOIN users u ON i.created_by = u.id
        WHERE i.interface = 'leads'";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " AND (
                        i.order_id LIKE '%$searchTerm%' OR 
                        i.full_name LIKE '%$searchTerm%' OR 
                        i.mobile LIKE '%$searchTerm%' OR
                        i.issue_date LIKE '%$searchTerm%' OR 
                        i.status LIKE '%$searchTerm%' OR
                        u.name LIKE '%$searchTerm%')";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY i.created_at DESC LIMIT $limit OFFSET $offset";

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
    <title>Lead List</title>
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
                        <h4>Lead List</h4>

                    </div>

                    <!-- Display alert messages if any -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show"
                            role="alert">
                            <?php
                            echo $_SESSION['message'];
                            unset($_SESSION['message']);
                            unset($_SESSION['message_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form method="get" class="d-flex">
                                        <input type="text" name="search" class="form-control me-2"
                                            placeholder="Lead ID/Customer Name/Phone/Status"
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="lead_list.php" class="btn btn-outline-secondary ms-2">
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

                            <h5 class="mb-3">Manage Leads</h5>
                            <?php if (!empty($search)): ?>
                                <div class="alert alert-info">
                                    Showing search results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                                    (<?php echo $totalRows; ?> results found)
                                </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="lead_table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Lead ID</th>
                                            <th>Customer Name</th>
                                            <th>Contact Number</th>
                                            <th>Products</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Created Date</th>
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
                                                        $customerName = isset($row['full_name']) ? htmlspecialchars($row['full_name']) : 'N/A';
                                                        echo $customerName;
                                                        ?>
                                                    </td>
                                                    <td><?php echo isset($row['mobile']) ? htmlspecialchars($row['mobile']) : 'N/A'; ?>
                                                    </td>
                                                    <td><?php echo isset($row['product_code']) ? htmlspecialchars($row['product_code']) : 'N/A'; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status = isset($row['status']) ? $row['status'] : 'pending';
                                                        if ($status == 'done'): ?>
                                                            <span class="badge bg-success">Complete</span>
                                                        <?php elseif ($status == 'cancel'): ?>
                                                            <span class="badge bg-danger">Canceled</span>
                                                        <?php elseif ($status == 'dispatch'): ?>
                                                            <span class="badge bg-primary">Dispatched</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if (isset($row['created_by']) && isset($row['creator_name'])) {
                                                            echo htmlspecialchars($row['creator_name']) . ' (' . htmlspecialchars($row['created_by']) . ')';
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo isset($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : 'N/A'; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="#" class="btn btn-sm btn-info text-white view-lead"
                                                                title="View"
                                                                data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </a>

                                                            <a href="edit_lead.php?id=<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
                                                                class="btn btn-sm btn-warning text-white" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>

                                                            <button type="button" class="btn btn-sm btn-success convert-lead"
                                                                title="Convert to Order"
                                                                data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
                                                                data-customer="<?php echo htmlspecialchars($customerName); ?>"
                                                                data-bs-toggle="modal" data-bs-target="#convertLeadModal">
                                                                <i class="fas fa-exchange-alt"></i>
                                                            </button>

                                                            <button type="button" class="btn btn-sm btn-primary update-status"
                                                                title="Update Status"
                                                                data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
                                                                data-customer="<?php echo htmlspecialchars($customerName); ?>"
                                                                data-status="<?php echo htmlspecialchars($status); ?>"
                                                                data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                                                <i class="fas fa-tasks"></i>
                                                            </button>

                                                            <button type="button" class="btn btn-sm btn-danger delete-lead"
                                                                title="Delete Lead"
                                                                data-id="<?php echo isset($row['order_id']) ? $row['order_id'] : ''; ?>"
                                                                data-customer="<?php echo htmlspecialchars($customerName); ?>"
                                                                data-bs-toggle="modal" data-bs-target="#deleteLeadModal">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No leads found</td>
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
                                                        href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php
                                                                 echo $totalPages; ?></a>
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

    <!-- View Lead Modal - Will be implemented with AJAX to fetch and display lead details -->
    <div class="modal fade" id="viewLeadModal" tabindex="-1" aria-labelledby="viewLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewLeadModalLabel">Lead Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="leadDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading lead details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Continuation of Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Lead Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm" method="post">
                        <input type="hidden" name="order_id" id="status_order_id">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                        <input type="hidden" name="page" value="<?php echo $page; ?>">

                        <div class="mb-3">
                            <label for="lead_customer" class="form-label">Customer:</label>
                            <input type="text" class="form-control" id="lead_customer" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="new_status" class="form-label">Status:</label>
                            <select class="form-select" id="new_status" name="new_status" required>
                                <option value="pending">Pending</option>
                                <option value="done">Complete</option>
                                <option value="cancel">Canceled</option>
                                <option value="dispatch">Dispatched</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status_notes" class="form-label">Notes:</label>
                            <textarea class="form-control" id="status_notes" name="status_notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="updateStatusForm" name="update_status" class="btn btn-primary">Update
                        Status</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Convert Lead to Order Modal -->
    <div class="modal fade" id="convertLeadModal" tabindex="-1" aria-labelledby="convertLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="convertLeadModalLabel">Convert Lead to Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="convertLeadForm" method="post">
                        <input type="hidden" name="order_id" id="convert_order_id">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                        <input type="hidden" name="page" value="<?php echo $page; ?>">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You are about to convert this lead to an order. This will change its status and make it
                            available in the orders section.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Customer:</label>
                            <input type="text" class="form-control" id="convert_customer" readonly>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="convertLeadForm" name="convert_to_order" class="btn btn-success">Convert
                        to Order</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Lead Modal -->
    <div class="modal fade" id="deleteLeadModal" tabindex="-1" aria-labelledby="deleteLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLeadModalLabel">Delete Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deleteLeadForm" method="post">
                        <input type="hidden" name="order_id" id="delete_order_id">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                        <input type="hidden" name="page" value="<?php echo $page; ?>">

                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning!</strong> You are about to delete this lead. This action cannot be undone.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Customer:</label>
                            <input type="text" class="form-control" id="delete_customer" readonly>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteLeadForm" name="delete_lead" class="btn btn-danger">Delete
                        Lead</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // View Lead Details
            $('.view-lead').click(function (e) {
                e.preventDefault();
                var leadId = $(this).data('id');
                $('#viewLeadModal').modal('show');

                // AJAX call to fetch lead details
                $.ajax({
                    url: 'get_lead_details.php',
                    type: 'GET',
                    data: { id: leadId },
                    success: function (response) {
                        $('#leadDetailsContent').html(response);
                    },
                    error: function () {
                        $('#leadDetailsContent').html('<div class="alert alert-danger">Error loading lead details. Please try again.</div>');
                    }
                });
            });

            // Update Status Modal
            $('.update-status').click(function () {
                var orderId = $(this).data('id');
                var customer = $(this).data('customer');
                var currentStatus = $(this).data('status');

                $('#status_order_id').val(orderId);
                $('#lead_customer').val(customer);
                $('#new_status').val(currentStatus);
            });

            // Convert Lead Modal
            $('.convert-lead').click(function () {
                var orderId = $(this).data('id');
                var customer = $(this).data('customer');

                $('#convert_order_id').val(orderId);
                $('#convert_customer').val(customer);
            });

            // Delete Lead Modal
            $('.delete-lead').click(function () {
                var orderId = $(this).data('id');
                var customer = $(this).data('customer');

                $('#delete_order_id').val(orderId);
                $('#delete_customer').val(customer);
            });

            // Auto-dismiss alerts after 5 seconds
            setTimeout(function () {
                $('.alert-dismissible').alert('close');
            }, 5000);
        });
        // Sidebar Toggle Script
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function (e) {
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