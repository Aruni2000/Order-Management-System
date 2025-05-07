<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: signin.php");
    exit();
}

// Include database connection
include 'db_connection.php';

// First, check if profile_image column exists, if not, create it
$checkColumnSql = "SHOW COLUMNS FROM users LIKE 'profile_image'";
$columnResult = $conn->query($checkColumnSql);

if ($columnResult->num_rows === 0) {
    // Column doesn't exist, create it
    $alterTableSql = "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255)";
    if (!$conn->query($alterTableSql)) {
        $_SESSION['error_message'] = "Error creating profile_image column: " . $conn->error;
        header("Location: profile.php");
        exit();
    }
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    // Get user ID from session
    $userId = $_SESSION['user_id'];
    
    // Set upload directory
    $uploadDir = 'uploads/profiles/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // File information
    $file = $_FILES['profile_picture'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Check if file extension is allowed
    if (in_array($fileExt, $allowedExtensions)) {
        // Check if there was no error during upload
        if ($fileError === 0) {
            // Check file size (max 2MB)
            if ($fileSize < 2000000) {
                // Generate unique filename
                $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExt;
                $destination = $uploadDir . $newFileName;
                
                // Move uploaded file to destination
                if (move_uploaded_file($fileTmpName, $destination)) {
                    // Check if there's an old image to delete
                    $oldImageSql = "SELECT profile_image FROM users WHERE id = ?";
                    $oldImageStmt = $conn->prepare($oldImageSql);
                    
                    if ($oldImageStmt) {
                        $oldImageStmt->bind_param("i", $userId);
                        $oldImageStmt->execute();
                        $oldImageResult = $oldImageStmt->get_result();
                        
                        if ($oldImageResult->num_rows === 1) {
                            $user = $oldImageResult->fetch_assoc();
                            $oldImage = $user['profile_image'];
                            
                            if (!empty($oldImage) && file_exists($uploadDir . $oldImage)) {
                                unlink($uploadDir . $oldImage);
                            }
                        }
                        
                        $oldImageStmt->close();
                    }
                    
                    // Update profile image in database
                    $updateSql = "UPDATE users SET profile_image = ? WHERE id = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    
                    if ($updateStmt === false) {
                        $_SESSION['error_message'] = "Error preparing update statement: " . $conn->error;
                        header("Location: profile.php");
                        exit();
                    }
                    
                    $updateStmt->bind_param("si", $newFileName, $userId);
                    
                    if ($updateStmt->execute()) {
                        $_SESSION['success_message'] = "Profile picture updated successfully!";
                    } else {
                        $_SESSION['error_message'] = "Error updating profile picture in database: " . $updateStmt->error;
                    }
                    
                    $updateStmt->close();
                } else {
                    $_SESSION['error_message'] = "Error moving uploaded file!";
                }
            } else {
                $_SESSION['error_message'] = "File size exceeds the maximum limit (2MB)!";
            }
        } else {
            $_SESSION['error_message'] = "Error uploading file! Error code: " . $fileError;
        }
    } else {
        $_SESSION['error_message'] = "Invalid file type! Allowed types: JPG, JPEG, PNG, GIF";
    }
    
    header("Location: profile.php");
    exit();
}

// If not POST request or no file uploaded, redirect to profile page
$_SESSION['error_message'] = "No file uploaded or invalid request!";
header("Location: profile.php");
exit();
?>