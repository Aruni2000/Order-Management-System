<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: signin.php");
    exit();
}

// Include the database connection file
include 'db_connection.php';
include 'functions.php';

// Handle user status update via AJAX if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $response = ['success' => false, 'message' => 'Unknown error'];

    if (isset($_POST['user_id']) && isset($_POST['new_status'])) {
        $user_id = $conn->real_escape_string($_POST['user_id']);
        $new_status = $conn->real_escape_string($_POST['new_status']);
        $current_user_id = $_SESSION['user_id']; // Get current user ID from session

        // Validate status
        if (in_array($new_status, ['active', 'inactive'])) {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Get the user's name before updating
                $user_query = "SELECT name FROM users WHERE id = '$user_id'";
                $user_result = $conn->query($user_query);
                $user_name = "";

                if ($user_result && $user_result->num_rows > 0) {
                    $user_name = $user_result->fetch_assoc()['name'];
                }

                // Update user status
                $update_sql = "UPDATE users SET status = '$new_status' WHERE id = '$user_id'";
                $conn->query($update_sql);

                if ($conn->affected_rows > 0) {
                    // Log the action in user_logs table
                    $action_type = ($new_status === 'active') ? 'activate_user' : 'deactivate_user';
                    $details = "User ID #$user_id ($user_name) was " .
                        ($new_status === 'active' ? 'activated' : 'deactivated') .
                        " by user ID #$current_user_id";

                    $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) 
                                VALUES ('$current_user_id', '$action_type', '0', '$details')";

                    $conn->query($log_sql);

                    if ($conn->affected_rows > 0) {
                        // Commit transaction if all operations were successful
                        $conn->commit();

                        $response = [
                            'success' => true,
                            'message' => "User status updated to $new_status and logged successfully",
                            'new_status' => $new_status
                        ];
                    } else {
                        // Rollback if logging failed
                        $conn->rollback();
                        $response = [
                            'success' => false,
                            'message' => "Error logging status change: " . $conn->error
                        ];
                    }
                } else {
                    // Rollback if update failed
                    $conn->rollback();
                    $response = [
                        'success' => false,
                        'message' => "Error updating status: " . $conn->error
                    ];
                }
            } catch (Exception $e) {
                // Rollback on any exception
                $conn->rollback();
                $response = [
                    'success' => false,
                    'message' => "Transaction failed: " . $e->getMessage()
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => "Invalid status value"
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => "Missing required parameters"
        ];
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get current user's role_id from session
$current_user_role = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;

// Check if user is admin (role_id = 1)
$is_admin = ($current_user_role == 1);

// Modify SQL based on user role
if ($is_admin) {
    // Admin can see all users
    $sql = "SELECT u.*, r.name AS role_name, 
            u.commission_per_parcel, u.percentage_drawdown, u.commission_type
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id ASC";
} else {
    // Non-admin users can only see non-admin users
    $sql = "SELECT u.*, r.name AS role_name,
            u.commission_per_parcel, u.percentage_drawdown, u.commission_type 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.role_id != 1
            ORDER BY u.id ASC";
}

$result = $conn->query($sql);

// Count total users (adjusted based on user role)
if ($is_admin) {
    $countQuery = "SELECT COUNT(*) as total FROM users";
} else {
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE role_id != 1";
}
$countResult = $conn->query($countQuery);
$totalusers = $countResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>All Users</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <!-- SweetAlert CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Compact action buttons */
        .btn-group-compact {
            display: flex;
            flex-direction: row;
            gap: 0.25rem;
        }

        .btn-group-compact .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }

        /* Custom modal styles */
        .modal-custom-body {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .modal-custom-body p {
            margin-bottom: 0.25rem;
        }

        /* Success and Error Message Styles */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        /* SweetAlert customizations */
        .swal2-popup {
            font-size: 0.9rem !important;
        }

        /* Commission info styles */
        .commission-info {
            font-size: 0.85rem;
            color: #495057;
            margin-top: 3px;
        }

        .commission-badge {
            font-size: 0.75rem;
            padding: 0.2rem 0.4rem;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>

    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="alert-container" id="alertContainer"></div>
                <div class="container-fluid px-3">
                    <h1 class="mt-3">Users</h1>
                    <ol class="breadcrumb mb-4">
                        <!-- Total User Count -->
                        <div class="alert alert-info">
                            <strong>Total Users:</strong> <?= $totalusers ?>
                            <?php if (!$is_admin): ?>
                                <small>(Admin users not included)</small>
                            <?php endif; ?>
                        </div>
                    </ol>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>User ID<br><small class="text-muted">Created At</small></th>
                                        <th>Name<br><small class="text-muted">Role (ID)</small></th>
                                        <th>Contact Info</th>
                                        <th>Mobile</th>
                                        <th>NIC</th>
                                        <th>Status</th>
                                        <th>Commission</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr id="user-row-<?= $row['id'] ?>">
                                            <td>
                                                <?= htmlspecialchars($row['id']) ?>
                                                <br>
                                                <small
                                                    class="text-muted"><?= htmlspecialchars($row['created_at']) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['name']) ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($row['role_name']) ?>
                                                    (<?= htmlspecialchars($row['role_id']) ?>)</small>
                                            </td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= isset($row['mobile']) ? htmlspecialchars($row['mobile']) : 'N/A' ?></td>
                                            <td><?= isset($row['nic']) ? htmlspecialchars($row['nic']) : 'N/A' ?></td>
                                            <td>
                                                <span
                                                    class="user-status-badge badge <?= $row['status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                // Fixed commission display code
                                                $commission_type = isset($row['commission_type']) && !empty($row['commission_type']) ? $row['commission_type'] : 'none';
                                                $commission_badge_class = '';
                                                $commission_info = '';

                                                // Ensure we have valid numeric values for commission
                                                $commission_per_parcel = isset($row['commission_per_parcel']) && is_numeric($row['commission_per_parcel'])
                                                    ? number_format((float) $row['commission_per_parcel'], 2)
                                                    : '0.00';

                                                $percentage_drawdown = isset($row['percentage_drawdown']) && is_numeric($row['percentage_drawdown'])
                                                    ? number_format((float) $row['percentage_drawdown'], 2)
                                                    : '0.00';

                                                // Determine the actual commission type based on values
                                                if ($commission_per_parcel > 0 && $percentage_drawdown > 0) {
                                                    $commission_type = 'both';
                                                } elseif ($commission_per_parcel > 0) {
                                                    $commission_type = 'fixed';
                                                } elseif ($percentage_drawdown > 0) {
                                                    $commission_type = 'percentage';
                                                } else {
                                                    $commission_type = 'none';
                                                }

                                                switch ($commission_type) {
                                                    case 'fixed':
                                                        $commission_badge_class = 'bg-info';
                                                        $commission_info = "Rs. {$commission_per_parcel} per parcel";
                                                        break;
                                                    case 'percentage':
                                                        $commission_badge_class = 'bg-warning text-dark';
                                                        $commission_info = "{$percentage_drawdown}% of total";
                                                        break;
                                                    case 'both':
                                                        $commission_badge_class = 'bg-primary';
                                                        $commission_info = "Rs. {$commission_per_parcel} + {$percentage_drawdown}%";
                                                        break;
                                                    default:
                                                        $commission_badge_class = 'bg-light text-dark';
                                                        $commission_info = "No Commission";
                                                }
                                                ?>
                                                <span class="badge commission-badge <?= $commission_badge_class ?>">
                                                    <?= htmlspecialchars(ucfirst($commission_type)) ?>
                                                </span>
                                                <div class="commission-info">
                                                    <?= htmlspecialchars($commission_info) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group-compact">
                                                <a href="edit_user.php?id=<?= htmlspecialchars($row['id']) ?>&name=<?= urlencode(htmlspecialchars($row['name'])) ?>&email=<?= urlencode(htmlspecialchars($row['email'])) ?>&status=<?= htmlspecialchars($row['status']) ?>&role=<?= urlencode(htmlspecialchars($row['role_name'])) ?>&commission_type=<?= urlencode(htmlspecialchars($commission_type)) ?>&commission_per_parcel=<?= isset($row['commission_per_parcel']) ? urlencode(htmlspecialchars($row['commission_per_parcel'])) : '0.00' ?>&percentage_drawdown=<?= isset($row['percentage_drawdown']) ? urlencode(htmlspecialchars($row['percentage_drawdown'])) : '0.00' ?>" class="btn btn-info btn-sm">Edit</a>
                                                    <button class="btn btn-primary btn-sm view-user-btn"
                                                        data-user-id="<?= $row['id'] ?>"
                                                        data-user-name="<?= htmlspecialchars($row['name']) ?>"
                                                        data-user-email="<?= htmlspecialchars($row['email']) ?>"
                                                        data-user-mobile="<?= isset($row['mobile']) ? htmlspecialchars($row['mobile']) : 'N/A' ?>"
                                                        data-user-nic="<?= isset($row['nic']) ? htmlspecialchars($row['nic']) : 'N/A' ?>"
                                                        data-user-status="<?= htmlspecialchars($row['status']) ?>"
                                                        data-user-role="<?= htmlspecialchars($row['role_name']) ?>"
                                                        data-user-role-id="<?= htmlspecialchars($row['role_id']) ?>"
                                                        data-user-created="<?= htmlspecialchars($row['created_at']) ?>"
                                                        data-commission-type="<?= htmlspecialchars($commission_type) ?>"
                                                        data-commission-parcel="<?= isset($row['commission_per_parcel']) ? htmlspecialchars(number_format((float) $row['commission_per_parcel'], 2)) : '0.00' ?>"
                                                        data-commission-percentage="<?= isset($row['percentage_drawdown']) ? htmlspecialchars(number_format((float) $row['percentage_drawdown'], 2)) : '0.00' ?>">
                                                        View
                                                    </button>
                                                    <button
                                                        class="btn btn-<?= $row['status'] == 'active' ? 'danger' : 'success' ?> btn-sm toggle-status-btn"
                                                        data-user-id="<?= $row['id'] ?>"
                                                        data-current-status="<?= $row['status'] ?>"
                                                        data-user-name="<?= htmlspecialchars($row['name']) ?>">
                                                        <?= $row['status'] == 'active' ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-custom-body" id="viewUserModalBody">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Function to show alert messages
            function showAlert(type, message) {
                const alertContainer = document.getElementById('alertContainer');
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
                alertContainer.appendChild(alertDiv);

                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                    alert.close();
                }, 5000);
            }

            // Toast SweetAlert notification
            function showToast(icon, title) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                Toast.fire({
                    icon: icon,
                    title: title
                });
            }

            // View User Modal Handling
            const viewButtons = document.querySelectorAll('.view-user-btn');
            const viewUserModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
            const viewUserModalBody = document.getElementById('viewUserModalBody');

            viewButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const userData = {
                        name: this.getAttribute('data-user-name'),
                        email: this.getAttribute('data-user-email'),
                        mobile: this.getAttribute('data-user-mobile'),
                        nic: this.getAttribute('data-user-nic'),
                        status: this.getAttribute('data-user-status'),
                        role: this.getAttribute('data-user-role'),
                        roleId: this.getAttribute('data-user-role-id'),
                        created: this.getAttribute('data-user-created'),
                        commissionType: this.getAttribute('data-commission-type'),
                        commissionParcel: this.getAttribute('data-commission-parcel'),
                        commissionPercentage: this.getAttribute('data-commission-percentage')
                    };

                    // Format commission display based on type
                    let commissionDisplay = "No Commission";

                    if (userData.commissionType === 'fixed') {
                        commissionDisplay = `Fixed: Rs. ${userData.commissionParcel} per parcel`;
                    } else if (userData.commissionType === 'percentage') {
                        commissionDisplay = `Percentage: ${userData.commissionPercentage}% of total amount`;
                    } else if (userData.commissionType === 'both') {
                        commissionDisplay = `Combined: Rs. ${userData.commissionParcel} per parcel + ${userData.commissionPercentage}% of total`;
                    }

                    viewUserModalBody.innerHTML = `
                    <p><strong>Name:</strong> ${userData.name}</p>
                    <p><strong>Email:</strong> ${userData.email}</p>
                    <p><strong>Mobile:</strong> ${userData.mobile}</p>
                    <p><strong>NIC:</strong> ${userData.nic}</p>
                    <p><strong>Status:</strong> ${userData.status}</p>
                    <p><strong>Role:</strong> ${userData.role} (ID: ${userData.roleId})</p>
                    <p><strong>Commission:</strong> ${commissionDisplay}</p>
                    <p><strong>Created At:</strong> ${userData.created}</p>
                `;

                    viewUserModal.show();
                });
            });

            // Status Toggle Handling
            const toggleStatusButtons = document.querySelectorAll('.toggle-status-btn');

            toggleStatusButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const userId = this.getAttribute('data-user-id');
                    const currentStatus = this.getAttribute('data-current-status');
                    const userName = this.getAttribute('data-user-name');
                    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                    const actionText = currentStatus === 'active' ? 'deactivate' : 'activate';
                    const actionColor = currentStatus === 'active' ? '#d33' : '#28a745';

                    // SweetAlert confirmation before status change
                    Swal.fire({
                        title: `Are you sure?`,
                        html: `You are about to <strong>${actionText}</strong> user: <br><strong>${userName}</strong>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: actionColor,
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: `Yes, ${actionText} user!`,
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state
                            Swal.fire({
                                title: 'Processing...',
                                html: `Updating user status to ${newStatus}`,
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // AJAX call to update status
                            fetch('users.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=update_status&user_id=${userId}&new_status=${newStatus}`
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Update button and badge
                                        const userRow = document.getElementById(`user-row-${userId}`);
                                        const statusBadge = userRow.querySelector('.user-status-badge');
                                        const toggleButton = userRow.querySelector('.toggle-status-btn');

                                        if (newStatus === 'active') {
                                            statusBadge.classList.remove('bg-secondary');
                                            statusBadge.classList.add('bg-success');
                                            toggleButton.classList.remove('btn-success');
                                            toggleButton.classList.add('btn-danger');
                                            toggleButton.textContent = 'Deactivate';
                                        } else {
                                            statusBadge.classList.remove('bg-success');
                                            statusBadge.classList.add('bg-secondary');
                                            toggleButton.classList.remove('btn-danger');
                                            toggleButton.classList.add('btn-success');
                                            toggleButton.textContent = 'Activate';
                                        }

                                        statusBadge.textContent = newStatus;
                                        toggleButton.setAttribute('data-current-status', newStatus);

                                        // Show success message
                                        Swal.fire({
                                            title: 'Success!',
                                            text: `User ${userName} has been ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully.`,
                                            icon: 'success',
                                            confirmButtonColor: '#4CAF50'
                                        });
                                    } else {
                                        // Show error message
                                        Swal.fire({
                                            title: 'Error!',
                                            text: data.message || 'Failed to update user status',
                                            icon: 'error',
                                            confirmButtonColor: '#d33'
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An error occurred while updating user status',
                                        icon: 'error',
                                        confirmButtonColor: '#d33'
                                    });
                                });
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>