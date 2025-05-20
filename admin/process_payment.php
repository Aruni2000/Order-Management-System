<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Include the database connection file
include 'db_connection.php';
include 'functions.php'; // Include helper functions

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if order_id is provided
    if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
        $order_id = $_POST['order_id'];
        $user_id = $_SESSION['user_id']; // Current logged-in user ID
        
        // Get order details
        $order_sql = "SELECT o.*, c.name as customer_name FROM order_header o 
                      LEFT JOIN customers c ON o.customer_id = c.customer_id
                      WHERE o.order_id = ?";
        $stmt = $conn->prepare($order_sql);
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order_data = $result->fetch_assoc();
            $customer_name = $order_data['customer_name'];
            $total_amount = $order_data['total_amount'];
            $currency = isset($order_data['currency']) ? $order_data['currency'] : 'lkr';
            
            // Process payment slip upload
            $upload_dir = "uploads/payment_slips/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $payment_slip = null;
            
            // Check if file is uploaded
            if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == 0) {
                $file_tmp = $_FILES['payment_slip']['tmp_name'];
                $file_name = $_FILES['payment_slip']['name'];
                $file_size = $_FILES['payment_slip']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Allowed file extensions
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
                
                // Check file extension
                if (in_array($file_ext, $allowed_extensions)) {
                    // Check file size (max 2MB)
                    if ($file_size <= 2097152) {
                        // Generate a unique filename
                        $new_file_name = "payment_" . $order_id . "_" . date("YmdHis") . "." . $file_ext;
                        $upload_path = $upload_dir . $new_file_name;
                        
                        // Move the uploaded file
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            $payment_slip = $upload_path;
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to upload payment slip']);
                            exit();
                        }
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'File size exceeds the limit (2MB)']);
                        exit();
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid file format. Allowed formats: JPG, JPEG, PNG, PDF']);
                    exit();
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Please upload a payment slip']);
                exit();
            }
            
            // Generate payment ID
            $payment_id = "PAY" . date("YmdHis") . rand(100, 999);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // 1. Insert into payments table
                $payment_sql = "INSERT INTO payments (payment_id, order_id, amount_paid, payment_method, payment_date, pay_by, payment_slip) 
                               VALUES (?, ?, ?, 'Manual Payment', NOW(), ?, ?)";
                $stmt = $conn->prepare($payment_sql);
                $stmt->bind_param("ssdis", $payment_id, $order_id, $total_amount, $user_id, $payment_slip);
                $stmt->execute();
                
                // 2. Update order pay_status to 'paid'
                $update_sql = "UPDATE order_header SET pay_status = 'paid' WHERE order_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("s", $order_id);
                $stmt->execute();
                
                // 3. Log the action in user_logs table
                $action_type = "process_payment";
                $details = "Payment processed for Order ID #$order_id for customer ($customer_name) by user ID #$user_id. Amount: $total_amount " . ($currency == 'usd' ? '$' : 'Rs');
                
                $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                           VALUES (?, ?, ?, ?, NOW())";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("isss", $user_id, $action_type, $order_id, $details);
                $log_stmt->execute();
                
                // Commit the transaction
                $conn->commit();
                
                // Set success message
                echo json_encode(['status' => 'success', 'message' => "Payment for Order #$order_id has been processed successfully."]);
                
            } catch (Exception $e) {
                // Rollback the transaction if something fails
                $conn->rollback();
                
                // Delete uploaded file if transaction failed
                if ($payment_slip && file_exists($payment_slip)) {
                    unlink($payment_slip);
                }
                
                // Return error message
                echo json_encode(['status' => 'error', 'message' => "Failed to process payment. Error: " . $e->getMessage()]);
            }
            
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        }
        
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>