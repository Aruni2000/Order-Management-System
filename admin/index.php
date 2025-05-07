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

// Include helper functions
include 'functions.php';

// Process filter parameters if present - fixed date handling
$date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) ? $_GET['date_to'] : '';

// Initialize statistics with default values
$stats = [
    'total_users' => 0,
    'all_inquiries' => 0,
    'pending_inquiries' => 0,
    'approved_inquiries' => 0,
    'rejected_inquiries' => 0,
    'total_customers' => 0,
    'total_products' => 0,
    'total_orders' => 0,
    'complete_orders' => 0,
    'pending_orders' => 0,
    'cancel_orders' => 0,
    'dispatch_orders' => 0
];

// Helper function to safely query the database
function safeQuery($conn, $query)
{
    try {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['count']) ? $row['count'] : 0;
        }
    } catch (Exception $e) {
        // Log error for debugging if needed
        // error_log("Database query error: " . $e->getMessage());
        return 0;
    }
    return 0;
}

// Prepare date conditions for SQL query - FIXED DATE HANDLING
$date_condition = "";
if (!empty($date_from) && !empty($date_to)) {
    // Format dates properly for SQL with time components to include the full day
    $date_condition = " AND (order_date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59')";
} elseif (!empty($date_from)) {
    $date_condition = " AND order_date >= '$date_from 00:00:00'";
} elseif (!empty($date_to)) {
    $date_condition = " AND order_date <= '$date_to 23:59:59'";
}

// Safely fetch all statistics
$stats['total_users'] = safeQuery($conn, "SELECT COUNT(*) as count FROM users");

// Check if customers table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'customers'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['total_customers'] = safeQuery($conn, "SELECT COUNT(*) as count FROM customers");
}

// Check if products table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'products'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['total_products'] = safeQuery($conn, "SELECT COUNT(*) as count FROM products");
}

// Check if orders table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'order_header'");
if ($tableExists && $tableExists->num_rows > 0) {
    // Debug: Print date condition to check what's being applied
    // echo "<!-- Date condition: $date_condition -->";
    
    // Base query for total orders
    $total_orders_query = "SELECT COUNT(*) as count FROM order_header WHERE 1=1";
    
    // Apply date filter if set
    if (!empty($date_condition)) {
        $total_orders_query .= $date_condition;
    }
    
    // Debug: Print queries to verify correctness
    // echo "<!-- Total orders query: $total_orders_query -->";
    
    $stats['total_orders'] = safeQuery($conn, $total_orders_query);
    
    // Count for complete orders
    $complete_orders_query = "SELECT COUNT(*) as count FROM order_header WHERE status = 'done'";
    if (!empty($date_condition)) {
        $complete_orders_query .= $date_condition;
    }
    $stats['complete_orders'] = safeQuery($conn, $complete_orders_query);
    
    // Count for pending orders
    $pending_orders_query = "SELECT COUNT(*) as count FROM order_header WHERE status = 'pending'";
    if (!empty($date_condition)) {
        $pending_orders_query .= $date_condition;
    }
    $stats['pending_orders'] = safeQuery($conn, $pending_orders_query);
    
    // Count for cancel orders
    $cancel_orders_query = "SELECT COUNT(*) as count FROM order_header WHERE status = 'cancel'";
    if (!empty($date_condition)) {
        $cancel_orders_query .= $date_condition;
    }
    $stats['cancel_orders'] = safeQuery($conn, $cancel_orders_query);
    
    // Count for dispatch orders
    $dispatch_orders_query = "SELECT COUNT(*) as count FROM order_header WHERE status = 'dispatch'";
    if (!empty($date_condition)) {
        $dispatch_orders_query .= $date_condition;
    }
    $stats['dispatch_orders'] = safeQuery($conn, $dispatch_orders_query);
}

// Add debugging function to check database values if needed
function debugOrderCounts($conn, $date_from, $date_to) {
    if (!empty($date_from) || !empty($date_to)) {
        $debug_query = "SELECT status, COUNT(*) as count FROM order_header ";
        $where_clauses = [];
        
        if (!empty($date_from)) {
            $where_clauses[] = "order_date >= '$date_from 00:00:00'";
        }
        
        if (!empty($date_to)) {
            $where_clauses[] = "order_date <= '$date_to 23:59:59'";
        }
        
        if (!empty($where_clauses)) {
            $debug_query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $debug_query .= " GROUP BY status";
        
        $result = $conn->query($debug_query);
        echo "<!-- Debug order counts: ";
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "{$row['status']}: {$row['count']} | ";
            }
        } else {
            echo "No results found for the date range";
        }
        echo " -->";
    }
}
// Uncomment to debug
// debugOrderCounts($conn, $date_from, $date_to);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="dark" data-toggled="close">

