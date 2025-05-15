<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Force redirect to login page
    header("Location: signin.php");
    exit(); // Stop execution immediately
}

// Include the database connection file
include 'db_connection.php';
include 'functions.php'; // Include helper functions

// Set content type header to JSON
header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'Unknown error occurred');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['order_id']) && !empty($_POST['order_id']) && isset($_POST['carrier']) && !empty($_POST['carrier'])) {
        $order_id = intval($_POST['order_id']);
        $courier_id = intval($_POST['carrier']);
        $dispatch_notes = isset($_POST['dispatch_notes']) ? trim($_POST['dispatch_notes']) : '';
        $user_id = $_SESSION['user_id'] ?? 0; // Get current user ID if available
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Get an available tracking number from the tracking table
            $query_tracking = "SELECT tracking_id FROM tracking 
                              WHERE courier_id = ? AND status = 'unused' 
                              ORDER BY created_at ASC LIMIT 1";
            
            $stmt_tracking = $conn->prepare($query_tracking);
            $stmt_tracking->bind_param("i", $courier_id);
            $stmt_tracking->execute();
            $tracking_result = $stmt_tracking->get_result();
            
            if ($tracking_result->num_rows == 0) {
                throw new Exception("No available tracking numbers for the selected courier");
            }
            
            $tracking_row = $tracking_result->fetch_assoc();
            $tracking_number = $tracking_row['tracking_id'];
            
            // 2. Update the tracking number status to 'used'
            $update_tracking = "UPDATE tracking SET 
                               status = 'used'
                               WHERE tracking_id = ? AND courier_id = ?";
            
            $stmt_update = $conn->prepare($update_tracking);
            $stmt_update->bind_param("si", $tracking_number, $courier_id);
            $result_update = $stmt_update->execute();
            
            if (!$result_update) {
                throw new Exception("Failed to update tracking status: " . $conn->error);
            }
            
            // 3. Update the order header with the tracking information
            $query_order = "UPDATE order_header SET 
                          status = 'dispatch',
                          tracking_number = ?,
                          courier_id = ?,
                          notes = CONCAT(IFNULL(notes, ''), '\nDispatch Notes: ', ?)
                          WHERE order_id = ?";
            
            $stmt_order = $conn->prepare($query_order);
            $stmt_order->bind_param("sisi", $tracking_number, $courier_id, $dispatch_notes, $order_id);
            $result_order = $stmt_order->execute();
            
            if (!$result_order) {
                throw new Exception("Failed to update order header: " . $conn->error);
            }
            
            // 4. Update all related order items
            $query_items = "UPDATE order_items SET 
                          status = 'dispatch'
                          WHERE order_id = ?";
            
            $stmt_items = $conn->prepare($query_items);
            $stmt_items->bind_param("i", $order_id);
            $result_items = $stmt_items->execute();
            
            if (!$result_items) {
                throw new Exception("Failed to update order items: " . $conn->error);
            }
            
            // 5. Log the dispatch action to user_logs table
            $action_type = "dispatch_order";
            
            // Get customer name for the log
            $customer_query = "SELECT c.name as customer_name 
                              FROM order_header o 
                              LEFT JOIN customers c ON o.customer_id = c.customer_id
                              WHERE o.order_id = ?";
            $stmt_customer = $conn->prepare($customer_query);
            $stmt_customer->bind_param("i", $order_id);
            $stmt_customer->execute();
            $customer_result = $stmt_customer->get_result();
            $customer_name = "Unknown Customer";
            
            if ($customer_result->num_rows > 0) {
                $customer_data = $customer_result->fetch_assoc();
                $customer_name = $customer_data['customer_name'];
            }
            
            $details = "Order ID #$order_id for customer ($customer_name) was dispatched by user ID #$user_id. Tracking: $tracking_number";
            
            $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                       VALUES (?, ?, ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("isss", $user_id, $action_type, $order_id, $details);
            $log_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            $response['status'] = 'success';
            $response['message'] = 'Order has been dispatched successfully';
            $response['order_id'] = $order_id;
            $response['tracking_number'] = $tracking_number;
            
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        
    } else {
        $response['message'] = 'Order ID and courier are required';
    }
} else {
    $response['message'] = 'Invalid request method';
}

// Close the database connection
$conn->close();

// Return the response as JSON
echo json_encode($response);
exit;
?>