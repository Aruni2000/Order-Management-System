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

// Build basic SQL query with the join already included
$countSql = "SELECT COUNT(*) as total FROM user_logs LEFT JOIN users ON user_logs.user_id = users.id";
$sql = "SELECT user_logs.*, users.name as user_name FROM user_logs 
        LEFT JOIN users ON user_logs.user_id = users.id";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " WHERE user_logs.id LIKE '%$searchTerm%' OR 
                        user_logs.user_id LIKE '%$searchTerm%' OR 
                        users.name LIKE '%$searchTerm%' OR
                        user_logs.action_type LIKE '%$searchTerm%' OR 
                        user_logs.inquiry_id LIKE '%$searchTerm%' OR 
                        user_logs.details LIKE '%$searchTerm%'";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY user_logs.created_at DESC LIMIT $limit OFFSET $offset";

// Execute the queries
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);0

$result = $conn->query($sql);

// Enhanced function to format details text with collapsible changes
function formatDetailsText($details, $userId, $userName, $actionType) {
    // Replace user ID with user name and ID
    $userInfo = !empty($userName) ? "$userName($userId)" : "ID #$userId";
    
    // For edit actions, format the changes as bullet points and make them collapsible
    if (strpos($actionType, 'edit_') !== false) {
        // Check if the details mention changes
        if (strpos($details, 'Changes:') !== false) {
            // Split the text at "Changes:" to separate the action from the changes
            $parts = explode('Changes:', $details, 2);
            $actionPart = trim($parts[0]);
            $changesPart = isset($parts[1]) ? trim($parts[1]) : '';
            
            // Replace "by user ID #X" with "by user Name(ID)"
            $actionPart = preg_replace("/by user ID #(\d+)/i", "by user $userInfo", $actionPart);
            
            // Create collapsible changes section with unique ID
            $uniqueId = 'changes_' . uniqid();
            $output = $actionPart . ' ';
            
            // Format the changes as bullet points inside a collapsible div
            if (!empty($changesPart)) {
                // Add show/hide button
                $output .= '<button class="btn btn-sm btn-outline-secondary toggle-changes" data-bs-toggle="collapse" data-bs-target="#' . $uniqueId . '">Show Changes</button>';
                $output .= '<div class="collapse mt-2" id="' . $uniqueId . '">';
                
                // Split changes by semicolon or comma
                $changes = preg_split('/[;,]\s*/', $changesPart);
                
                $changesList = "<ul class='mb-0 ps-3'>";
                foreach ($changes as $change) {
                    $change = trim($change);
                    if (!empty($change)) {
                        $changesList .= "<li>" . htmlspecialchars($change) . "</li>";
                    }
                }
                $changesList .= "</ul>";
                
                $output .= $changesList;
                $output .= '</div>';
                
                return $output;
            }
        }
    }
    
    // For other action types, just replace the user ID with name
    return preg_replace("/by user ID #(\d+)/i", "by user $userInfo", $details);
}

// Function to check if the details text contains multiple changes
function hasMultipleChanges($details) {
    // Count the number of semicolons which separate changes
    return substr_count($details, ';') >= 1;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>User Activity Logs</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <style>
        /* Custom styles for details column */
        .details-cell {
            max-width: 400px; /* Limit width */
        }
        .details-cell ul {
            margin-top: 5px;
        }
        .details-cell li {
            text-align: left;
        }
        .action-type-text {
            min-width: 100px;
            display: inline-block;
        }
        .toggle-changes.collapsed::after {
            content: " ▼";
        }
        .toggle-changes:not(.collapsed)::after {
            content: " ▲";
        }
        .toggle-changes:not(.collapsed) {
            margin-bottom: 5px;
        }
    </style>
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
                        <h4>User Activity Logs</h4>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form method="get" class="d-flex">
                                        <input type="text" name="search" class="form-control me-2"
                                            placeholder="Search logs..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="user_logs.php" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        <?php endif; ?>
                                        <!-- Preserve other GET parameters -->
                                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                        <input type="hidden" name="page" value="1"> <!-- Reset to page 1 when searching -->
                                    </form>
                                </div>
                                <div class="col-md-6 text-end">
                                    <form method="get" class="d-inline">
                                        <!-- Preserve search term when changing limit -->
                                        <?php if (!empty($search)): ?>
                                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                        <?php endif; ?>
                                        <div class="d-inline-block">
                                            <label>Show</label>
                                            <select name="limit" class="form-select d-inline-block w-auto ms-1"
                                                onchange="this.form.submit()">
                                                <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                                <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                                                <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                                <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                                            </select>
                                            <label>entries</label>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!--<h5 class="mb-3">User Activity History</h5>-->
                            <?php if (!empty($search)): ?>
                                <div class="alert alert-info">
                                    Showing search results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                                    (<?php echo $totalRows; ?> results found)
                                </div>
                            <?php endif; ?>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Action ID</th>
                                            <th>User ID and Name</th>
                                            <th>Action Type</th>
                                            <th width="40%">Details</th>
                                            <th>Action Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($row['user_id']); ?> - 
                                                        <?php echo !empty($row['user_name']) ? htmlspecialchars($row['user_name']) : 'N/A'; ?>
                                                    </td>
                                                    <td>
                                                        <span class="action-type-text">
                                                            <?php echo htmlspecialchars($row['action_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="details-cell">
                                                        <?php 
                                                        // Format the details text with collapsible changes
                                                        $formattedDetails = formatDetailsText(
                                                            $row['details'], 
                                                            $row['user_id'], 
                                                            isset($row['user_name']) ? $row['user_name'] : '',
                                                            $row['action_type']
                                                        );
                                                        
                                                        // Output the formatted details (don't use htmlspecialchars to allow HTML from formatting)
                                                        echo $formattedDetails;
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($row['created_at']))); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No user logs found</td>
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
                                            <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
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
                                                    <a class="page-link" href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">1</a>
                                                </li>
                                                <?php if ($startPage > 2): ?>
                                                    <li class="page-item disabled">
                                                        <span class="page-link">...</span>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
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
                                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $totalPages; ?></a>
                                                </li>
                                            <?php endif; ?>

                                            <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Toggle button text when clicked
        $('.toggle-changes').on('click', function() {
            if ($(this).hasClass('collapsed') || !$(this).attr('aria-expanded') || $(this).attr('aria-expanded') === 'false') {
                $(this).text('Hide Changes');
            } else {
                $(this).text('Show Changes');
            }
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