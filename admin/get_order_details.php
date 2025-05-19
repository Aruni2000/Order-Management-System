<?php
// Include necessary files
require_once 'db_connection.php';
require_once 'functions.php';

// Get order ID from request
$orderId = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($orderId)) {
    echo '<div class="alert alert-danger">Invalid order ID provided.</div>';
    exit;
}

// Fetch order header information
$orderQuery = "SELECT oh.*, c.name as customer_name, c.email as customer_email,
               c.phone as customer_phone, c.address as customer_address
               FROM order_header oh
               LEFT JOIN customers c ON oh.customer_id = c.customer_id
               WHERE oh.order_id = ?";
               
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("s", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Order not found.</div>';
    exit;
}

$order = $result->fetch_assoc();

// Fetch order items
$itemsQuery = "SELECT oi.*, p.product_name, p.sku
               FROM order_items oi
               LEFT JOIN products p ON oi.product_id = p.product_id
               WHERE oi.order_id = ?";
               
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("s", $orderId);
$stmt->execute();
$itemsResult = $stmt->get_result();
$items = [];

while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Format currency and dates
$issueDateFormatted = date('d/m/Y', strtotime($order['issue_date']));
$dueDateFormatted = date('d/m/Y', strtotime($order['due_date']));
$currencySymbol = ($order['currency'] == 'usd') ? '$' : 'Rs';
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-primary text-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice me-2"></i>Order #<?php echo htmlspecialchars($orderId); ?>
            </h5>
            <span class="badge bg-warning">Pending</span>
        </div>
    </div>
    
    <div class="card-body p-4">
        <!-- Customer & Order Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Customer Information</h6>
                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></p>
                <p class="mb-1"><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['customer_address'] ?? 'N/A')); ?></p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Order Information</h6>
                <p class="mb-1"><strong>Order Date:</strong> <?php echo $issueDateFormatted; ?></p>
                <p class="mb-1"><strong>Due Date:</strong> <?php echo $dueDateFormatted; ?></p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-warning">Pending</span></p>
                <p class="mb-1"><strong>Payment Status:</strong> 
                    <?php if ($order['pay_status'] == 'paid'): ?>
                        <span class="badge bg-success">Paid</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Unpaid</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <hr>
        
        <!-- Order Items -->
        <h6 class="text-muted mb-3">Order Items</h6>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></td>
                                <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo number_format($item['unit_price'], 2) . ' ' . $currencySymbol; ?></td>
                                <td><?php echo number_format($item['quantity'] * $item['unit_price'], 2) . ' ' . $currencySymbol; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No items found for this order</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Subtotal:</th>
                        <th><?php echo number_format($order['sub_total'] ?? 0, 2) . ' ' . $currencySymbol; ?></th>
                    </tr>
                    <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                    <tr>
                        <th colspan="5" class="text-end">Discount:</th>
                        <th><?php echo number_format($order['discount'], 2) . ' ' . $currencySymbol; ?></th>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($order['tax']) && $order['tax'] > 0): ?>
                    <tr>
                        <th colspan="5" class="text-end">Tax:</th>
                        <th><?php echo number_format($order['tax'], 2) . ' ' . $currencySymbol; ?></th>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th colspan="5" class="text-end">Total Amount:</th>
                        <th><?php echo number_format($order['total_amount'] ?? 0, 2) . ' ' . $currencySymbol; ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if (!empty($order['notes'])): ?>
        <div class="mt-4">
            <h6 class="text-muted mb-2">Order Notes</h6>
            <div class="p-3 bg-light rounded">
                <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>