<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the very beginning
session_start();

// Set content type header to JSON
header('Content-Type: application/json');

// Check if order_id parameter exists
if (!isset($_GET['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing order ID parameter'
    ]);
    exit;
}

$order_id = intval($_GET['order_id']);

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
    // Get order information with courier details and user information by joining the necessary tables
    $query = "SELECT oh.order_id, oh.tracking_number, oh.courier_id, 
                     oh.dispatch_note, oh.notes, oh.created_at as dispatch_date,
                     oh.created_by as processed_by_id, c.courier_name,
                     u.name as processed_by_name
              FROM order_header oh
              LEFT JOIN couriers c ON oh.courier_id = c.courier_id
              LEFT JOIN users u ON oh.created_by = u.id
              WHERE oh.order_id = ?";
              
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("SQL preparation error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception("SQL execution error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ]);
        exit;
    }
    
    // Format the processed_by field to include both name and ID
    $processed_by = $data['processed_by_name'] ? $data['processed_by_name'] . ' (' . $data['processed_by_id'] . ')' : $data['processed_by_id'];
    
    // Format the dispatch notes properly
    $dispatch_notes = !empty($data['dispatch_note']) ? $data['dispatch_note'] : 
                     (!empty($data['notes']) ? $data['notes'] : 'No dispatch notes available');
    
    // Format the response
    $response = [
        'success' => true,
        'order_id' => $order_id,
        'tracking_number' => $data['tracking_number'],
        'courier_id' => $data['courier_id'],
        'courier_name' => $data['courier_name'],
        'dispatch_date' => $data['dispatch_date'],
        'processed_by' => $processed_by,
        'dispatch_notes' => $dispatch_notes
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

exit;
?>