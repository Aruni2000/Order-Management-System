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

// Fetch inquiries data
$sql = "SELECT * FROM user_form_data ORDER BY created_at DESC";
$result = $conn->query($sql);

// Count total inquiries
$countQuery = "SELECT COUNT(*) as total FROM user_form_data";
$countResult = $conn->query($countQuery);
$totalInquiries = $countResult->fetch_assoc()['total'];
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
    <title> All Inquiry</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                <div class="alert-container"></div>
                <div class="container-fluid px-4">
                    
                    <h1 class="mt-3">All Inquiries</h1>
                    <ol class="breadcrumb mb-4">
                        <!-- Total Inquiry Count -->
                        <div class="alert alert-info">
                            <strong>Total Inquiries:</strong> <?= $totalInquiries ?>
                        </div>
                    </ol>
                    <div class="table-container">
                        <div class="spinner-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Message</th>
                                        <th>Company</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <?php include 'inquiry_rows.php'; ?>
                                        <!-- View Modal -->
                                        <?php include 'modal.php'; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Required scripts -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>