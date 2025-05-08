<?php
// This file processes the uploaded lead file and inserts data into order_header table

// Start session at the very beginning only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: signin.php");
    exit();
}

// Include the database connection file
include 'db_connection.php';


/**
 * Process lead import file and insert into order_header
 * @param string $file_path Path to the uploaded file
 * @param int $user_id User ID who is importing the file
 * @return array Result with status and message
 */
function processLeadFile($file_path, $user_id) {
    global $conn;
    
    $result = [
        'success' => false,
        'message' => '',
        'total' => 0,
        'successful' => 0,
        'failed' => 0,
        'failures' => []
    ];
    
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    
        
        // Check if all required fields are present
        foreach ($requiredFields as $field) {
            if (!isset($columnMap[$field])) {
                return [
                    'success' => false,
                    'message' => "Missing required column: $field"
                ];
            }
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        $total = count($rows);
        $successful = 0;
        $failed = 0;
        $failures = [];
        
        // Process each row
        foreach ($rows as $rowIndex => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Extract data from row
            $fullName = trim($row[$columnMap['full name']]);
            $city = trim($row[$columnMap['city']]);
            $phoneNumber = trim($row[$columnMap['phone number']]);
            $productCode = trim($row[$columnMap['product code']]);
            $other = isset($columnMap['other']) ? trim($row[$columnMap['other']]) : '';
            
            // Skip if any required field is empty
            if (empty($fullName) || empty($phoneNumber) || empty($productCode)) {
                $failed++;
                $failures[] = "Row " . ($rowIndex + 2) . ": Missing required data";
                continue;
            }
            
            // Check if product code exists
            $product_query = "SELECT id, lkr_price, usd_price FROM products WHERE id = ? OR name = ? LIMIT 1";
            $stmt = $conn->prepare($product_query);
            $stmt->bind_param("ss", $productCode, $productCode);
            $stmt->execute();
            $product_result = $stmt->get_result();
            
            if ($product_result->num_rows == 0) {
                $failed++;
                $failures[] = "Row " . ($rowIndex + 2) . ": Product not found: $productCode";
                $stmt->close();
                continue;
            }
            
            $product = $product_result->fetch_assoc();
            $product_id = $product['id'];
            $product_price = $product['lkr_price']; // Default to LKR price
            $stmt->close();
            
            // Check if customer exists, if not create a new one
            $customer_id = null;
            $customer_query = "SELECT id FROM customers WHERE name = ? AND phone = ? LIMIT 1";
            $stmt = $conn->prepare($customer_query);
            $stmt->bind_param("ss", $fullName, $phoneNumber);
            $stmt->execute();
            $customer_result = $stmt->get_result();
            
            if ($customer_result->num_rows > 0) {
                $customer = $customer_result->fetch_assoc();
                $customer_id = $customer['id'];
            } else {
                // Create new customer
                $insert_customer = "INSERT INTO customers (name, phone, city, created_at, status) 
                                   VALUES (?, ?, ?, NOW(), 'active')";
                $stmt = $conn->prepare($insert_customer);
                $stmt->bind_param("sss", $fullName, $phoneNumber, $city);
                $stmt->execute();
                $customer_id = $conn->insert_id;
            }
            $stmt->close();
            
            // Create order header
            $today = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+7 days'));
            
            // Insert order header
            $insert_order = "INSERT INTO order_header (
                customer_id, user_id, issue_date, due_date, 
                subtotal, discount, notes, pay_status, 
                pay_by, created_at, created_by, total_amount, 
                currency, status, interface
            ) VALUES (
                ?, ?, ?, ?, 
                ?, 0.00, ?, 'unpaid', 
                'pending', NOW(), ?, ?, 
                'lkr', 'pending', 'leads'
            )";
            
            $stmt = $conn->prepare($insert_order);
            $stmt->bind_param(
                "iissdsdi",
                $customer_id, $user_id, $today, $due_date,
                $product_price, $other, $user_id, $product_price
            );
            
            if ($stmt->execute()) {
                $order_id = $conn->insert_id;
                
                // Add product to order_details table (assuming you have this table)
                // If you don't have this table, you can create it or modify the code as needed
                $insert_detail = "INSERT INTO order_details (
                    order_id, product_id, quantity, unit_price, total_price
                ) VALUES (?, ?, 1, ?, ?)";
                
                $detail_stmt = $conn->prepare($insert_detail);
                $detail_stmt->bind_param("iddd", $order_id, $product_id, $product_price, $product_price);
                
                if ($detail_stmt->execute()) {
                    $successful++;
                } else {
                    // If order details insertion fails, consider the whole row failed
                    $failed++;
                    $failures[] = "Row " . ($rowIndex + 2) . ": Failed to add product details";
                }
                $detail_stmt->close();
            } else {
                $failed++;
                $failures[] = "Row " . ($rowIndex + 2) . ": Failed to create order";
            }
            $stmt->close();
        }
        
        // Commit transaction if everything was successful
        if ($failed == 0 && $successful > 0) {
            $conn->commit();
            $result['success'] = true;
            $result['message'] = "Successfully imported $successful leads.";
        } else if ($successful > 0) {
            $conn->commit();
            $result['success'] = true;
            $result['message'] = "Imported $successful leads with $failed failures.";
        } else {
            $conn->rollback();
            $result['success'] = false;
            $result['message'] = "Failed to import any leads. Please check the file format.";
        }
        
        $result['total'] = $total;
        $result['successful'] = $successful;
        $result['failed'] = $failed;
        $result['failures'] = $failures;
   

/**
 * Process all pending imports
 * This function can be run via cron job or manually
 */
function processAllPendingImports() {
    global $conn;
    
    // Update lead_imports table to create a column for tracking status
    $createTableQuery = "CREATE TABLE IF NOT EXISTS lead_imports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        note TEXT,
        imported_by VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        total_records INT DEFAULT 0,
        successful_records INT DEFAULT 0,
        failed_records INT DEFAULT 0,
        error_message TEXT
    )";
    
    $conn->query($createTableQuery);
    
    // Get all pending imports
    $query = "SELECT * FROM lead_imports WHERE status = 'pending' ORDER BY created_at ASC";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($import = $result->fetch_assoc()) {
            // Mark as processing
            $updateQuery = "UPDATE lead_imports SET status = 'processing' WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $import['id']);
            $stmt->execute();
            $stmt->close();
            
            $file_path = 'uploads/' . $import['file_name'];
            
            // Check if file exists
            if (!file_exists($file_path)) {
                // Mark as failed
                $error = "File not found: " . $import['file_name'];
                $updateQuery = "UPDATE lead_imports SET 
                                status = 'failed', 
                                error_message = ?, 
                                completed_at = NOW() 
                                WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("si", $error, $import['id']);
                $stmt->execute();
                $stmt->close();
                continue;
            }
            
            // Process the file
            $result = processLeadFile($file_path, $import['user_id']);
            
            // Update import status
            if ($result['success']) {
                $updateQuery = "UPDATE lead_imports SET 
                                status = 'completed', 
                                total_records = ?, 
                                successful_records = ?, 
                                failed_records = ?, 
                                completed_at = NOW() 
                                WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("iiii", $result['total'], $result['successful'], $result['failed'], $import['id']);
            } else {
                $updateQuery = "UPDATE lead_imports SET 
                                status = 'failed', 
                                error_message = ?, 
                                completed_at = NOW() 
                                WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("si", $result['message'], $import['id']);
            }
            $stmt->execute();
            $stmt->close();
        }
    }
}

