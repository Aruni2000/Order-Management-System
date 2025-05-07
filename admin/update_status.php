<?php
// update_status.php
header('Content-Type: application/json');

try {
    // Include the database connection file
    include 'db_connection.php';
    
    // Start session to get current user info
    session_start();
    
    // Ensure we have a valid connection using the same variable as in the main file
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Connection failed: " . ($conn->connect_error ?? "Database connection not established"));
    }

    // Validate inputs
    if (!isset($_POST['id']) || !isset($_POST['status'])) {
        throw new Exception("Missing required parameters");
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    // Use sanitize_full_special_chars as a replacement for the deprecated FILTER_SANITIZE_STRING
    $status = htmlspecialchars($_POST['status'], ENT_QUOTES, 'UTF-8');

    if (!$id) {
        throw new Exception("Invalid inquiry ID");
    }

    if (!in_array($status, ['approved', 'rejected'])) {
        throw new Exception("Invalid status value");
    }

    // Check current status
    $checkStmt = $conn->prepare("SELECT status FROM user_form_data WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Inquiry not found");
    }

    $currentData = $result->fetch_assoc();
    // Only check if you have a 'status' column in your table
    // If you don't have this column yet, you might need to add it
    if (isset($currentData['status']) && in_array($currentData['status'], ['approved', 'rejected'])) {
        throw new Exception("This inquiry has already been processed");
    }

    $checkStmt->close();

    // Begin transaction to ensure both operations complete successfully
    $conn->begin_transaction();

    // Update status
    $updateStmt = $conn->prepare("UPDATE user_form_data SET status = ? WHERE id = ?");
    $updateStmt->bind_param("si", $status, $id);

    if (!$updateStmt->execute()) {
        $conn->rollback();
        throw new Exception("Failed to update status: " . $conn->error);
    }

    $updateStmt->close();
    
    // Get current user ID from session
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Create log entry
    $actionType = ($status === 'approved') ? 'approve_inquiry' : 'reject_inquiry';
    $details = "Inquiry ID #$id was $status by user ID #$userId";
    
    // Insert into user_logs table
    $logStmt = $conn->prepare("INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())");
    $logStmt->bind_param("isis", $userId, $actionType, $id, $details);
    
    if (!$logStmt->execute()) {
        $conn->rollback();
        throw new Exception("Failed to create log entry: " . $conn->error);
    }
    
    $logStmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Don't close the connection here if other parts of the app might use it
    // $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'status' => $status // Include the status in the response
    ]);

} catch (Exception $e) {
    // If a transaction is active, roll it back
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>