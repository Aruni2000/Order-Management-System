<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

// Include database connection
include 'db_connection.php';
include 'functions.php';

// Check if the form was submitted with the mark_as_paid flag
if (isset($_POST['mark_as_paid']) && isset($_POST['order_id'])) {
    // Get form data
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id']; // Current logged-in user
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Get order details for logging
        $order_sql = "SELECT c.name, i.customer_id, i.total_amount, i.currency 
                      FROM order_header i 
                      LEFT JOIN customers c ON i.customer_id = c.customer_id
                      WHERE i.order_id = ?";
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $order_result = $stmt->get_result();
        
        if ($order_result->num_rows === 0) {
            throw new Exception("Order not found");
        }
        
        $order_data = $order_result->fetch_assoc();
        $customer_name = $order_data['name'] ?? 'Unknown Customer';
        $customer_id = $order_data['customer_id'] ?? '';
        $amount = $order_data['total_amount'] ?? 0;
        $currency = $order_data['currency'] ?? 'lkr';
        
        // Current date 
        $currentDate = date('Y-m-d');
        $currentDateTime = date('Y-m-d H:i:s');
        
        // 2. Handle payment slip upload if provided
        $slip_filename = null;
        if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['payment_slip'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validate file extension
            $extensions = ["jpeg", "jpg", "png", "pdf"];
            if (!in_array($file_ext, $extensions)) {
                throw new Exception("Extension not allowed. Please upload a JPG, JPEG, PNG or PDF file.");
            }
            
            // Validate file size (max 2MB)
            $max_size = 2 * 1024 * 1024; // 2MB
            if ($file['size'] > $max_size) {
                throw new Exception("File size is too large. Maximum file size is 2MB.");
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = 'uploads/payments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Create a unique filename
            $slip_filename = 'payment_slip_' . $order_id . '_' . time() . '.' . $file_ext;
            $uploadPath = $uploadDir . $slip_filename;
            
            // Move the uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to upload file. Please try again.");
            }
        } else {
            throw new Exception("Payment slip is required");
        }
        
        // 3. Update order status to paid but KEEP the 'dispatch' status
        $update_sql = "UPDATE order_header SET pay_status = 'paid', pay_date = ?, slip = ?, pay_by = ? WHERE order_id = ? AND status = 'dispatch'";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssis", $currentDate, $slip_filename, $user_id, $order_id);
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows === 0) {
            throw new Exception("Failed to update order or order is not in dispatch status");
        }
        
        // 4. Update order_items table - keep status as 'dispatch' but set pay_status to 'paid'
        $items_sql = "UPDATE order_items SET pay_status = 'paid' WHERE order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        
        // 5. Insert payment record
        $payment_method = 'Cash'; // Default payment method
        
        $payment_sql = "INSERT INTO payments (order_id, amount_paid, payment_method, payment_date, pay_by) 
                        VALUES (?, ?, ?, ?, ?)";
        
        $pay_stmt = $conn->prepare($payment_sql);
        $pay_stmt->bind_param("idssi", 
            $order_id, 
            $amount, 
            $payment_method, 
            $currentDateTime, 
            $user_id
        );
        $pay_stmt->execute();
        
        // 6. Log the payment action if user_logs table exists
        try {
            $action_type = "mark_as_paid";
            $details = "Order ID #$order_id for customer ($customer_name) was marked as paid by user ID #$user_id. Amount: $amount";
            
            $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                       VALUES (?, ?, ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("isss", $user_id, $action_type, $order_id, $details);
            $log_stmt->execute();
        } catch (Exception $logError) {
            // If logging fails, continue anyway
            // This is non-critical and shouldn't prevent the payment from being processed
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Return success response
        echo json_encode([
            'status' => 'success', 
            'message' => "Order #$order_id has been marked as paid successfully."
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction if something fails
        $conn->rollback();
        
        // Return error response
        echo json_encode([
            'status' => 'error', 
            'message' => "Failed to mark order as paid: " . $e->getMessage()
        ]);
    }
} else {
    // Return error if required parameters are missing
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request parameters'
    ]);
}