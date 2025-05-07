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


// Fetch rejected inquiries
$sql = "SELECT * FROM user_form_data WHERE status = 'rejected' ORDER BY created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
// Count total rejected inquiries
$count_query = "SELECT COUNT(*) AS total_rejected FROM user_form_data WHERE status = 'rejected'";
$count_result = $conn->query($count_query);
$total_rejected = 0;

if ($count_result) {
    $row = $count_result->fetch_assoc();
    $total_rejected = $row['total_rejected'];
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Rejected Inquiry  </title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="css/styles.css" rel="stylesheet" />

</head>


<body class="sb-nav-fixed">
<?php include 'navbar.php'; ?>

<div id="layoutSidenav">
    <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
        <main>

        <div class="alert-container"></div>
    <div class="container-fluid px-4">
        <h1 class="mt-3">Rejected Inquiries</h1>
        <ol class="breadcrumb mb-4">
        <div class="alert alert-danger">
        <strong>Total Rejected Inquiries:</strong> <?= $total_rejected ?>
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
                            <tr data-inquiry-id="<?= htmlspecialchars($row['id']) ?>">
                                <td>
                                    <?= htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) ?>
                                    <span class="status-badge badge <?= $row['status'] === 'approved' ? 'bg-success' : 
                                        ($row['status'] === 'rejected' ? 'bg-danger' : 'bg-warning') ?>">
                                        <?= htmlspecialchars($row['status'] ?: 'Pending') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td class="message-cell">
                                    <div class="message-content" data-bs-toggle="tooltip" data-bs-placement="top" 
                                         title="<?= htmlspecialchars($row['mesage']) ?>">
                                        <?= htmlspecialchars($row['mesage']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['company']) ?></td>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#viewModal<?= $row['id'] ?>">
                                View
                            </button>
                                        <!--<button type="button" class="btn btn-success btn-sm action-button" -->
                                        <!--        data-action="approved" -->
                                        <!--        data-inquiry-id="<?= htmlspecialchars($row['id']) ?>"-->
                                        <!--        <?= $row['status'] === 'approved' || $row['status'] === 'rejected' ? 'disabled' : '' ?>>-->
                                        <!--    Approve-->
                                        <!--</button>-->
                                        <!--<button type="button" class="btn btn-danger btn-sm action-button" -->
                                        <!--        data-action="rejected" -->
                                        <!--        data-inquiry-id="<?= htmlspecialchars($row['id']) ?>"-->
                                        <!--        <?= $row['status'] === 'approved' || $row['status'] === 'rejected' ? 'disabled' : '' ?>>-->
                                        <!--    Reject-->
                                        <!--</button>-->
                                    </div>
                                </td>
                            </tr>
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
    
    <script>
    $(document).ready(function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Show alert function
        function showAlert(message, type = 'success') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            $('.alert-container').append(alertHtml);
            
            setTimeout(() => {
                $('.alert').alert('close');
            }, 3000);
        }

        // Handle status update
        $('.action-button').on('click', function() {
            const button = $(this);
            const row = button.closest('tr');
            const inquiryId = button.data('inquiry-id');
            const action = button.data('action');
            
            // Disable both buttons in the row
            row.find('.action-button').prop('disabled', true);
            
            // Show spinner overlay
            $('.spinner-overlay').css('display', 'flex');
            
            $.ajax({
                url: 'update_status.php',
                type: 'POST',
                data: {
                    id: inquiryId,
                    status: action
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update status badge
                        const statusBadge = row.find('.status-badge');
                        statusBadge.removeClass('bg-warning bg-success bg-danger')
                            .addClass(action === 'approved' ? 'bg-success' : 'bg-danger')
                            .text(action);
                        
                        showAlert(`Inquiry successfully ${action}!`);
                    } else {
                        // Re-enable buttons on error
                        row.find('.action-button').prop('disabled', false);
                        showAlert(response.error || 'Failed to update status.', 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    // Re-enable buttons on error
                    row.find('.action-button').prop('disabled', false);
                    showAlert('An error occurred while processing your request.', 'danger');
                },
                complete: function() {
                    // Hide spinner overlay
                    $('.spinner-overlay').hide();
                }
            });
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>