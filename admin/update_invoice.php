<?php
include 'db_connection.php'; // Include the database connection file
include 'functions.php'; // Include helper functions

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if connection is successful
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Get invoice ID
    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    
    if ($invoice_id <= 0) {
        $_SESSION['error'] = "Invalid invoice ID";
        header("Location: invoices.php");
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Process customer data
        $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
        $customer_name = $_POST['customer_name'];
        $customer_email = $_POST['customer_email'];
        $customer_address = $_POST['customer_address'];
        $customer_phone = $_POST['customer_phone'];
        
        // If customer_id is empty or 0, create a new customer
        if (empty($customer_id)) {
            $customerSql = "INSERT INTO customers (name, email, address, phone) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($customerSql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error . " for query: " . $customerSql);
            }
            $stmt->bind_param("ssss", $customer_name, $customer_email, $customer_address, $customer_phone);
            $stmt->execute();
            $customer_id = $conn->insert_id;
        } else {
            // Update existing customer
            $customerSql = "UPDATE customers SET name = ?, email = ?, address = ?, phone = ? WHERE customer_id = ?";
            $stmt = $conn->prepare($customerSql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error . " for query: " . $customerSql);
            }
            $stmt->bind_param("ssssi", $customer_name, $customer_email, $customer_address, $customer_phone, $customer_id);
            $stmt->execute();
        }
        
        // Get invoice data
        $pay_status = $_POST['pay_status'] ?? 'unpaid';
        $pay_status = strtolower($pay_status); // Ensure lowercase for consistency
        $invoice_date = $_POST['invoice_date'];
        $due_date = $_POST['due_date'];
        $subtotal = floatval($_POST['subtotal']);
        $discount = floatval($_POST['discount']);
        $total_amount = floatval($_POST['total_amount']);
        $notes = $_POST['notes'];
        $currency = $_POST['currency'] ?? 'lkr';
        
        // Validate pay_status value (ensure it's one of the allowed values)
        $allowed_pay_statuses = ['paid', 'unpaid'];
        if (!in_array($pay_status, $allowed_pay_statuses)) {
            $pay_status = 'unpaid'; // Default to unpaid if invalid status
        }
        
        // Determine invoice_status based on pay_status
        $invoice_status = 'pending'; // Default
        if ($pay_status == 'paid') {
            $invoice_status = 'done';
        } else {
            $invoice_status = 'pending';
        }
        
        // Get the current user ID from session (assuming it's stored in session)
        $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1; // Default to 1 if not set
        
        // Update invoice table - ensure column names match database
        $updateInvoiceSql = "UPDATE invoices SET 
            customer_id = ?, 
            user_id = ?, 
            issue_date = ?, 
            due_date = ?, 
            subtotal = ?, 
            discount = ?, 
            total_amount = ?, 
            notes = ?, 
            status = ?, 
            pay_status = ?,
            currency = ?,
            pay_date = ? 
            WHERE invoice_id = ?";
            
        $stmt = $conn->prepare($updateInvoiceSql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error . " for query: " . $updateInvoiceSql);
        }
        
        // Set pay_date to current date if pay_status is 'paid', otherwise NULL
        $pay_date = ($pay_status == 'paid') ? date('Y-m-d') : null;
        
        $stmt->bind_param(
            "iissdddsssssi", 
            $customer_id, 
            $user_id, 
            $invoice_date, 
            $due_date, 
            $subtotal, 
            $discount, 
            $total_amount,
            $notes, 
            $invoice_status,
            $pay_status,
            $currency,
            $pay_date,
            $invoice_id
        );
        $stmt->execute();
        
        // Process invoice items - REORDERING THIS SECTION
        // First delete any existing items if we're re-adding them
        if (isset($_POST['invoice_product']) && is_array($_POST['invoice_product']) && !empty($_POST['invoice_product'])) {
            $deleteItemsSql = "DELETE FROM invoice_items WHERE invoice_id = ?";
            $stmt = $conn->prepare($deleteItemsSql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error . " for query: " . $deleteItemsSql);
            }
            $stmt->bind_param("i", $invoice_id);
            $stmt->execute();
            
            // Get product data from the form
            $products = $_POST['invoice_product'];
            $prices = $_POST['invoice_product_price'] ?? [];
            $discounts = $_POST['invoice_product_discount'] ?? [];
            $subtotals = $_POST['invoice_product_sub'] ?? [];
            $descriptions = $_POST['invoice_product_description'] ?? [];
            
            // Insert new invoice items with the CORRECT status values
            $insertItemSql = "INSERT INTO invoice_items 
                             (invoice_id, product_id, price, discount, total_amount, status, pay_status, description) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertItemSql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error . " for query: " . $insertItemSql);
            }
            
            for ($i = 0; $i < count($products); $i++) {
                // Skip empty product selections
                if (empty($products[$i])) continue;
                
                $product_id = intval($products[$i]);
                $price = floatval($prices[$i] ?? 0);
                $item_discount = floatval($discounts[$i] ?? 0);
                $total_price = floatval($subtotals[$i] ?? 0);
                $description = $descriptions[$i] ?? '';
                
                $stmt->bind_param("iiddssss", $invoice_id, $product_id, $price, $item_discount, $total_price, $invoice_status, $pay_status, $description);
                $stmt->execute();
            }
        }
        
        // Now update ALL invoice items to ensure consistent status
        $updateAllItemsSql = "UPDATE invoice_items SET 
                            status = ?, 
                            pay_status = ? 
                            WHERE invoice_id = ?";
        $stmt = $conn->prepare($updateAllItemsSql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error . " for query: " . $updateAllItemsSql);
        }
        $stmt->bind_param("ssi", $invoice_status, $pay_status, $invoice_id);
        $stmt->execute();
        
        // Handle payment record if pay_status is 'paid'
        if ($pay_status == 'paid') {
            // Check if payment exists
            $checkPaymentSql = "SELECT COUNT(*) as count FROM payments WHERE invoice_id = ?";
            $stmt = $conn->prepare($checkPaymentSql);
            $stmt->bind_param("i", $invoice_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment_exists = $result->fetch_assoc()['count'] > 0;
            
            if (!$payment_exists) {
                // Create a payment record
                $insertPaymentSql = "INSERT INTO payments (invoice_id, amount_paid, payment_date, payment_method, pay_by) 
                                    VALUES (?, ?, NOW(), 'Cash', ?)";
                $stmt = $conn->prepare($insertPaymentSql);
                $stmt->bind_param("idi", $invoice_id, $total_amount, $user_id);
                $stmt->execute();
            }
        }
        
        // Commit transaction if everything went well
        $conn->commit();
        
        // Set success message and redirect
        $_SESSION['success'] = "Invoice #$invoice_id has been updated successfully! Status: $invoice_status, Payment: $pay_status";
        header("Location: view_invoice.php?id=$invoice_id");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['error'] = "Error updating invoice: " . $e->getMessage();
        header("Location: edit_invoice.php?id=$invoice_id");
        exit;
    }
    
} else {
    // If not POST request, redirect to invoices page
    header("Location: invoices.php");
    exit;
}
?>