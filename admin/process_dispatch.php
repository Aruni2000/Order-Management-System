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
    if(isset($_POST['order_id']) && !empty($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $tracking_number = isset($_POST['tracking_number']) ? trim($_POST['tracking_number']) : '';
        $courier_id = isset($_POST['carrier']) && !empty($_POST['carrier']) ? intval($_POST['carrier']) : null;
        $dispatch_notes = isset($_POST['dispatch_notes']) ? trim($_POST['dispatch_notes']) : '';
        $user_id = $_SESSION['user_id'] ?? 0; // Get current user ID if available
        
        // Validate inputs
        if($courier_id === null) {
            $response['message'] = 'Please select a valid courier';
            echo json_encode($response);
            exit;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. Update the order header
            $query1 = "UPDATE order_header SET 
                      status = 'dispatch',
                      tracking_number = ?,
                      courier_id = ?,
                      notes = CONCAT(IFNULL(notes, ''), '\nDispatch Notes: ', ?)
                      WHERE order_id = ?";
            
            $stmt1 = $conn->prepare($query1);
            $stmt1->bind_param("sisi", $tracking_number, $courier_id, $dispatch_notes, $order_id);
            $result1 = $stmt1->execute();
            
            if (!$result1) {
                throw new Exception("Failed to update order header: " . $conn->error);
            }
            
            // 2. Update all related order items
            $query2 = "UPDATE order_items SET 
                      status = 'dispatch'
                      WHERE order_id = ?";
            
            $stmt2 = $conn->prepare($query2);
            $stmt2->bind_param("i", $order_id);
            $result2 = $stmt2->execute();
            
            if (!$result2) {
                throw new Exception("Failed to update order items: " . $conn->error);
            }
            
            // 3. Log the dispatch action (optional)
            // You could add a log entry to an activity_log table if you have one
            if (function_exists('logActivity')) {
                logActivity($user_id, "Order #$order_id marked as dispatched", "order_dispatch");
            }
            
            // Commit transaction
            $conn->commit();
            
            $response['status'] = 'success';
            $response['message'] = 'Order has been dispatched successfully';
            $response['order_id'] = $order_id;
            
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
        
    } else {
        $response['message'] = 'Order ID is required';
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