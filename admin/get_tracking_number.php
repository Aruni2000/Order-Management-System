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

// Set content type header to JSON
header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'Unknown error occurred');

if(isset($_GET['courier_id']) && !empty($_GET['courier_id'])) {
    $courier_id = intval($_GET['courier_id']);
    
    // Check if there are available tracking numbers for this courier
    $query = "SELECT COUNT(*) as available_count FROM tracking 
              WHERE courier_id = ? AND status = 'unused'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $courier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $available_count = $row['available_count'] ?? 0;
    
    if($available_count > 0) {
        // Get a sample tracking number without actually reserving it yet
        $query = "SELECT tracking_id FROM tracking 
                  WHERE courier_id = ? AND status = 'unused' 
                  ORDER BY created_at ASC LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $courier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $tracking_number = $row['tracking_id'];
        
        $response = array(
            'status' => 'success',
            'tracking_number' => $tracking_number,
            'message' => 'Tracking number available'
        );
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'No available tracking numbers for the selected courier'
        );
    }
} else {
    $response = array(
        'status' => 'error',
        'message' => 'Courier ID is required'
    );
}

// Close the database connection
$conn->close();

// Return the response as JSON
echo json_encode($response);
exit;
?>