<?php
// Start session at the very beginning
session_start();

// Include the database connection file
include 'db_connection.php';
include 'functions.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: signin.php");
    exit();
}

// Check for success message
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Clear the messages from the session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Fetch customers
$sql = "SELECT * FROM customers ORDER BY customer_id ASC";
$result = $conn->query($sql);

// Count total Customers
$countQuery = "SELECT COUNT(*) as total FROM customers";
$countResult = $conn->query($countQuery);
$totalcustomers = $countResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Customers List</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <!-- SweetAlert CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .btn-group-compact {
            display: flex;
            flex-direction: row;
            gap: 0.25rem;
        }
        .btn-group-compact .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
        /* SweetAlert customizations */
        .swal2-popup {
            font-size: 0.9rem !important;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <!-- Alert Container for Dynamic and Session Messages -->
                <div class="alert-container" id="alertContainer">
                    <?php 
                    // Display session success message
                    if ($success_message) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
                             htmlspecialchars($success_message) . 
                             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' . 
                             '</div>';
                    }

                    // Display session error message
                    if ($error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . 
                             htmlspecialchars($error_message) . 
                             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' . 
                             '</div>';
                    }
                    ?>
                </div>

                <div class="container-fluid px-4">
                    <h1 class="mt-3">Customers</h1>
                    <ol class="breadcrumb mb-4">
                        <div class="alert alert-info">
                            <strong>Total Customers:</strong> <?= $totalcustomers ?>
                        </div>
                    </ol>

                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Customer ID<br><small class="text-muted">Created At</small></th>
                                        <th>Contact Info</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr id="customer-row-<?= $row['customer_id'] ?>">
                                            <td>
                                                <?= htmlspecialchars($row['customer_id']) ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($row['created_at']) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['name']) ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($row['phone']) ?></td>
                                            <td><?= htmlspecialchars($row['address']) ?></td>
                                            <td>
                                                <span class="customer-status-badge badge <?= $row['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group-compact">
                                                    <a href="edit_customer.php?id=<?= htmlspecialchars($row['customer_id']) ?>" class="btn btn-info btn-sm">Edit</a>
                                                    <button class="btn btn-primary btn-sm view-customer-btn" 
                                                            data-customer-id="<?= $row['customer_id'] ?>" 
                                                            data-customer-name="<?= htmlspecialchars($row['name']) ?>"
                                                            data-customer-email="<?= htmlspecialchars($row['email']) ?>"
                                                            data-customer-phone="<?= htmlspecialchars($row['phone']) ?>"
                                                            data-customer-address="<?= htmlspecialchars($row['address']) ?>"
                                                            data-customer-status="<?= htmlspecialchars($row['status']) ?>"
                                                            data-customer-created="<?= htmlspecialchars($row['created_at']) ?>">
                                                        View
                                                    </button>
                                                    <button class="btn btn-<?= $row['status'] == 'Active' ? 'danger' : 'success' ?> btn-sm toggle-status-btn" 
                                                            data-customer-id="<?= $row['customer_id'] ?>"
                                                            data-current-status="<?= $row['status'] ?>"
                                                            data-customer-name="<?= htmlspecialchars($row['name']) ?>">
                                                        <?= $row['status'] == 'Active' ? 'Deactivate' : 'Activate' ?>
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

    <!-- View Customer Modal -->
    <div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-labelledby="viewCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCustomerModalLabel">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewCustomerModalBody">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to show alert messages
        function showAlert(message, type) {
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
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                bsAlert.close();
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

        // View Customer Modal Handling
        const viewButtons = document.querySelectorAll('.view-customer-btn');
        const viewCustomerModal = new bootstrap.Modal(document.getElementById('viewCustomerModal'));
        const viewCustomerModalBody = document.getElementById('viewCustomerModalBody');

        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const customerData = {
                    id: this.getAttribute('data-customer-id'),
                    name: this.getAttribute('data-customer-name'),
                    email: this.getAttribute('data-customer-email'),
                    phone: this.getAttribute('data-customer-phone'),
                    address: this.getAttribute('data-customer-address'),
                    status: this.getAttribute('data-customer-status'),
                    created: this.getAttribute('data-customer-created')
                };

                viewCustomerModalBody.innerHTML = `
                    <p><strong>Customer ID:</strong> ${customerData.id}</p>
                    <p><strong>Name:</strong> ${customerData.name}</p>
                    <p><strong>Email:</strong> ${customerData.email}</p>
                    <p><strong>Phone:</strong> ${customerData.phone}</p>
                    <p><strong>Address:</strong> ${customerData.address}</p>
                    <p><strong>Status:</strong> ${customerData.status}</p>
                    <p><strong>Created At:</strong> ${customerData.created}</p>
                `;

                viewCustomerModal.show();
            });
        });

        // Status Toggle Button Handling with SweetAlert
        const toggleStatusButtons = document.querySelectorAll('.toggle-status-btn');

        toggleStatusButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                const currentStatus = this.getAttribute('data-current-status');
                const customerName = this.getAttribute('data-customer-name');
                const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
                const actionText = currentStatus === 'Active' ? 'deactivate' : 'activate';
                const actionColor = currentStatus === 'Active' ? '#d33' : '#28a745';
                
                // SweetAlert confirmation before status change
                Swal.fire({
                    title: `Are you sure?`,
                    html: `You are about to <strong>${actionText}</strong> customer: <br><strong>${customerName}</strong>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: actionColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${actionText} customer!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            html: `Updating customer status to ${newStatus}`,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // AJAX call to update status
                        fetch('toggle_customer_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `customer_id=${customerId}&action=${actionText}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update button and badge
                                const customerRow = document.getElementById(`customer-row-${customerId}`);
                                const statusBadge = customerRow.querySelector('.customer-status-badge');
                                const toggleButton = customerRow.querySelector('.toggle-status-btn');

                                if (data.new_status === 'Active') {
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

                                statusBadge.textContent = data.new_status;
                                toggleButton.setAttribute('data-current-status', data.new_status);

                                // Show success message
                                Swal.fire({
                                    title: 'Success!',
                                    text: `Customer ${customerName} has been ${data.new_status === 'Active' ? 'activated' : 'deactivated'} successfully.`,
                                    icon: 'success',
                                    confirmButtonColor: '#4CAF50'
                                });
                            } else {
                                // Show error message
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message || 'Failed to update customer status',
                                    icon: 'error',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while updating customer status',
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        });
                    }
                });
            });
        });

        // Optional: Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }, 5000);
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>