<head>
    <?php
    // Include header.php file which contains all the meta tags, CSS links, and other header elements
    include('header.php');
    ?>
    <!-- TITLE -->
    <title> Admin Dashboard</title>

    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    
    <!-- DatePicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        /* Minimalist styling for the dashboard */
        body {
            background-color: #f9fafb;
        }
        
        .stats-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .stats-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: #1f2937;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .stats-flex {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .stat-item {
            flex: 1;
            min-width: 180px;
            padding: 1rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .stat-icon {
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
            color: #4b5563;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0.25rem 0;
            color: #111827;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .stat-link {
            display: block;
            margin-top: 0.75rem;
            text-decoration: none;
            color: #2563eb;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .stat-link:hover {
            text-decoration: underline;
        }
        
        /* Filter form styling */
        .filter-form {
            background-color: #fff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
        }
        
        .filter-group {
            flex: 1;
            min-width: 180px;
        }
        
        .filter-label {
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
            color: #4b5563;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        
        .btn-secondary {
            background-color: #f3f4f6;
            border-color: #e5e7eb;
            color: #4b5563;
        }
        
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
        
        .btn-success {
            background-color: #10b981;
            border-color: #10b981;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        /* Order stats grid */
        .order-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-indicator.complete {
            background-color: #10b981;
        }
        
        .status-indicator.pending {
            background-color: #f59e0b;
        }
        
        .status-indicator.cancel {
            background-color: #ef4444;
        }
        
        .status-indicator.dispatch {
            background-color: #3b82f6;
        }
        
        .form-control, .form-select {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }
        
        /* Print styles */
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 0;
                margin: 0;
                background-color: #fff;
            }
            
            .container-fluid {
                width: 100%;
                padding: 0;
            }
            
            .stats-container {
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }
        }
        
        /* Styles for the flatpickr input */
        .flatpickr-input {
            background-color: #fff !important;
        }
    </style>
</head>

<body class="sb-nav-fixed">

    <?php
    // Include navbar.php file which contains the top navigation bar
    include 'navbar.php';
    ?>

    <div id="layoutSidenav">
        <?php
        // Include sidebar.php file which contains the side navigation menu
        include 'sidebar.php';
        ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Dashboard</h1>
                    <ol class="breadcrumb mb-4">
                        <!--<li class="breadcrumb-item active"> Dashboard</li>-->
                    </ol>

                    <!-- Order Statistics Container -->
                    <div class="stats-container">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="stats-title">Order Management</h2>
                        </div>
                        
                        <!-- Filter Form (Simplified) -->
                        <div class="filter-form no-print">
                            <form method="GET" action="" id="orderFilterForm" class="d-flex flex-wrap align-items-end gap-3">
                                <div class="filter-group">
                                    <label for="date_from" class="filter-label">Date From</label>
                                    <input type="text" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>" placeholder="Select date">
                                </div>
                                <div class="filter-group">
                                    <label for="date_to" class="filter-label">Date To</label>
                                    <input type="text" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>" placeholder="Select date">
                                </div>
                                <div class="filter-buttons mt-auto">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="resetButton">
                                        <i class="fas fa-redo"></i> Reset
                                    </button>
                                    <!-- <button type="button" class="btn btn-success" id="printButton">
                                        <i class="fas fa-print"></i> Print
                                    </button> -->
                                </div>
                            </form>
                        </div>
                        
                        <div class="order-stats-grid mt-4">
                            <!-- Total Orders -->
                            <div class="stat-item">
                                <div class="stat-icon text-info">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div class="stat-value"><?= $stats['total_orders'] ?></div>
                                <div class="stat-label">Total Orders</div>
                                <a href="order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-link">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>

                            <!-- Complete Orders -->
                            <div class="stat-item">
                                <div class="stat-icon text-success">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <div class="stat-value"><?= $stats['complete_orders'] ?></div>
                                <div class="stat-label">
                                    <span class="status-indicator complete"></span>Complete
                                </div>
                                <a href="complete_order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-link">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>

                            <!-- Pending Orders -->
                            <div class="stat-item">
                                <div class="stat-icon text-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-value"><?= $stats['pending_orders'] ?></div>
                                <div class="stat-label">
                                    <span class="status-indicator pending"></span>Pending
                                </div>
                                <a href="pending_order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-link">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>
                            
                            <!-- Dispatch Orders -->
                            <div class="stat-item">
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="stat-value"><?= $stats['dispatch_orders'] ?></div>
                                <div class="stat-label">
                                    <span class="status-indicator dispatch"></span>Dispatch
                                </div>
                                <a href="dispatch_order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-link">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>
                            
                            <!-- Canceled Orders -->
                            <div class="stat-item">
                                <div class="stat-icon text-danger">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div class="stat-value"><?= $stats['cancel_orders'] ?></div>
                                <div class="stat-label">
                                    <span class="status-indicator cancel"></span>Canceled
                                </div>
                                <a href="cancel_order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-link">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Combined Users, Customers & Products Container -->
                    <div class="stats-container">
                        <h2 class="stats-title">Inventory & User Management</h2>
                        <div class="stats-flex">
                            <!-- Total Users -->
                            <div class="stat-item">
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-value"><?= $stats['total_users'] ?></div>
                                <div class="stat-label">Users</div>
                                <a href="users.php" class="stat-link">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>

                            <!-- Total Customers -->
                            <div class="stat-item">
                                <div class="stat-icon text-secondary">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="stat-value"><?= $stats['total_customers'] ?></div>
                                <div class="stat-label">Customers</div>
                                <a href="customer_list.php" class="stat-link">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>
                            
                            <!-- Total Products -->
                            <div class="stat-item">
                                <div class="stat-icon text-info">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="stat-value"><?= $stats['total_products'] ?></div>
                                <div class="stat-label">Products</div>
                                <a href="product_list.php" class="stat-link">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Include JavaScript dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
        // Initialize date pickers with a more minimal configuration
        flatpickr("#date_from", {
            dateFormat: "Y-m-d",
            allowInput: true,
            static: true
        });
        
        flatpickr("#date_to", {
            dateFormat: "Y-m-d",
            allowInput: true,
            static: true
        });
        
        // Print functionality
        document.getElementById('printButton') && document.getElementById('printButton').addEventListener('click', function() {
            window.print();
        });
        
        // Reset button functionality
        document.getElementById('resetButton').addEventListener('click', function() {
            // Clear the input fields
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            
            // Submit the form to refresh the page with cleared filters
            document.getElementById('orderFilterForm').submit();
        });
    </script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>