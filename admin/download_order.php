<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: signin.php");
    exit();
}

// Include database connection and functions
include 'db_connection.php';
include 'functions.php';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Order ID is required");
}

$order_id = $_GET['id'];
$show_payment_details = isset($_GET['show_payment']) && $_GET['show_payment'] === 'true';

// Fetch order details from database with individual item discounts
$order_query = "SELECT i.*, i.pay_status AS order_pay_status, c.name as customer_name, 
                c.address as customer_address, c.email as customer_email, c.phone as customer_phone,
                p.payment_id, p.amount_paid, p.payment_method, p.payment_date, p.pay_by,
                r.name as paid_by_name, u.name as user_name,
                i.delivery_fee
                FROM order_header i 
                LEFT JOIN customers c ON i.customer_id = c.customer_id
                LEFT JOIN payments p ON i.order_id = p.order_id
                LEFT JOIN roles r ON p.pay_by = r.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE i.order_id = ?";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found");
}

$order = $result->fetch_assoc();

// Get currency from order
$currency = isset($order['currency']) ? strtolower($order['currency']) : 'lkr';
$currencySymbol = ($currency == 'usd') ? '$' : 'Rs.';

// Ensure delivery fee is properly set
$delivery_fee = isset($order['delivery_fee']) && !is_null($order['delivery_fee']) ? floatval($order['delivery_fee']) : 0.00;

// Modified item query to include item-level discounts and original prices
$itemSql = "SELECT ii.*, ii.pay_status, p.name as product_name, 
            COALESCE(ii.description, p.description) as product_description,
            (ii.total_amount + ii.discount) as original_price, 
            ii.total_amount as item_price,
            COALESCE(ii.discount, 0) as item_discount
            FROM order_items ii
            JOIN products p ON ii.product_id = p.id
            WHERE ii.order_id = ?";

