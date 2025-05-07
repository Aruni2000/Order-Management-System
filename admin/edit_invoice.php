<?php
include 'db_connection.php'; // Include the database connection file
include 'functions.php'; // Include helper functions

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if invoice_id is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to invoices list if no valid ID provided
    header("Location: invoices.php");
    exit;
}

$invoice_id = intval($_GET['id']); // Ensure invoice_id is an integer

// Fetch invoice data
$invoiceSql = "SELECT * FROM invoices WHERE invoice_id = ?";
$stmt = $conn->prepare($invoiceSql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error . " for query: " . $invoiceSql);
}
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoiceResult = $stmt->get_result();

if ($invoiceResult->num_rows === 0) {
    // Invoice not found, redirect
    header("Location: invoices.php");
    exit;
}

$invoice = $invoiceResult->fetch_assoc();

// Fetch invoice items
$itemsSql = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$stmt = $conn->prepare($itemsSql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error . " for query: " . $itemsSql);
}
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoiceItems = $stmt->get_result();

// Fetch products for dropdown
$sql = "SELECT * FROM products ORDER BY name ASC";
$result = $conn->query($sql);
if ($result === false) {
    die("Query failed: " . $conn->error . " for query: " . $sql);
}

// Fetch customer for this invoice
$customerSql = "SELECT * FROM customers WHERE customer_id = ?";
$stmt = $conn->prepare($customerSql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error . " for query: " . $customerSql);
}
$stmt->bind_param("i", $invoice['customer_id']);
$stmt->execute();
$customerData = $stmt->get_result()->fetch_assoc();

