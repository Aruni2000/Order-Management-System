<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: signin.php");
    exit();
}

// Include the database connection file
include 'db_connection.php';

// Include helper functions
include 'functions.php';

// Process filter parameters if present
$date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) ? $_GET['date_to'] : '';

// Initialize statistics with default values
$stats = [
    'total_users' => 0,
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
        return 0;
    }
    return 0;
}

// Prepare date conditions for SQL query
$date_condition = "";
if (!empty($date_from) && !empty($date_to)) {
    $date_condition = " AND (issue_date BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59')";
} elseif (!empty($date_from)) {
    $date_condition = " AND issue_date >= '$date_from 00:00:00'";
} elseif (!empty($date_to)) {
    $date_condition = " AND issue_date <= '$date_to 23:59:59'";
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
    // Base query for total orders
    $total_orders_query = "SELECT COUNT(*) as count FROM order_header WHERE 1=1";
    
    // Apply date filter if set
    if (!empty($date_condition)) {
        $total_orders_query .= $date_condition;
    }
    
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
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="dark" data-toggled="close">

<head>
    <?php include('header.php'); ?>
    
    <title>Admin Dashboard</title>
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --secondary: #6b7280;
            --light-bg: #f9fafb;
            --border-color: #e5e7eb;
            --dark-text: #1f2937;
            --light-text: #6b7280;
        }
        
        body {
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .dashboard-container {
            padding: 1.5rem;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }
        
        .filter-bar {
            background-color: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
            min-width: 160px;
        }
        
        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--light-text);
            margin-bottom: 0.25rem;
        }
        
        .form-control {
            width: 180%;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: border-color 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }
        
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        
        .btn-secondary {
            background-color: #f3f4f6;
            color: var(--light-text);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .stat-card {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .card-icon {
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            color: white;
            font-size: 0.875rem;
        }
        
        .icon-primary { background-color: var(--primary); }
        .icon-success { background-color: var(--success); }
        .icon-warning { background-color: var(--warning); }
        .icon-danger { background-color: var(--danger); }
        .icon-info { background-color: var(--info); }
        .icon-secondary { background-color: var(--secondary); }
        
        .card-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-text);
            line-height: 1;
        }
        
        .card-label {
            font-size: 0.75rem;
            color: var(--light-text);
            margin-top: 0.25rem;
        }
        
        .card-footer {
            margin-top: auto;
            padding-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        
        .status-indicator {
            display: inline-block;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            margin-right: 0.375rem;
        }
        
        .status-complete { background-color: var(--success); }
        .status-pending { background-color: var(--warning); }
        .status-cancel { background-color: var(--danger); }
        .status-dispatch { background-color: var(--info); }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .card-value {
                font-size: 1.25rem;
            }
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                background-color: white;
            }
            
            .stat-card {
                box-shadow: none;
                border: 1px solid var(--border-color);
            }
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>

    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        
        <div id="layoutSidenav_content">
            <main>
                <div class="dashboard-container">
                    <div class="page-header">
                        <h1 class="page-title">Dashboard</h1>
                    </div>
                    
                    <!-- Filter Bar -->
                    <div class="filter-bar no-print">
                        <form method="GET" action="" id="orderFilterForm" class="filter-form">
                            <div class="form-group">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="text" name="date_from" id="date_from" class="form-control" 
                                       value="<?= htmlspecialchars($date_from) ?>" placeholder="Select date">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="text" name="date_to" id="date_to" class="form-control" 
                                       value="<?= htmlspecialchars($date_to) ?>" placeholder="Select date">
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <button type="button" class="btn btn-secondary" id="resetButton">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Order Management Section -->
                    <h2 class="section-title">Order Management</h2>
                    <div class="stats-grid">
                        <!-- Total Orders -->
                        <a href="order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-card">
                            <div class="card-header">
                                <div class="card-icon icon-info">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                            </div>
                            <div class="card-value"><?= $stats['total_orders'] ?></div>
                            <div class="card-label">Total Orders</div>
                            <div class="card-footer">
                                View <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <!-- Complete Orders -->
                        <a href="complete_order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-card">
                            <div class="card-header">
                                <div class="card-icon icon-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="card-value"><?= $stats['complete_orders'] ?></div>
                            <div class="card-label">
                                <span class="status-indicator status-complete"></span>Complete
                            </div>
                            <div class="card-footer">
                                View <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <!-- Pending Orders -->
                        <a href="pending_order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-card">
                            <div class="card-header">
                                <div class="card-icon icon-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="card-value"><?= $stats['pending_orders'] ?></div>
                            <div class="card-label">
                                <span class="status-indicator status-pending"></span>Pending
                            </div>
                            <div class="card-footer">
                                View <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <!-- Dispatch Orders -->
                        <a href="dispatch_order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-card">
                            <div class="card-header">
                                <div class="card-icon icon-info">
                                    <i class="fas fa-truck"></i>
                                </div>
                            </div>
                            <div class="card-value"><?= $stats['dispatch_orders'] ?></div>
                            <div class="card-label">
                                <span class="status-indicator status-dispatch"></span>Dispatch
                            </div>
                            <div class="card-footer">
                                View <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <!-- Canceled Orders -->
                        <a href="cancel_order_list.php<?= !empty($date_from) || !empty($date_to) ? '?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '' ?>" class="stat-card">
                            <div class="card-header">
                                <div class="card-icon icon-danger">
                                    <i class="fas fa-ban"></i>
                                </div>
                            </div>
                            <div class="card-value"><?= $stats['cancel_orders'] ?></div>
                            <div class="card-label">
                                <span class="status-indicator status-cancel"></span>Canceled
                            </div>
                            <div class="card-footer">
                                View <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                    
                    <!-- Inventory & User Management Section -->
                    <h2 class="section-title">Inventory & User Management</h2>
                    <div class="stats-grid">
                        <!-- Total Users -->
                        <a href="users.php" class="stat-card">
                            <div class="card-header">
                                <div class="card-icon icon-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="card-value"><?= $stats['total_users'] ?></div>
                            <div class="card-label">Users</div>
                            <div class="card-footer">
                                View <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <!-- Total Customers -->
                        <a href="customer_list.php" class="stat-card">
                            <div class="card-header">
                                <div class="card-icon icon-secondary">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                            <div class="card-value"><?= $stats['total_customers'] ?></div>
                            <div class="card-label">Customers</div>
                            <div class="card-footer">
                                View <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <!-- Total Products -->
                        <a href="product_list.php" class="stat-card">
                            <div class="card-header">
                                <div class="card-icon icon-info">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                            <div class="card-value"><?= $stats['total_products'] ?></div>
                            <div class="card-label">Products</div>
                            <div class="card-footer">
                                View <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
        // Initialize date pickers
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

<?php $conn->close(); ?>