$stmt = $conn->prepare($itemSql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$itemsResult = $stmt->get_result();
$items = [];

while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Determine overall order payment status
if (isset($order['order_pay_status']) && !empty($order['order_pay_status'])) {
    $orderPayStatus = strtolower($order['order_pay_status']);
} else {
    $allItemsPaid = true;
    $anyItemPaid = false;

    foreach ($items as $item) {
        if (strtolower($item['pay_status']) == 'paid') {
            $anyItemPaid = true;
        } else {
            $allItemsPaid = false;
        }
    }

    if ($allItemsPaid && count($items) > 0) {
        $orderPayStatus = 'paid';
    } elseif ($anyItemPaid) {
        $orderPayStatus = 'partial';
    } else {
        $orderPayStatus = 'unpaid';
    }
}

// Company information
$company = [
    'name' => 'FE IT Solutions pvt (Ltd)',
    'address' => 'No: 04, Wijayamangalarama Road, Kohuwala',
    'email' => 'info@feitsolutions.com',
    'phone' => '011-2824524'
];

// Function to get the color for payment status
function getPaymentStatusColor($status)
{
    $status = strtolower($status ?? 'unpaid');

    switch ($status) {
        case 'paid':
            return "color: #28a745;"; // Green for paid
        case 'partial':
            return "color: #fd7e14;"; // Orange for partial payment
        case 'unpaid':
        default:
            return "color: #dc3545;"; // Red for unpaid
    }
}

// Function to get badge class for payment status
function getPaymentStatusBadge($status)
{
    $status = strtolower($status ?? 'unpaid');

    switch ($status) {
        case 'paid':
            return "bg-success"; // Green for paid
        case 'partial':
            return "bg-warning"; // Orange for partial payment
        case 'unpaid':
        default:
            return "bg-danger"; // Red for unpaid
    }
}

// Set autoPrint for normal view
$autoPrint = !$show_payment_details;

// Calculate total item-level discounts
$total_item_discounts = 0;
foreach ($items as $item) {
    $total_item_discounts += floatval($item['item_discount']);
}

// Calculate subtotal before discounts (using original prices)
$subtotal_before_discounts = 0;
foreach ($items as $item) {
    $subtotal_before_discounts += floatval($item['original_price']);
}

// Check if there are any discounts at all (order level or item level)
$has_any_discount = $total_item_discounts > 0 || floatval($order['discount']) > 0;

// Count how many columns we need to display in the table
$column_count = $has_any_discount ? 5 : 4;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order #<?php echo $order_id; ?></title>
     <!-- FAVICON -->
     <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" />


    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-size: 14px;
            color: #333;
        }

        .order-container {
            max-width: 100%;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .company-logo {
            display: flex;
            flex-direction: column;
        }

        .company-logo img {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .company-details {
            margin-top: 10px;
            line-height: 1.5;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }

        .order-info {
            text-align: right;
        }

        .order-title {
            color: #333;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .order-date {
            margin-top: 5px;
        }

        .billing-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .billing-block {
            flex: 0 0 48%;
        }

        .billing-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }

        .billing-info {
            line-height: 1.5;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .product-table thead tr {
            background: linear-gradient(to right, #4CAF50, #17a2b8);
        }

        .product-table th {
            color: white;
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
            background: transparent;
        }

        .product-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
        }

        .product-table tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }

        .product-table tbody tr:hover {
            background-color: #e9ecef;
        }

        .product-table .total-row td {
            font-weight: bold;
            text-align: right;
            padding: 5px 10px;
        }

        .product-table .total-value {
            text-align: right;
        }

        .notes {
            margin-top: 30px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #fff;
        }

        .note-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .payment-info {
            margin-top: 40px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .payment-methods h5 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin-top: 50px;
            text-align: center;
            padding-top: 5px;
        }

        .currency-name {
            font-size: 14px;
            font-weight: bold;
            margin-left: 5px;
        }

        .control-buttons {
            margin: 20px 0;
            text-align: center;
        }

        .control-buttons button {
            margin: 0 5px;
            padding: 8px 15px;
            cursor: pointer;
        }

        .pay-status {
            font-weight: bold;
            text-align: right;
            margin-top: 5px;
        }

        .payment-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }

        .bg-success {
            background-color: #28a745;
        }

        .bg-warning {
            background-color: #fd7e14;
        }

        .bg-danger {
            background-color: #dc3545;
        }

        .payment-info {
            line-height: 1.6;
        }

        .payment-details h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .item-discount {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        /* Highlight the delivery fee row */
        .delivery-fee-row td {
            background-color: #f8f9fa;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .product-table thead tr {
                background: linear-gradient(to right, #4CAF50, #17a2b8) !important;
            }

            .product-table th {
                color: white !important;
                background: transparent !important;
            }
            
            .delivery-fee-row td {
                background-color: #f8f9fa !important;
            }

            body {
                background-color: white;
                padding: 0;
            }

            .order-container {
                box-shadow: none;
                padding: 0;
            }

            .control-buttons,
            .alert {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="order-container">
        <?php if (!$autoPrint): ?>
            <div class="control-buttons">
                <button class="btn btn-primary" onclick="window.print()">Print Invoice</button>
                <button class="btn btn-secondary"
                    onclick="window.location.href='order_view.php?id=<?php echo $order_id; ?>'">Open in New Tab</button>

                <?php if ($show_payment_details && $orderPayStatus != 'paid'): ?>
                    <button id="markAsPaidBtn" class="btn btn-success">Mark as Paid</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="order-header">
            <div class="company-logo">
                <img src="img/system/fe_it_logo.png" alt="FE IT Solutions Logo">
            </div>
            <div class="order-info">
                <div class="order-title">ORDER : # <?php echo $order_id; ?></div>
                <div class="order-date">Date Issued: <?php echo date('Y-m-d', strtotime($order['issue_date'])); ?>
                </div>
                <div>Due Date: <?php echo date('Y-m-d', strtotime($order['due_date'])); ?></div>
                <!-- Add this line to display just the created time -->
                <div>Created Time: <?php echo date('H:i:s', strtotime($order['created_at'])); ?></div>
                <div class="pay-status">
                    Pay Status:
                    <span class="payment-badge <?php echo getPaymentStatusBadge($orderPayStatus); ?>">
                        <?php echo ucfirst($orderPayStatus); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="billing-details">
            <div class="billing-block">
                <div class="billing-title">Billing From :</div>
                <div class="billing-info">
                    <div><?php echo htmlspecialchars($company['name']); ?></div>
                    <div>No: 04</div>
                    <div>Wijayamangalarama Road, Kohuwala</div>
                    <div><?php echo htmlspecialchars($company['email']); ?></div>
                    <div><?php echo htmlspecialchars($company['phone']); ?></div>
                </div>
            </div>
            <div class="billing-block">
                <div class="billing-title">Billing To :</div>
                <div class="billing-info">
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                    <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?><br>
                    Email: <?php echo htmlspecialchars($order['customer_email']); ?><br>
                    Phone: <?php echo htmlspecialchars($order['customer_phone']); ?>
                </div>
            </div>
        </div>

        <table class="product-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="<?php echo $has_any_discount ? '35%' : '40%'; ?>">PRODUCT</th>
                    <th width="<?php echo $has_any_discount ? '30%' : '40%'; ?>">DESCRIPTION</th>
                    <?php if ($has_any_discount): ?>
                        <th width="15%" style="text-align: right;">DISCOUNT</th>
                    <?php endif; ?>
                    <th width="15%" style="text-align: right;">PRICE</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                if (count($items) > 0):
                    foreach ($items as $item):
                        $original_price = $item['original_price'] ?? 0;
                        $item_price = $item['item_price'] ?? 0;
                        $item_discount = $item['item_discount'] ?? 0;
                ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['product_description']); ?></td>
                            <?php if ($has_any_discount): ?>
                                <td style="text-align: right;">
                                    <?php echo $currencySymbol . ' ' . number_format($item_discount, 2); ?>
                                </td>
                            <?php endif; ?>
                            <td style="text-align: right;">
                                <?php 
                                // Show original price with discount info if applicable
                                if ($item_discount > 0) {
                                    echo $currencySymbol . ' ' . number_format($original_price, 2);
                                    echo '<br><span class="item-discount">(After discount: ' . $currencySymbol . ' ' . number_format($item_price, 2) . ')</span>';
                                } else {
                                    echo $currencySymbol . ' ' . number_format($item_price, 2);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td colspan="<?php echo $column_count; ?>" style="text-align: center;">No items found for this order</td>
                    </tr>
                <?php endif; ?>

                <tr class="total-row">
                    <td colspan="<?php echo $column_count - 1; ?>" style="text-align: right; border-right: none;">Sub Total :</td>
                    <td class="total-value">
                        <?php echo $currencySymbol . ' ' . number_format($subtotal_before_discounts, 2); ?>
                    </td>
                </tr>

                <?php if ($has_any_discount): ?>
                    <tr class="total-row">
                        <td colspan="<?php echo $column_count - 1; ?>" style="text-align: right; border-right: none;">Item Discounts :</td>
                        <td class="total-value">
                            <?php echo $currencySymbol . ' ' . number_format($total_item_discounts, 2); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if ($delivery_fee > 0): ?>
                    <tr class="total-row delivery-fee-row">
                        <td colspan="<?php echo $column_count - 1; ?>" style="text-align: right; border-right: none;">Delivery Fee :</td>
                        <td class="total-value">
                            <?php echo $currencySymbol . ' ' . number_format($delivery_fee, 2); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr class="total-row">
                    <td colspan="<?php echo $column_count - 1; ?>" style="text-align: right; border-right: none;">Total :</td>
                    <td class="total-value">
                        <?php 
                        // Calculate final total ensuring delivery fee is included
                        $final_total = $subtotal_before_discounts - $total_item_discounts + $delivery_fee;
                        echo $currencySymbol . ' ' . number_format($final_total, 2); 
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="notes">
            <div class="note-title">Note:</div>
            <p><?php echo !empty($order['notes']) ? nl2br(htmlspecialchars($order['notes'])) : 'Once the order has been verified by the accounts payable team and recorded, the only task left is to send it for approval before releasing the payment'; ?>
            </p>
        </div>

        <?php if ($orderPayStatus == 'paid' || $orderPayStatus == 'partial'): ?>
            <div class="payment-info">
                <div class="payment-details">
                    <h3>Payment Information</h3>
                    <div>Payment Method: <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></div>
                    <div>Amount Paid:
                        <?php echo $currencySymbol . ' ' . number_format((float) ($order['amount_paid'] ?? 0), 2); ?>
                    </div>
                    <div>Payment Date:
                        <?php echo ($order['payment_date']) ? date('d/m/Y', strtotime($order['payment_date'])) : 'N/A'; ?>
                    </div>
                    <div>Processed By: <?php echo htmlspecialchars($order['paid_by_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="signature">
                    <div class="signature-line">
                        Authorized Signature
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="payment-info">
                <div class="payment-methods">
                    <h5>Payment Methods</h5>
                    <p>
                        Account Name: F E IT SOLUTIONS PVT (LTD)<br>
                        Account Number: 100810008655<br>
                        Account Type: LKR Current Account<br>
                        Bank Name: Nations Trust Bank PLC
                    </p>
                </div>
                <div class="signature">
                    <div class="signature-line">
                        Authorized Signature
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($autoPrint): ?>
            // Auto print when page loads
            window.onload = function () {
                window.print();
            }
        <?php endif; ?>

        // Handle Mark as Paid button click
        document.addEventListener('DOMContentLoaded', function () {
            const markAsPaidBtn = document.getElementById('markAsPaidBtn');
            if (markAsPaidBtn) {
                markAsPaidBtn.addEventListener('click', function () {
                    // Create form data for the AJAX request
                    const formData = new FormData();
                    formData.append('order_id', '<?php echo $order_id; ?>');
                    formData.append('pay_status', 'paid');

                    // Send AJAX request
                    fetch('update_order_status.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Simply reload the current page to show updated status
                                window.location.reload();
                            } else {
                                alert('Error updating payment status: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while updating the payment status.');
                        });
                });
            }
        });
    </script>
</body>

</html>
<?php
$conn->close();
?>