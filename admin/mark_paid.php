<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
include 'db_connection.php';

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get order ID
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if ($order_id <= 0) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid order ID']);
        exit();
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['payment_slip']) || $_FILES['payment_slip']['error'] !== UPLOAD_ERR_OK) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Payment slip is required']);
        exit();
    }
    
    // Process the uploaded file
    $file = $_FILES['payment_slip'];
    $filename = 'payment_slip_' . $order_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $uploadDir = 'uploads/payments/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $filename;
    
    // Get current user ID from session
    $pay_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload payment slip');
        }
        
        // Get the order amount from the order_header table
        $amountQuery = "SELECT total_amount FROM order_header WHERE order_id = ?";
        $amountStmt = $conn->prepare($amountQuery);
        $amountStmt->bind_param("i", $order_id);
        $amountStmt->execute();
        $amountResult = $amountStmt->get_result();
        
        if ($amountResult->num_rows === 0) {
            throw new Exception('Order not found');
        }
        
        $orderData = $amountResult->fetch_assoc();
        $amount_paid = $orderData['total_amount'];
        
        // Current date and time
        $currentDateTime = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d');
        
        // Default payment method
        $payment_method = 'Cash'; // You can modify this if you collect payment method from the form
        
        // Update order status in the order_header table
        $orderStmt = $conn->prepare("UPDATE order_header SET pay_status = 'paid', pay_date = ?, slip = ?, status = 'done', pay_by = ? WHERE order_id = ?");
        $orderStmt->bind_param("ssis", $currentDate, $filename, $pay_by, $order_id);
        if (!$orderStmt->execute()) {
            throw new Exception('Failed to update order: ' . $conn->error);
        }
        
        // Update order_items table
        $itemsStmt = $conn->prepare("UPDATE order_items SET status = 'done', pay_status = 'paid' WHERE order_id = ?");
        $itemsStmt->bind_param("i", $order_id);
        if (!$itemsStmt->execute()) {
            throw new Exception('Failed to update order items: ' . $conn->error);
        }
        
        // Insert payment record into payments table
        $paymentStmt = $conn->prepare("INSERT INTO payments (order_id, amount_paid, payment_method, payment_date, pay_by) VALUES (?, ?, ?, ?, ?)");
        $paymentStmt->bind_param("idssi", $order_id, $amount_paid, $payment_method, $currentDateTime, $pay_by);
        if (!$paymentStmt->execute()) {
            throw new Exception('Failed to record payment: ' . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
}
?>