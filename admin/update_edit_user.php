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

// Include necessary files
include 'db_connection.php';
include 'functions.php';

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: users.php");
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid security token. Please try again.";
    header("Location: users.php");
    exit();
}

// Basic validation of required fields
$required_fields = ['name', 'email', 'status', 'role_id', 'user_id', 'commission_type'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $_SESSION['error_message'] = ucfirst($field) . " is required.";
        header("Location: users.php");
        exit();
    }
}

// Sanitize and validate inputs
$user_id = intval($_POST['user_id']);
$name = trim($_POST['name']);
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$status = strtolower(trim($_POST['status']));
$role_id = intval($_POST['role_id']);
$mobile = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$nic = isset($_POST['nic']) ? trim($_POST['nic']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Commission fields
$commission_type = trim($_POST['commission_type']);
$commission_per_parcel = 0.00;
$percentage_drawdown = 0.00;

// Validate commission type
if (!in_array($commission_type, ['fixed', 'percentage', 'none'])) {
    $_SESSION['error_message'] = "Invalid commission type.";
    header("Location: users.php");
    exit();
}

// Process commission values based on type
if ($commission_type === 'fixed') {
    $commission_per_parcel = filter_var($_POST['commission_per_parcel'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $commission_per_parcel = is_numeric($commission_per_parcel) ? floatval($commission_per_parcel) : 0.00;
    // Validate commission per parcel
    if ($commission_per_parcel < 0) {
        $_SESSION['error_message'] = "Commission per parcel cannot be negative.";
        header("Location: users.php");
        exit();
    }
} elseif ($commission_type === 'percentage') {
    $percentage_drawdown = filter_var($_POST['percentage_drawdown'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $percentage_drawdown = is_numeric($percentage_drawdown) ? floatval($percentage_drawdown) : 0.00;
    // Validate percentage drawdown
    if ($percentage_drawdown < 0 || $percentage_drawdown > 100) {
        $_SESSION['error_message'] = "Percentage drawdown must be between 0 and 100.";
        header("Location: users.php");
        exit();
    }
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = "Invalid email format.";
    header("Location: users.php");
    exit();
}

// Check for duplicate email (excluding current user when editing)
$email_check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
$email_stmt = $conn->prepare($email_check_sql);
$email_stmt->bind_param("si", $email, $user_id);
$email_stmt->execute();
$email_result = $email_stmt->get_result();

if ($email_result->num_rows > 0) {
    $_SESSION['error_message'] = "Email address is already in use by another user.";
    header("Location: users.php");
    $email_stmt->close();
    exit();
}
$email_stmt->close();

// Validate status
if ($status !== 'active' && $status !== 'inactive') {
    $_SESSION['error_message'] = "Invalid status value.";
    header("Location: users.php");
    exit();
}

// Check if role exists
$role_check_stmt = $conn->prepare("SELECT id FROM roles WHERE id = ?");
$role_check_stmt->bind_param("i", $role_id);
$role_check_stmt->execute();
$role_result = $role_check_stmt->get_result();

if ($role_result->num_rows === 0) {
    $_SESSION['error_message'] = "Selected role does not exist.";
    header("Location: users.php");
    $role_check_stmt->close();
    exit();
}
$role_check_stmt->close();

// Handle profile image upload if provided
$profile_image = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    $file_type = $_FILES['profile_image']['type'];
    $file_size = $_FILES['profile_image']['size'];
    
    // Validate file type and size
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error_message'] = "Only JPG, PNG, and GIF images are allowed.";
        header("Location: users.php");
        exit();
    }
    
    if ($file_size > $max_size) {
        $_SESSION['error_message'] = "Image size should not exceed 2MB.";
        header("Location: users.php");
        exit();
    }
    
    // Create unique filename
    $upload_dir = 'uploads/profiles/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $profile_image = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $profile_image;
    
    // Move the uploaded file
    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
        $_SESSION['error_message'] = "Failed to upload image. Please try again.";
        header("Location: users.php");
        exit();
    }
}

// Prepare database operation for updating user
try {
    $conn->begin_transaction();
    
    // Check if user exists
    $user_check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $user_check_stmt->bind_param("i", $user_id);
    $user_check_stmt->execute();
    
    if ($user_check_stmt->get_result()->num_rows === 0) {
        throw new Exception("User not found.");
    }
    $user_check_stmt->close();
    
    // Fetch the original user data to track changes
    $original_user_stmt = $conn->prepare("
        SELECT name, email, status, role_id, mobile, nic, address, profile_image,
               commission_type, commission_per_parcel, percentage_drawdown
        FROM users WHERE id = ?
    ");
    $original_user_stmt->bind_param("i", $user_id);
    $original_user_stmt->execute();
    $original_result = $original_user_stmt->get_result();
    $original_user = $original_result->fetch_assoc();
    $original_user_stmt->close();
    
    // Track changes
    $changes = [];
    if ($original_user['name'] !== $name) {
        $changes[] = "Name changed from '{$original_user['name']}' to '{$name}'";
    }
    if ($original_user['email'] !== $email) {
        $changes[] = "Email changed from '{$original_user['email']}' to '{$email}'";
    }
    if (!empty($password)) {
        $changes[] = "Password was updated";
    }
    if ($original_user['status'] !== $status) {
        $changes[] = "Status changed from '{$original_user['status']}' to '{$status}'";
    }
    if ($original_user['role_id'] != $role_id) {
        // Get role names for better logging
        $role_names_stmt = $conn->prepare("
            SELECT r1.name as old_role, r2.name as new_role 
            FROM roles r1, roles r2 
            WHERE r1.id = ? AND r2.id = ?
        ");
        $role_names_stmt->bind_param("ii", $original_user['role_id'], $role_id);
        $role_names_stmt->execute();
        $role_result = $role_names_stmt->get_result();
        $roles = $role_result->fetch_assoc();
        $role_names_stmt->close();
        
        $changes[] = "Role changed from '{$roles['old_role']}' to '{$roles['new_role']}'";
    }
    if ($original_user['mobile'] !== $mobile) {
        $changes[] = "Mobile changed from '{$original_user['mobile']}' to '{$mobile}'";
    }
    if ($original_user['nic'] !== $nic) {
        $changes[] = "NIC changed from '{$original_user['nic']}' to '{$nic}'";
    }
    if ($original_user['address'] !== $address) {
        $changes[] = "Address was updated";
    }
    if ($profile_image !== null) {
        $changes[] = "Profile image was updated";
    }
    
    // Track commission changes
    $original_commission_type = isset($original_user['commission_type']) ? $original_user['commission_type'] : 'none';
    $original_commission_per_parcel = isset($original_user['commission_per_parcel']) ? floatval($original_user['commission_per_parcel']) : 0.00;
    $original_percentage_drawdown = isset($original_user['percentage_drawdown']) ? floatval($original_user['percentage_drawdown']) : 0.00;
    
    if ($original_commission_type !== $commission_type) {
        $changes[] = "Commission type changed from '{$original_commission_type}' to '{$commission_type}'";
    }
    
    if ($original_commission_per_parcel != $commission_per_parcel) {
        $changes[] = "Commission per parcel changed from '{$original_commission_per_parcel}' to '{$commission_per_parcel}'";
    }
    
    if ($original_percentage_drawdown != $percentage_drawdown) {
        $changes[] = "Percentage drawdown changed from '{$original_percentage_drawdown}%' to '{$percentage_drawdown}%'";
    }
    
    // Prepare SQL based on whether password is being updated
    if (!empty($password)) {
        // Hash the password if it's being updated
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        if ($profile_image) {
            // Update with new password and profile image
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET name = ?, email = ?, password = ?, status = ?, role_id = ?, 
                    mobile = ?, nic = ?, address = ?, profile_image = ?, 
                    commission_type = ?, commission_per_parcel = ?, percentage_drawdown = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $update_stmt->bind_param("ssssissssddi", $name, $email, $hashed_password, $status, $role_id, 
                                    $mobile, $nic, $address, $profile_image, 
                                    $commission_type, $commission_per_parcel, $percentage_drawdown,
                                    $user_id);
        } else {
            // Update with new password, keep existing profile image
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET name = ?, email = ?, password = ?, status = ?, role_id = ?, 
                    mobile = ?, nic = ?, address = ?, 
                    commission_type = ?, commission_per_parcel = ?, percentage_drawdown = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $update_stmt->bind_param("ssssisssddi", $name, $email, $hashed_password, $status, $role_id, 
                                    $mobile, $nic, $address, 
                                    $commission_type, $commission_per_parcel, $percentage_drawdown,
                                    $user_id);
        }
    } else {
        // Don't update password
        if ($profile_image) {
            // Update with new profile image
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET name = ?, email = ?, status = ?, role_id = ?, 
                    mobile = ?, nic = ?, address = ?, profile_image = ?, 
                    commission_type = ?, commission_per_parcel = ?, percentage_drawdown = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $update_stmt->bind_param("ssissssddi", $name, $email, $status, $role_id, 
                                    $mobile, $nic, $address, $profile_image, 
                                    $commission_type, $commission_per_parcel, $percentage_drawdown,
                                    $user_id);
        } else {
            // Update without changing password or profile image
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET name = ?, email = ?, status = ?, role_id = ?, 
                    mobile = ?, nic = ?, address = ?,
                    commission_type = ?, commission_per_parcel = ?, percentage_drawdown = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");

            $update_stmt->bind_param("ssissssddii", $name, $email, $status, $role_id, 
            $mobile, $nic, $address, 
            $commission_type, $commission_per_parcel, $percentage_drawdown,
            $user_id);
        }
        
    }
    
    $update_stmt->execute();
    
    if ($update_stmt->affected_rows <= 0 && $update_stmt->error) {
        throw new Exception("Database error: " . $update_stmt->error);
    }
    
    $update_stmt->close();
    
    // Log the user edit action with detailed changes
    $logged_in_user_id = $_SESSION['user_id']; // Adjust if your session variable uses a different name
    $action_type = 'edit_user';
    $inquiry_id = 0; // or NULL if your table allows NULL values for this field
    
    // Prepare the details message
    $change_details = !empty($changes) ? 
        implode("; ", $changes) : 
        "No fields were changed";
        
    $details = "User ID #{$user_id} ({$name}) was updated by user ID #{$logged_in_user_id}. Changes: {$change_details}";
    $created_at = date('Y-m-d H:i:s');

    // Insert into user_logs table
    $log_stmt = $conn->prepare("
        INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $log_stmt->bind_param("isiss", $logged_in_user_id, $action_type, $inquiry_id, $details, $created_at);
    $log_result = $log_stmt->execute();

    // Check if logging failed but don't stop the transaction for logging failures
    if (!$log_result) {
        error_log("Failed to log user edit action: " . $log_stmt->error);
    }
    $log_stmt->close();
    
    $conn->commit();
    
    $_SESSION['success_message'] = "User updated successfully.";
    
    // Changed redirect to edit_user.php with user_id parameter
    header("Location: edit_user.php?id=" . $user_id);
    
    // If we reach here, the header redirect didn't work
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
    <script>
        // JavaScript fallback redirect - updated to edit_user.php
        window.location.href = "edit_user.php?id=<?php echo $user_id; ?>";
    </script>
</head>
<body>
    <p>If you are not redirected automatically, please <a href="edit_user.php?id=<?php echo $user_id; ?>">click here</a>.</p>
</body>
</html>
<?php
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    
    // Try PHP redirect first - keep users.php for errors
    header("Location: users.php");
    
    // If we reach here, the header redirect didn't work
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
    <script>
        // JavaScript fallback redirect
        window.location.href = "users.php";
    </script>
</head>
<body>
    <p>If you are not redirected automatically, please <a href="users.php">click here</a>.</p>
</body>
</html>
<?php
    exit();
}

// Close the connection at the end of the script
$conn->close();
?>