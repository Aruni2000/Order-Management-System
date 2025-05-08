<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: signin.php");
    exit();
}
include 'db_connection.php';
include 'functions.php';

// Fetch necessary data for the form - only need to do this once
$sql = "SELECT id, name, description, lkr_price FROM products WHERE status = 'active' ORDER BY name ASC";
$result = $conn->query($sql);
$customerSql = "SELECT * FROM customers WHERE status = 'active' ORDER BY name ASC";
$customerResult = $conn->query($customerSql);

// Fetch delivery fee from branding table
$deliveryFeeSql = "SELECT delivery_fee FROM branding LIMIT 1";
$deliveryFeeResult = $conn->query($deliveryFeeSql);
$deliveryFee = 0.00;
if ($deliveryFeeResult && $deliveryFeeResult->num_rows > 0) {
    $row = $deliveryFeeResult->fetch_assoc();
    $deliveryFee = floatval($row['delivery_fee']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Create Order</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="alert-container"></div>
                <div class="container-fluid px-4">
                    <br>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Create new Order</h4>
                    </div> 
                                 
                <div class="order-container">
                    <form method="post" action="process_order.php" id="orderForm" target="_blank">

                        <!-- Order Details Section -->
                        <div class="form-section">
                            <h4 class="section-title">Order Details</h4>
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="mb-3 mb-md-0">
                                        <label class="form-label"><strong>Status</strong></label>
                                        <select name="order_status" class="form-select">
                                            <option value="Paid">Paid</option>
                                            <option value="Unpaid" selected>Unpaid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3 mb-md-0">
                                                <label class="form-label"><strong>Order Date</strong></label>
                                                <input type="date" class="form-control" name="order_date"
                                                    value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3 mb-md-0">
                                                <label class="form-label"><strong>Due Date</strong></label>
                                                <input type="date" class="form-control" name="due_date"
                                                    value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                                    required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Hidden currency field - always set to LKR -->
                                <input type="hidden" name="order_currency" id="order_currency" value="lkr">
                            </div>
                        </div>

                        <!-- Customer Information Section -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title m-0"><strong>Customer Information</strong></h5>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            id="select_existing_customer">
                                            <i class="fas fa-users me-1"></i> Select Customer
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="hidden" name="customer_id" id="customer_id" value="">
                                        <div class="mb-3">
                                            <label class="form-label">Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="customer_name"
                                                id="customer_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="customer_email"
                                                id="customer_email">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" name="customer_phone"
                                                id="customer_phone">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" name="customer_address"
                                                id="customer_address">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Products Section -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Products</h5>
                                <div class="table-responsive">
                                    <table class="table" id="order_table">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px;">Action</th>
                                                <th>Product</th>
                                                <th>Description</th>
                                                <th style="width: 150px;">Price</th>
                                                <th style="width: 150px;">Discount</th>
                                                <th style="width: 150px;">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <button type="button"
                                                        class="btn btn-outline-danger btn-sm remove_product w-100">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    <select name="order_product[]" class="form-select product-select">
                                                        <option value="">-- Select Product --</option>
                                                        <?php
                                                        // Reset the pointer for $result
                                                        $result->data_seek(0);
                                                        while ($row = $result->fetch_assoc()): ?>
                                                            <option value="<?= $row['id'] ?>"
                                                                data-lkr-price="<?= $row['lkr_price'] ?>"
                                                                data-description="<?= htmlspecialchars($row['description']) ?>">
                                                                <?= htmlspecialchars($row['name']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="order_product_description[]"
                                                        class="form-control product-description">
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-text currency-symbol">Rs.</span>
                                                        <input type="number" name="order_product_price[]"
                                                            class="form-control price" value="0.00" step="0.01">
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" name="order_product_discount[]"
                                                        class="form-control discount" value="0" min="0" step="1">
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-text currency-symbol">Rs.</span>
                                                        <input type="text" name="order_product_sub[]"
                                                            class="form-control subtotal" value="0.00" readonly>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-start mt-4 flex-wrap">
                                    <button type="button" id="add_product" class="btn btn-outline-success mb-3 mb-md-0">
                                        <i class="fas fa-plus me-1"></i> Add Product
                                    </button>

                                    <div class="totals-section">
                                        <div class="row mb-2">
                                            <div class="col-6 text-end">Subtotal:</div>
                                            <div class="col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text currency-symbol">Rs.</span>
                                                    <input type="text" id="subtotal_amount" name="subtotal"
                                                        class="form-control text-end" value="0.00" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-6 text-end">Discount:</div>
                                            <div class="col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text currency-symbol">Rs.</span>
                                                    <input type="text" id="discount_amount" name="discount"
                                                        class="form-control text-end" value="0.00" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delivery Fee Row - Initially Hidden -->
                                        <div class="row mb-2" id="delivery_fee_row" style="display: none;">
                                            <div class="col-6 text-end">Delivery Fee:</div>
                                            <div class="col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text currency-symbol">Rs.</span>
                                                    <input type="text" id="delivery_fee" name="delivery_fee"
                                                        class="form-control text-end"
                                                        value="<?php echo number_format($deliveryFee, 2); ?>" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6 text-end"><strong>Total:</strong></div>
                                            <div class="col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text currency-symbol">Rs.</span>
                                                    <input type="text" id="total_amount" name="total_amount"
                                                        class="form-control text-end" value="0.00" readonly>
                                                    <input type="hidden" id="lkr_total_amount" name="lkr_price"
                                                        value="0.00">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes & Submit Section -->
                        <div class="card">
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" id="submit_order">
                                        <i class="fas fa-save me-1"></i> Create Order
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
            </div>

            <!-- Customer Selection Modal -->
            <div id="customerModal" class="customer-modal">
                <div class="customer-modal-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="m-0">Select Customer</h5>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="input-group mb-4">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="customerSearch" class="form-control"
                            placeholder="Search for customers...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Reset the pointer for $customerResult
                                $customerResult->data_seek(0);
                                while ($customer = $customerResult->fetch_assoc()): ?>
                                    <tr class="customer-row" data-id="<?= $customer['id'] ?? '' ?>"
                                        data-name="<?= htmlspecialchars($customer['name'] ?? '') ?>"
                                        data-email="<?= htmlspecialchars($customer['email'] ?? '') ?>"
                                        data-phone="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                                        data-address="<?= htmlspecialchars($customer['address'] ?? '') ?>">
                                        <td><?= htmlspecialchars($customer['name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($customer['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($customer['phone'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($customer['address'] ?? '') ?></td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-primary select-customer-btn">Select</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Customer Success Modal -->
            <div class="modal fade" id="customerAddedModal" tabindex="-1" aria-labelledby="customerAddedModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="customerAddedModalLabel">Success</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Customer has been successfully added to the database.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Store the delivery fee value from PHP
            let deliveryFee = <?php echo $deliveryFee; ?>;
            let hasProducts = false; // Flag to track if any products have been selected

            // Email validation function
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Phone number validation function (10 digits)
            function isValidPhoneNumber(phone) {
                const phoneRegex = /^\d{10}$/;
                return phoneRegex.test(phone);
            }

            // Validate customer information
            function validateCustomerInfo() {
                const customerName = $('#customer_name').val().trim();
                const customerEmail = $('#customer_email').val().trim();
                const customerPhone = $('#customer_phone').val().trim();

                // Clear previous error messages
                $('.validation-error').remove();

                let isValid = true;

                // Name validation (required)
                if (customerName === '') {
                    $('#customer_name').after('<div class="text-danger validation-error">Customer name is required</div>');
                    isValid = false;
                }

                // Email validation (optional, but if provided must be valid)
                if (customerEmail !== '' && !isValidEmail(customerEmail)) {
                    $('#customer_email').after('<div class="text-danger validation-error">Invalid email format</div>');
                    isValid = false;
                }

                // Phone validation (optional, but if provided must be 10 digits)
                if (customerPhone !== '' && !isValidPhoneNumber(customerPhone)) {
                    $('#customer_phone').after('<div class="text-danger validation-error">Phone number must be 10 digits</div>');
                    isValid = false;
                }

                return isValid;
            }

            // Function to check if any product is selected and show/hide delivery fee
            function checkForProducts() {
                let productSelected = false;

                $('#order_table tbody tr').each(function () {
                    if ($(this).find('.product-select').val() !== "") {
                        productSelected = true;
                        return false; // Break the loop if any product is found
                    }
                });

                hasProducts = productSelected;

                // Show/hide the delivery fee row based on whether products are selected
                if (productSelected) {
                    $('#delivery_fee_row').show();
                } else {
                    $('#delivery_fee_row').hide();
                }

                // Always update totals after checking for products to ensure correct calculation
                updateTotals();

                return productSelected;
            }

            // Function to update product price based on currency
            function updateProductPrice(row) {
                var selectedOption = row.find('.product-select option:selected');
                if (selectedOption.val() === "") return;

                var priceField = row.find('.price');
                var description = selectedOption.data('description') || '';
                var price = parseFloat(selectedOption.data('lkr-price') || 0);

                priceField.val(isNaN(price) ? '0.00' : price.toFixed(2));
                row.find('.product-description').val(description);

                // First check if any products are selected (this will update the hasProducts flag)
                checkForProducts();
                // Then update the row total
                updateRowTotal(row);
            }

            // Updated Row Total Calculation Function
            function updateRowTotal(row) {
                let price = parseFloat(row.find('.price').val()) || 0;
                let discount = parseFloat(row.find('.discount').val()) || 0;

                // Ensure discount doesn't exceed price
                if (discount > price) {
                    discount = price;
                    row.find('.discount').val(discount);
                }

                let subtotal = price - discount;
                row.find('.subtotal').val(subtotal.toFixed(2));
                updateTotals();
            }

            // Updated Totals Calculation Function with fix for delivery fee
            function updateTotals() {
                let subtotal = 0;
                let totalDiscount = 0;

                $('#order_table tbody tr').each(function () {
                    let rowPrice = parseFloat($(this).find('.price').val()) || 0;
                    let rowDiscount = parseFloat($(this).find('.discount').val()) || 0;

                    // Ensure discount doesn't exceed price
                    if (rowDiscount > rowPrice) {
                        rowDiscount = rowPrice;
                        $(this).find('.discount').val(rowDiscount);
                    }

                    let rowSubtotal = rowPrice - rowDiscount;
                    $(this).find('.subtotal').val(rowSubtotal.toFixed(2));

                    subtotal += rowPrice;
                    totalDiscount += rowDiscount;
                });

                $('#subtotal_amount').val(subtotal.toFixed(2));
                $('#discount_amount').val(totalDiscount.toFixed(2));

                // Always check for products before calculating total
                let hasAnyProducts = false;
                $('#order_table tbody tr').each(function () {
                    if ($(this).find('.product-select').val() !== "") {
                        hasAnyProducts = true;
                        return false; // Break the loop
                    }
                });

                // Calculate total including delivery fee if products are selected
                let total = subtotal - totalDiscount;

                // Add delivery fee if any products are selected
                if (hasAnyProducts) {
                    total += deliveryFee;
                    $('#delivery_fee_row').show();
                } else {
                    $('#delivery_fee_row').hide();
                }

                $('#total_amount').val(total.toFixed(2));
                $('#lkr_total_amount').val(total.toFixed(2));
            }

            // Update status and pay_date when payment status changes
            $("select[name='pay_status']").change(function () {
                if ($(this).val() === "paid") {
                    $('#order_status').val("done");
                    $('#pay_date').val(new Date().toISOString().split('T')[0]); // Current date in YYYY-MM-DD format
                } else {
                    $('#order_status').val("pending");
                    $('#pay_date').val("");
                }
            });

            // Set initial values based on default selection
            $("select[name='pay_status']").trigger('change');

            // Customer modal functionality
            var customerModal = document.getElementById("customerModal");
            $("#select_existing_customer").click(function () {
                customerModal.style.display = "block";
            });
            $(".close-modal").click(function () {
                customerModal.style.display = "none";
            });
            $(window).click(function (event) {
                if (event.target == customerModal) {
                    customerModal.style.display = "none";
                }
            });

            // Customer search functionality
            $("#customerSearch").on("keyup", function () {
                var value = $(this).val().toLowerCase();
                $(".customer-row").filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Select customer functionality
            $(".select-customer-btn").click(function () {
                var row = $(this).closest('tr');
                $('#customer_id').val(row.data('id'));
                $('#customer_name').val(row.data('name'));
                $('#customer_email').val(row.data('email'));
                $('#customer_phone').val(row.data('phone'));
                $('#customer_address').val(row.data('address'));
                customerModal.style.display = "none";
            });

            // Real-time validation for email
            $('#customer_email').on('input', function () {
                $('.validation-error').remove();
                const email = $(this).val().trim();
                if (email !== '' && !isValidEmail(email)) {
                    $(this).after('<div class="text-danger validation-error">Invalid email format</div>');
                }
            });

            // Real-time validation for phone
            $('#customer_phone').on('input', function () {
                $('.validation-error').remove();
                const phone = $(this).val().trim();
                if (phone !== '' && !isValidPhoneNumber(phone)) {
                    $(this).after('<div class="text-danger validation-error">Phone number must be 10 digits</div>');
                }
            });

            // Form submission validation
            $('#orderForm').on('submit', function (e) {
                // Validate customer information
                if (!validateCustomerInfo()) {
                    e.preventDefault();
                    return false;
                }

                // Validate at least one product is added
                if ($('#order_table tbody tr').length === 0) {
                    alert('Please add at least one product to the order.');
                    e.preventDefault();
                    return false;
                }

                // Validate product selection
                let isProductValid = true;
                $('#order_table tbody tr').each(function () {
                    let productSelect = $(this).find('.product-select');
                    if (productSelect.val() === "") {
                        alert('Please select a product for all order lines.');
                        isProductValid = false;
                        return false;
                    }
                });

                if (!isProductValid) {
                    e.preventDefault();
                    return false;
                }

                // If all validations pass, allow form submission
                return true;
            });

            // Product selection change
            $(document).on('change', '.product-select', function () {
                updateProductPrice($(this).closest('tr'));
            });

            // Add product row
            $('#add_product').click(function () {
                let newRow = $('#order_table tbody tr:first').clone();
                newRow.find('input').val('');
                newRow.find('.price').val('0.00');
                newRow.find('.discount').val('0');
                newRow.find('.subtotal').val('0.00');
                newRow.find('.product-select').val('');
                $('#order_table tbody').append(newRow);
            });

            // Remove product row
            $(document).on('click', '.remove_product', function () {
                if ($('#order_table tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    // After removing a row, check if there are any products left
                    checkForProducts();
                } else {
                    // If it's the last row, just clear it instead of removing
                    let row = $(this).closest('tr');
                    row.find('.product-select').val('');
                    row.find('.product-description').val('');
                    row.find('.price').val('0.00');
                    row.find('.discount').val('0');
                    row.find('.subtotal').val('0.00');
                    checkForProducts();
                }
            });

            // Update on price or discount change
            $(document).on('input', '.price, .discount', function () {
                // Ensure discount is a whole number
                if ($(this).hasClass('discount')) {
                    let value = $(this).val();
                    $(this).val(value.replace(/[^0-9]/g, ''));
                }
                updateRowTotal($(this).closest('tr'));
            });

            // Initialize: hide delivery fee row until products are added
            $('#delivery_fee_row').hide();

            // Initialize the form on page load
            updateTotals();
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
<?php
// Close database connections
$result->close();
$customerResult->close();
$conn->close();
?>