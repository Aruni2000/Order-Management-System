<?php
// Start session at the very beginning
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include the database connection
include 'db_connection.php';

// Get the order ID from the request
$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if (!$orderId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

// Sanitize the input
$orderId = $conn->real_escape_string($orderId);

// Query to get payment details including slip from order_header
$sql = "SELECT o.pay_status, o.pay_by, o.pay_date, o.slip, o.total_amount, 
               CASE 
                   WHEN o.slip IS NOT NULL THEN 'Bank Transfer'
                   ELSE 'Cash'
               END AS payment_method,
               u.name AS processed_by
        FROM order_header o
        LEFT JOIN users u ON o.pay_by = u.id
        WHERE o.order_id = '$orderId'";

$result = $conn->query($sql);

// Format and send the response
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Prepare the response
    $response = [
        'success' => true,
        'payment_method' => $row['payment_method'],
        'amount_paid' => number_format((float)$row['total_amount'], 2) . ' ' . ($row['currency'] ?? 'Rs'),
        'payment_date' => $row['pay_date'] ? date('d/m/Y', strtotime($row['pay_date'])) : 'N/A',
        'processed_by' => $row['processed_by'] ?? 'N/A',
        'slip' => $row['slip'] ?? null
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Payment details not found']);
}

// Close the connection
$conn->close();
?>