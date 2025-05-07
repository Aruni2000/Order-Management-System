<?php
include 'db_connection.php';
include 'functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if invoice ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: invoice_create.php");
    exit();
}

$invoice_id = intval($_GET['id']);
$show_payment_details = isset($_GET['show_payment']) && $_GET['show_payment'] === 'true';

// Get invoice details with pay_status field
$sql = "SELECT i.*, i.pay_status as invoice_pay_status, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, 
         c.address as customer_address, u.name as user_name
         FROM invoices i
         JOIN customers c ON i.customer_id = c.customer_id
         JOIN users u ON i.user_id = u.id
         WHERE i.invoice_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Invoice not found";
    exit();
}

$invoice = $result->fetch_assoc();

// Get currency from invoice
$currency = isset($invoice['currency']) ? strtolower($invoice['currency']) : 'usd';
$currencySymbol = ($currency == 'usd') ? '$' : 'Rs.';

// Modified item query to include pay_status field
$itemSql = "SELECT ii.*, ii.pay_status, p.name as product_name, 
            COALESCE(ii.description, p.description) as product_description,
            ii.total_amount as item_price
            FROM invoice_items ii
            JOIN products p ON ii.product_id = p.id
            WHERE ii.invoice_id = ?";

$stmt = $conn->prepare($itemSql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$itemsResult = $stmt->get_result();
$items = [];

while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Determine overall invoice payment status
// First check if invoice pay_status is set in the database
if (isset($invoice['invoice_pay_status']) && !empty($invoice['invoice_pay_status'])) {
    // Use the status directly from the database
    $invoicePayStatus = strtolower($invoice['invoice_pay_status']);
} else {
    // Fall back to the item-based logic
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
        $invoicePayStatus = 'paid';
    } elseif ($anyItemPaid) {
        $invoicePayStatus = 'partial';
    } else {
        $invoicePayStatus = 'unpaid';
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
function getPaymentStatusColor($status) {
    $status = strtolower($status ?? 'unpaid');
    
    switch($status) {
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
function getPaymentStatusBadge($status) {
    $status = strtolower($status ?? 'unpaid');
    
    switch($status) {
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #<?php echo $invoice_id; ?></title>
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

        .invoice-container {
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

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-title {
            color: #333;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .invoice-date {
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

        /* Updated table styles with full row gradient */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Full row gradient for table header */
        .product-table thead tr {
            background: linear-gradient(to right, #4CAF50, #17a2b8);
        }

        .product-table th {
            color: white;
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
            background: transparent; /* Remove individual cell backgrounds */
        }

        .product-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
        }

        /* Style for alternating rows */
        .product-table tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }

        /* Hover effect */
        .product-table tbody tr:hover {
            background-color: #e9ecef;
        }

        .product-table .total-row td {
            font-weight: normal;
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

        /* Control buttons */
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

        /* Payment status badge */
        .payment-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }

        /* Print styles to ensure gradients print properly */
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

            body {
                background-color: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                padding: 0;
            }
            
            .control-buttons, .alert {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <?php if (!$autoPrint): ?>
        <div class="control-buttons">
            <button class="btn btn-primary" onclick="window.print()">Print Invoice</button>
            <button class="btn btn-secondary" onclick="window.location.href='invoice_view.php?id=<?php echo $invoice_id; ?>'">Open in New Tab</button>
        </div>
        <?php endif; ?>

        <div class="invoice-header">
            <div class="company-logo">
                <img src="img/system/fe_it_logo.png" alt="FE IT Solutions Logo">
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE : # <?php echo $invoice_id; ?></div>
                <div class="invoice-date">Date Issued :</div>
                <div><?php echo date('Y-m-d', strtotime($invoice['issue_date'])); ?> - <?php echo date('H:i:s'); ?></div>
                <div class="pay-status">
                    Pay Status: 
                    <span class="payment-badge <?php echo getPaymentStatusBadge($invoicePayStatus); ?>">
                        <?php echo ucfirst($invoicePayStatus); ?>
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
                    <div>Wijayamangalarama Road,Kohuwala</div>
                    <div><?php echo htmlspecialchars($company['email']); ?></div>
                    <div><?php echo htmlspecialchars($company['phone']); ?></div>
                </div>
            </div>
            <div class="billing-block">
                <div class="billing-title">Billing To :</div>
                <address>
                    <strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong><br>
                    <?php echo nl2br(htmlspecialchars($invoice['customer_address'])); ?><br>
                    Email: <?php echo htmlspecialchars($invoice['customer_email']); ?><br>
                    Phone: <?php echo htmlspecialchars($invoice['customer_phone']); ?>
                </address>
            </div>
        </div>

        <table class="product-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="30%">PRODUCT</th>
                    <th width="50%">DESCRIPTION</th>
                    <th width="15%" style="text-align: right;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_description']); ?></td>
                        <td style="text-align: right;">
                            <?php 
                            // Handle null values and display the price
                            $price = $item['item_price'] ?? 0;
                            if ($price == 0 && count($items) > 0) {
                                // Fallback: use total amount divided by number of items
                                $price = $invoice['total_amount'] / count($items);
                            }
                            echo $currencySymbol . ' ' . number_format($price, 2); 
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; border-right: none;">Sub Total :</td>
                    <td class="total-value"><?php echo $currencySymbol . ' ' . number_format($invoice['subtotal'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; border-right: none;">Discount :</td>
                    <td class="total-value"><?php echo $currencySymbol . ' ' . number_format($invoice['discount'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; border-right: none;">Total :</td>
                    <td class="total-value"><?php echo $currencySymbol . ' ' . number_format($invoice['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="notes">
            <div class="note-title">Note:</div>
            <p><?php echo !empty($invoice['notes']) ? htmlspecialchars($invoice['notes']) : 'Once the order has been verified by the accounts payable team and recorded, the only task left is to send it for approval before releasing the payment'; ?></p>
        </div>

        <div class="payment-info">
            <div class="payment-methods">
                <h5>Payment Methods</h5>
                <p>
                    Bank Transfer: Bank of Ceylon<br>
                    Account Name: FE IT Solutions pvt (Ltd)<br>
                    Account Number: 8956321452<br>
                    Branch: Kohuwala
                </p>
            </div>
            <div class="signature">
                <div class="signature-line">
                    Authorized Signature
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php if ($autoPrint): ?>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        }
        <?php endif; ?>
        
        // Handle Mark as Paid button click
        document.addEventListener('DOMContentLoaded', function() {
            const markAsPaidBtn = document.getElementById('markAsPaidBtn');
            if (markAsPaidBtn) {
                markAsPaidBtn.addEventListener('click', function() {
                    // Create form data for the AJAX request
                    const formData = new FormData();
                    formData.append('invoice_id', '<?php echo $invoice_id; ?>');
                    formData.append('pay_status', 'paid');
                    
                    // Send AJAX request
                    fetch('update_invoice_status.php', {
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