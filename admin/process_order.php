<?php
// Disable error reporting for production
error_reporting(0);

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Start a new output buffer
ob_start();

// Include necessary files
require_once 'db_connection.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate required fields
        if (empty($_POST['customer_name'])) {
            throw new Exception("Customer name is required.");
        }

        // Check if products are added
        if (empty($_POST['order_product'])) {
            throw new Exception("At least one product must be added to the order.");
        }

        // Begin transaction
        $conn->begin_transaction();
        
        // Get current user ID from session (default to 1 if not set)
        $user_id = $_SESSION['user_id'] ?? 1;
        
        // Process customer details
        $customer_name = trim($_POST['customer_name']);
        $customer_email = $_POST['customer_email'] ?? '';
        $customer_address = $_POST['customer_address'] ?? 'No. 12, Galle Road, Colombo, Sri Lanka';
        $customer_phone = $_POST['customer_phone'] ?? '+94712345678';
        
        // Find or create customer
        $customer_id = 0;
        $checkCustomerSql = "SELECT customer_id FROM customers WHERE name = ? AND email = ?";
        $stmt = $conn->prepare($checkCustomerSql);
        $stmt->bind_param("ss", $customer_name, $customer_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $customer_id = $customer['customer_id'];
        } else {
            // Insert new customer
            $insertCustomerSql = "INSERT INTO customers (name, email, phone, address, status) 
                                 VALUES (?, ?, ?, ?, 'Active')";
            $stmt = $conn->prepare($insertCustomerSql);
            $stmt->bind_param("ssss", $customer_name, $customer_email, $customer_phone, $customer_address);
            $stmt->execute();
            $customer_id = $conn->insert_id;
        }
        
        // Prepare order details
        $order_date = $_POST['order_date'] ?? date('Y-m-d');
        $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
        
        // Get notes from form input instead of using a default message
        $notes = $_POST['notes'] ?? "";
        
        // Get currency from form input
        $currency = isset($_POST['order_currency']) ? strtolower($_POST['order_currency']) : 'lkr';
        
        // Get order status from form
        $order_status = $_POST['order_status'] ?? 'Unpaid';
        $pay_status = $order_status === 'Paid' ? 'paid' : 'unpaid';
        $pay_date = $order_status === 'Paid' ? date('Y-m-d') : null;
        $status = $order_status === 'Paid' ? 'done' : 'pending';
        
        // Detailed calculation of totals
        $products = $_POST['order_product'];
        $product_prices = $_POST['order_product_price'];
        $discounts = $_POST['order_product_discount'] ?? [];
        $product_descriptions = $_POST['order_product_description'] ?? [];
        
        // Initialize subtotal to store the original price before discounts
        $subtotal_before_discounts = 0;
        $total_discount = 0;
        
        // Get delivery fee from form
        $delivery_fee = isset($_POST['delivery_fee']) ? floatval($_POST['delivery_fee']) : 0.00;
        
        // Prepare an array to store order items
        $order_items = [];
        foreach ($products as $key => $product_id) {
            // Skip empty product selections
            if (empty($product_id)) continue;
            
            $original_price = floatval($product_prices[$key] ?? 0);
            $discount = floatval($discounts[$key] ?? 0);
            $description = $product_descriptions[$key] ?? '';
            
            // Ensure discount doesn't exceed price
            $discount = min($discount, $original_price);
            
            // Calculate subtotal before discount
            $subtotal_before_discounts += $original_price;
            $total_discount += $discount;
            
            // Store item details for insertion
            $order_items[] = [
                'product_id' => $product_id,
                'original_price' => $original_price,
                'discount' => $discount,
                'description' => $description
            ];
        }
        
        // Final total calculation with delivery fee
        $total_amount = $subtotal_before_discounts - $total_discount + $delivery_fee;
        
        // Insert order_header
        $insertOrderSql = "INSERT INTO order_header (
            customer_id, user_id, issue_date, due_date, 
            subtotal, discount, total_amount, delivery_fee,
            notes, currency, status, pay_status, pay_date, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertOrderSql);
        $stmt->bind_param(
            "iissddddsssssi", 
            $customer_id, $user_id, $order_date, $due_date, 
            $subtotal_before_discounts, $total_discount, $total_amount, $delivery_fee,
            $notes, $currency, $status, $pay_status, $pay_date, $user_id
        );
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Order items insertion
        $insertItemSql = "INSERT INTO order_items (
            order_id, product_id, discount, 
            total_amount, pay_status, status, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertItemSql);
        
        foreach ($order_items as $item) {
            // Calculate the price after discount
            $item_price_after_discount = $item['original_price'] - $item['discount'];
            
            $stmt->bind_param(
                "iidssss", 
                $order_id, 
                $item['product_id'], 
                $item['discount'], 
                $item_price_after_discount, // Store the discounted price
                $pay_status, 
                $status, 
                $item['description']
            );
            $stmt->execute();
        }
        
        // If order is marked as Paid, insert into payments table
        if ($order_status === 'Paid') {
            // Default payment method to 'Cash'
            $payment_method = 'Cash';
            
            // Insert payment record
            $insertPaymentSql = "INSERT INTO payments (
                order_id, 
                amount_paid, 
                payment_method, 
                payment_date, 
                pay_by
            ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insertPaymentSql);
            $stmt->bind_param(
                "idsss", 
                $order_id, 
                $total_amount, 
                $payment_method, 
                $pay_date, 
                $user_id
            );
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set success message
        $_SESSION['order_success'] = "Order #" . $order_id . " created successfully!";
        
        // Redirect to view order page
        header("Location: download_order.php?id=" . $order_id);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set error message in session
        $_SESSION['order_error'] = $e->getMessage();
        
        // Redirect back to order creation page
        header("Location: create_order.php");
        exit();
    }
} else {
    // Not a POST request
    header("Location: create_order.php");
    exit();
}
?>