// Fetch all customers for dropdown
$allCustomersSql = "SELECT * FROM customers ORDER BY name ASC";
$customerResult = $conn->query($allCustomersSql);
if ($customerResult === false) {
    die("Query failed: " . $conn->error . " for query: " . $allCustomersSql);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Invoice - SB Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h2 class="mt-4">Edit Invoice #<?php echo htmlspecialchars($invoice_id); ?></h2>

                    <form method="post" action="update_invoice.php">
                        <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($invoice_id); ?>">
                        <div class="table-container">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label>Invoice Type:</label>
                                    <select name="invoice_type" class="form-control">
                                        <option value="Standard" <?php echo (isset($invoice['type']) && $invoice['type'] == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                                        <option value="Recurring" <?php echo (isset($invoice['type']) && $invoice['type'] == 'Recurring') ? 'selected' : ''; ?>>Recurring</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Status:</label>
                                    <select name="invoice_status" class="form-control">
                                        <option value="Paid" <?php echo (isset($invoice['status']) && $invoice['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Unpaid" <?php echo (isset($invoice['status']) && $invoice['status'] == 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                                        <option value="Pending" <?php echo (isset($invoice['status']) && $invoice['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Invoice Date:</label>
                                    <input type="date" class="form-" name="invoice_date"
                                        value="<?php echo isset($invoicontrolce['invoice_date']) ? htmlspecialchars($invoice['invoice_date']) : date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label>Due Date:</label>
                                    <input type="date" class="form-control" name="due_date"
                                        value="<?php echo isset($invoice['due_date']) ? htmlspecialchars($invoice['due_date']) : date('Y-m-d', strtotime('+30 days')); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Customer Information</h5>
                                    <div class="mb-2">
                                        <label>Select Existing Customer:</label>
                                        <select id="existing_customer" class="form-control">
                                            <option value="">-- New Customer --</option>
                                            <?php
                                            $customerResult->data_seek(0);
                                            while ($customerRow = $customerResult->fetch_assoc()) :
                                                $selected = (isset($invoice['customer_id']) && $customerRow['customer_id'] == $invoice['customer_id']) ? 'selected' : '';
                                            ?>
                                                <option value="<?= htmlspecialchars($customerRow['customer_id']) ?>" <?= $selected ?>
                                                    data-name="<?= htmlspecialchars($customerRow['name']) ?>"
                                                    data-email="<?= htmlspecialchars($customerRow['email']) ?>"
                                                    data-phone="<?= htmlspecialchars($customerRow['phone']) ?>"
                                                    data-address="<?= htmlspecialchars($customerRow['address']) ?>">
                                                    <?= htmlspecialchars($customerRow['name']) ?> -
                                                    <?= htmlspecialchars($customerRow['email']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <input type="text" class="form-control mb-2" name="customer_name" id="customer_name"
                                        placeholder="Enter Name" value="<?php echo isset($customerData['name']) ? htmlspecialchars($customerData['name']) : ''; ?>" required>
                                    <input type="email" class="form-control mb-2" name="customer_email"
                                        id="customer_email" placeholder="E-mail Address" value="<?php echo isset($customerData['email']) ? htmlspecialchars($customerData['email']) : ''; ?>">
                                    <input type="text" class="form-control mb-2" name="customer_address"
                                        id="customer_address" placeholder="Address" value="<?php echo isset($customerData['address']) ? htmlspecialchars($customerData['address']) : ''; ?>">
                                    <input type="text" class="form-control mb-2" name="customer_phone"
                                        id="customer_phone" placeholder="Phone Number" value="<?php echo isset($customerData['phone']) ? htmlspecialchars($customerData['phone']) : ''; ?>">
                                    <input type="hidden" name="customer_id" id="customer_id" value="<?php echo isset($invoice['customer_id']) ? htmlspecialchars($invoice['customer_id']) : ''; ?>">
                                </div>
                            </div>

                            <h5 class="mt-4">Products</h5>
                            <table class="table table-bordered" id="invoice_table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Discount</th>
                                        <th>Sub Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($invoiceItems->num_rows > 0) {
                                        while ($item = $invoiceItems->fetch_assoc()) {
                                            $product_id = $item['product_id'] ?? '';
                                            $unit_price = $item['unit_price'] ?? 0.00;
                                            $discount = isset($item['discount']) ? $item['discount'] : 0.00;
                                            $total_price = $item['total_price'] ?? 0.00;
                                    ?>
                                            <tr>
                                                <td>
                                                    <select name="invoice_product[]" class="form-control product-select">
                                                        <option value="">-- Select Product --</option>
                                                        <?php
                                                        $result->data_seek(0);
                                                        while ($row = $result->fetch_assoc()) :
                                                            $selected = ($row['id'] == $product_id) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?= htmlspecialchars($row['id']) ?>" data-price="<?= htmlspecialchars($row['price']) ?>" <?= $selected ?>>
                                                                <?= htmlspecialchars($row['name']) ?> -
                                                                $<?= number_format($row['price'], 2) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </td>
                                                <td><input type="number" name="invoice_product_price[]"
                                                        class="form-control price" value="<?= number_format($unit_price, 2, '.', '') ?>" step="0.01"></td>
                                                <td><input type="number" name="invoice_product_discount[]"
                                                        class="form-control discount" value="<?= number_format($discount, 2, '.', '') ?>" step="0.01"></td>
                                                <td><input type="text" name="invoice_product_sub[]"
                                                        class="form-control subtotal" value="<?= number_format($total_price, 2, '.', '') ?>" readonly></td>
                                                <td><button type="button" class="btn btn-danger remove_product">X</button></td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                        // Default row if no items exist
                                    ?>
                                        <tr>
                                            <td>
                                                <select name="invoice_product[]" class="form-control product-select">
                                                    <option value="">-- Select Product --</option>
                                                    <?php
                                                    $result->data_seek(0);
                                                    while ($row = $result->fetch_assoc()) :
                                                    ?>
                                                        <option value="<?= htmlspecialchars($row['id']) ?>" data-price="<?= htmlspecialchars($row['price']) ?>">
                                                            <?= htmlspecialchars($row['name']) ?> -
                                                            $<?= number_format($row['price'], 2) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" name="invoice_product_price[]"
                                                    class="form-control price" value="0.00" step="0.01"></td>
                                            <td><input type="number" name="invoice_product_discount[]"
                                                    class="form-control discount" value="0.00" step="0.01"></td>
                                            <td><input type="text" name="invoice_product_sub[]"
                                                    class="form-control subtotal" value="0.00" readonly></td>
                                            <td><button type="button" class="btn btn-danger remove_product">X</button></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Sub Total:</strong></td>
                                        <td><input type="text" id="subtotal_amount" name="subtotal" class="form-control"
                                                value="<?= number_format($invoice['subtotal'] ?? 0, 2, '.', '') ?>" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Discount:</strong></td>
                                        <td><input type="text" id="discount_amount" name="discount" class="form-control"
                                                value="<?= number_format($invoice['discount'] ?? 0, 2, '.', '') ?>" readonly></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><input type="text" id="total_amount" name="total_amount"
                                                class="form-control" value="<?= number_format($invoice['total'] ?? 0, 2, '.', '') ?>" readonly></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <button type="button" id="add_product" class="btn btn-primary">Add Product</button>

                            <div class="mt-4">
                                <label>Additional Notes:</label>
                                <textarea name="notes" class="form-control" rows="3"><?php echo isset($invoice['notes']) ? htmlspecialchars($invoice['notes']) : ''; ?></textarea>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-success">Update Invoice</button>
                                <a href="invoices.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        $(document).ready(function() {
            // Customer dropdown handler
            $('#existing_customer').change(function() {
                var selected = $(this).find('option:selected');
                if (selected.val()) {
                    $('#customer_id').val(selected.val());
                    $('#customer_name').val(selected.data('name'));
                    $('#customer_email').val(selected.data('email'));
                    $('#customer_address').val(selected.data('address'));
                    $('#customer_phone').val(selected.data('phone'));
                } else {
                    // Clear fields if "New Customer" is selected
                    $('#customer_id').val('');
                    $('#customer_name').val('');
                    $('#customer_email').val('');
                    $('#customer_address').val('');
                    $('#customer_phone').val('');
                }
            });

            // Product selection handler
            $(document).on('change', '.product-select', function() {
                var row = $(this).closest('tr');
                var price = $(this).find('option:selected').data('price') || 0;
                row.find('.price').val(parseFloat(price).toFixed(2));
                calculateRowTotal(row);
            });

            // Price and discount change handler
            $(document).on('input', '.price, .discount', function() {
                calculateRowTotal($(this).closest('tr'));
            });

            // Add product row
            $('#add_product').click(function() {
                var newRow = $('#invoice_table tbody tr:first').clone();
                newRow.find('select').val('');
                newRow.find('input[type="number"]').val('0.00');
                newRow.find('input[type="text"]').val('0.00');
                $('#invoice_table tbody').append(newRow);
            });

            // Remove product row
            $(document).on('click', '.remove_product', function() {
                if ($('#invoice_table tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calculateInvoiceTotal();
                } else {
                    alert('At least one product row is required.');
                }
            });

            // Function to calculate row total
            function calculateRowTotal(row) {
                var price = parseFloat(row.find('.price').val()) || 0;
                var discount = parseFloat(row.find('.discount').val()) || 0;
                var subtotal = price - discount;
                if (subtotal < 0) subtotal = 0;
                row.find('.subtotal').val(subtotal.toFixed(2));
                calculateInvoiceTotal();
            }

            // Function to calculate invoice total
            function calculateInvoiceTotal() {
                var subtotal = 0;
                var totalDiscount = 0;

                $('#invoice_table tbody tr').each(function() {
                    var price = parseFloat($(this).find('.price').val()) || 0;
                    var discount = parseFloat($(this).find('.discount').val()) || 0;
                    
                    subtotal += price;
                    totalDiscount += discount;
                });

                var total = subtotal - totalDiscount;
                if (total < 0) total = 0;

                $('#subtotal_amount').val(subtotal.toFixed(2));
                $('#discount_amount').val(totalDiscount.toFixed(2));
                $('#total_amount').val(total.toFixed(2));
            }

            // Initialize calculations
            calculateInvoiceTotal();
        });
    </script>
</body>
</html>