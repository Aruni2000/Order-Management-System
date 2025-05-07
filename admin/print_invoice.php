<?php
include 'db_connection.php';
include 'functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if invoice ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: invoices.php");
    exit();
}

$invoice_id = intval($_GET['id']);

// Get invoice details
$sql = "SELECT i.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, 
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

// Get invoice items
$itemSql = "SELECT ii.*, p.name as product_name, p.description as product_description
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

// Get payment details if any
$paymentSql = "SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC";
$stmt = $conn->prepare($paymentSql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$paymentsResult = $stmt->get_result();
$payments = [];

while ($payment = $paymentsResult->fetch_assoc()) {
    $payments[] = $payment;
}

// Company information
$company = [
    'name' => 'FE IT Solutions pvt (Ltd)',
    'address' => 'No: 04, Wijayamangalarama Road, Kohuwala',
    'email' => 'info@feitsolutions.com',
    'phone' => '011-2824524'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #<?php echo $invoice_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.4;
        }
        
        .invoice-container {
            width: 21cm;
            min-height: 29.7cm;
            padding: 2cm;
            margin: 0 auto;
            background: #fff;
        }
        
        .invoice-header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-logo {
            text-align: left;
            font-size: 24px;
            font-weight: bold;
        }
        
        .invoice-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .invoice-info {
            margin-bottom: 30px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            display: inline-block;
            margin-left: 10px;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        table th {
            background-color: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .footer {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .signature {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 200px;
            text-align: center;
            float: right;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }
            
            .invoice-container {
                width: 100%;
                padding: 1cm;
                box-shadow: none;
            }
            
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print text-center mt-3 mb-3">
        <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
        <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary">Back to View</a>
    </div>
    
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="row">
                <div class="col-6">
                    <div class="company-logo"><?php echo htmlspecialchars($company['name']); ?></div>
                </div>
                <div class="col-6 text-end">
                    <h4>INVOICE #<?php echo $invoice_id; ?></h4>
                </div>
            </div>
        </div>
        
        <div class="row invoice-info">
            <div class="col-sm-6">
                <h5>Billing From:</h5>
                <address>
                    <strong><?php echo htmlspecialchars($company['name']); ?></strong><br>
                    <?php echo nl2br(htmlspecialchars($company['address'])); ?><br>
                    Email: <?php echo htmlspecialchars($company['email']); ?><br>
                    Phone: <?php echo htmlspecialchars($company['phone']); ?>
                </address>
            </div>
            <div class="col-sm-6 text-end">
                <h5>Billing To:</h5>
                <address>
                    <strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong><br>
                    <?php if (!empty($invoice['customer_address'])): ?>
                        <?php echo nl2br(htmlspecialchars($invoice['customer_address'])); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($invoice['customer_email'])): ?>
                        Email: <?php echo htmlspecialchars($invoice['customer_email']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($invoice['customer_phone'])): ?>
                        Phone: <?php echo htmlspecialchars($invoice['customer_phone']); ?>
                    <?php endif; ?>
                </address>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-12">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 25%;">Invoice Number</th>
                        <td style="width: 25%;">#<?php echo $invoice_id; ?></td>
                        <th style="width: 25%;">Status</th>
                        <td style="width: 25%;">
                            <span class="status-badge status-<?php echo strtolower($invoice['status']); ?>">
                                <?php echo $invoice['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Invoice Date</th>
                        <td><?php echo date('F j, Y', strtotime($invoice['issue_date'])); ?></td>
                        <th>Due Date</th>
                        <td><?php echo date('F j, Y', strtotime($invoice['due_date'])); ?></td>
                    </tr>
                    <tr>
                        <th>Invoice Type</th>
                        <td colspan="3"><?php echo $invoice['invoice_type']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Description</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $item): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['product_description']); ?></td>
                    <td class="text-right">$<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-right">$<?php echo number_format($item['total_price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                    <td class="text-right">$<?php echo number_format($invoice['subtotal'], 2); ?></td>
                </tr>
                <?php if ($invoice['discount'] > 0): ?>
                <tr>
                    <td colspan="4" class="text-right"><strong>Discount:</strong></td>
                    <td class="text-right">$<?php echo number_format($invoice['discount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right"><strong>$<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        <?php if (!empty($invoice['notes'])): ?>
        <div class="notes">
            <h5>Notes</h5>
            <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($payments)): ?>
        <div class="mt-4">
            <h5>Payment History</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo date('F j, Y g:i A', strtotime($payment['payment_date'])); ?></td>
                        <td class="text-right">$<?php echo number_format($payment['amount_paid'], 2); ?></td>
                        <td><?php echo $payment['payment_method']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <div class="row">
                <div class="col-8">
                    <h5>Payment Methods</h5>
                    <p>
                        Bank Transfer: Bank of Ceylon<br>
                        Account Name: FE IT Solutions pvt (Ltd)<br>
                        Account Number: 8956321452<br>
                        Branch: Kohuwala
                    </p>
                    <p>Thank you for your business!</p>
                </div>
                <div class="col-4">
                    <div class="signature">
                        <p>Authorized Signature</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>