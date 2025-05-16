<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the very beginning
session_start();

// Set content type header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if required parameters exist
if (!isset($_POST['order_id']) || !isset($_POST['dispatch_note'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$order_id = intval($_POST['order_id']);
$dispatch_note = trim($_POST['dispatch_note']);

// Include database connection if not already included
if (!isset($conn) || !($conn instanceof mysqli)) {
    // Try to include the database connection file
    if (file_exists('db_connection.php')) {
        include 'db_connection.php';
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection file not found'
        ]);
        exit;
    }
}

// Check if database connection exists
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection could not be established'
    ]);
    exit;
}

try {
    // Update the dispatch_note in the order_header table
    $query = "UPDATE order_header SET dispatch_note = ? WHERE order_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("SQL preparation error: " . $conn->error);
    }
    
    $stmt->bind_param("si", $dispatch_note, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception("SQL execution error: " . $stmt->error);
    }
    
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Dispatch notes updated successfully'
        ]);
    } else {
        // No rows were updated, but this might just mean the value didn't change
        // Check if the order exists
        $checkQuery = "SELECT order_id FROM order_header WHERE order_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $order_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'No changes were made to the dispatch notes'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

exit;
?>