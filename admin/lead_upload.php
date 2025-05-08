<?php
// Start session at the very beginning only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Initialize message variables
$errorMsg = '';
$successMsg = '';

// Get current user ID from session
$current_user_id = $_SESSION['user_id'] ?? 1; // Default to 1 if not set

// Fetch all active users for dropdown
$users = [];
$user_query = "SELECT id, name FROM users WHERE status = 'active' ORDER BY name ASC";
$user_result = $conn->query($user_query);
if ($user_result && $user_result->num_rows > 0) {
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Check if form is submitted for file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_leads'])) {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg = "Invalid request. Please try again.";
    } else {
        // Process file upload
        if (isset($_FILES['lead_file']) && $_FILES['lead_file']['error'] == 0) {
            $allowed_ext = ['csv', 'xlsx', 'xls'];
            $file_name = $_FILES['lead_file']['name'];
            $file_size = $_FILES['lead_file']['size'];
            $file_tmp = $_FILES['lead_file']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Get selected user IDs (now can be multiple)
            $selected_user_ids = isset($_POST['user_ids']) ? $_POST['user_ids'] : [$current_user_id];
            
            // Validate file extension
            if (in_array($file_ext, $allowed_ext)) {
                // Validate file size (limit to 5MB)
                if ($file_size <= 5000000) {
                    // Create a unique file name to prevent overwriting
                    $new_file_name = 'lead_import_' . date('Ymd_His') . '.' . $file_ext;
                    $upload_path = 'uploads/' . $new_file_name;
                    
                    // Make sure the uploads directory exists
                    if (!file_exists('uploads')) {
                        mkdir('uploads', 0777, true);
                    }
                    
                    // Move the uploaded file to the uploads directory
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $processed = false;
                        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
                        
                        // Process each selected user
                        foreach ($selected_user_ids as $selected_user_id) {
                            // Get user info from database
                            $user_stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?");
                            $user_stmt->bind_param("i", $selected_user_id);
                            $user_stmt->execute();
                            $user_result = $user_stmt->get_result();
                            $user_data = $user_result->fetch_assoc();
                            $user_stmt->close();
                            
                            $user_name = $user_data['name'] ?? 'Unknown User';
                            
                            // Store upload record in database for each user
                            $stmt = $conn->prepare("INSERT INTO lead_imports (file_name, original_name, note, imported_by, user_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                            $stmt->bind_param("ssssi", $new_file_name, $file_name, $note, $user_name, $selected_user_id);
                            
                            if ($stmt->execute()) {
                                $import_id = $conn->insert_id;
                                $processed = true;
                            } else {
                                $errorMsg = "Error recording import in database: " . $stmt->error;
                                break;
                            }
                            
                            $stmt->close();
                        }
                        
                        if ($processed) {
                            $successMsg = "Lead file uploaded successfully! The import process will begin shortly.";
                            // Clear form fields after successful submission
                            $_POST['note'] = '';
                        }
                    } else {
                        $errorMsg = "Error moving uploaded file.";
                    }
                } else {
                    $errorMsg = "File size is too large. Maximum size is 5MB.";
                }
            } else {
                $errorMsg = "Invalid file type. Only CSV, XLS, and XLSX files are allowed.";
            }
        } else {
            $errorMsg = "Please select a file to upload.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Lead Import</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />

    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .user-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            background-color: #fcfcfc;
        }
        .user-checkbox-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .user-checkbox-item:hover {
            background-color: #f0f7ff;
        }
        .user-checkbox-item input {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .user-checkbox-item label {
            cursor: pointer;
            font-weight: 400;
            margin-bottom: 0;
            user-select: none;
        }
        .download-link {
            display: inline-block;
            margin-bottom: 15px;
            text-decoration: none;
        }
        .file-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .file-name-display {
            flex-grow: 1;
            padding: 6px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        .user-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }
        .user-actions button {
            padding: 4px 12px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <div class="lead-import-header">
                        <h4 class="mt-3">Lead Import</h4>
                        <a class="text-secondary" data-bs-toggle="collapse" href="#importForm" role="button" aria-expanded="true" aria-controls="importForm">
                        </a>
                    </div>
                    
                    <!-- Success/Error Alert -->
                    <?php if (!empty($successMsg)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($successMsg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMsg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($errorMsg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="collapse show" id="importForm">
                        <div class="form-container">
                            <form method="POST" action="lead_upload.php" id="leadImportForm" enctype="multipart/form-data">
                                <!-- CSRF Token -->
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <!-- Download template link -->
                                <a href="templates/generate_template.php" class="download-link">
                                    <i class="fas fa-download"></i> Download Form Template
                                </a>
                                
                                <div class="row mb-3">
                                    <!-- File Upload Field -->
                                    <div class="col-md-12 mb-3">
                                        <label for="lead_file" class="form-label">File</label>
                                        <div class="file-input-container">
                                            <div class="file-name-display" id="file-name-display">No file selected</div>
                                            <input type="file" name="lead_file" id="lead_file" class="d-none" accept=".csv, .xls, .xlsx">
                                            <button type="button" class="btn btn-primary choose-file-btn" id="choose-file-btn">Choose File</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Note Field -->
                                    <div class="col-md-12 mb-3">
                                        <label for="note" class="form-label">Note</label>
                                        <textarea class="form-control" id="note" name="note" rows="3"><?php echo isset($_POST['note']) ? htmlspecialchars($_POST['note']) : ''; ?></textarea>
                                    </div>
                                </div>

                                <!-- User Selection with Checkboxes - Improved UI -->
                                <div class="col-md-12 mb-4">
                                    <label class="form-label">Select Users</label>
                                    <div class="user-checkbox-grid">
                                        <?php foreach ($users as $user): ?>
                                            <div class="user-checkbox-item">
                                                <input type="checkbox" 
                                                       id="user_id_<?php echo $user['id']; ?>" 
                                                       name="user_ids[]" 
                                                       value="<?php echo $user['id']; ?>"
                                                       <?php echo ($current_user_id == $user['id']) ? 'checked' : ''; ?>>
                                                <label for="user_id_<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="user-actions">
                                        <button type="button" id="select-all-users" class="btn btn-outline-primary">
                                            <i class="fas fa-check-square me-1"></i> Select All
                                        </button>
                                        <button type="button" id="deselect-all-users" class="btn btn-outline-secondary">
                                            <i class="fas fa-square me-1"></i> Deselect All
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="buttons-container">
                                    <button type="reset" class="btn btn-reset">Reset</button>
                                    <button type="submit" name="import_leads" class="btn btn-primary btn-import">Import Leads</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // File input handling
        const fileInput = document.getElementById('lead_file');
        const fileNameDisplay = document.getElementById('file-name-display');
        const chooseFileBtn = document.getElementById('choose-file-btn');
        
        // Trigger file input click when the Choose File button is clicked
        chooseFileBtn.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Update file name display when a file is selected
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = 'No file selected';
            }
        });
        
        // Select/Deselect all users
        document.getElementById('select-all-users').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });
        
        document.getElementById('deselect-all-users').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
        
        // Form validation before submission
        document.getElementById('leadImportForm').addEventListener('submit', function(event) {
            let isValid = true;
            
            // Check if file is selected
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Please select a file to upload.');
                isValid = false;
            }
            
            // Check if at least one user is selected
            const userCheckboxes = document.querySelectorAll('input[name="user_ids[]"]:checked');
            if (userCheckboxes.length === 0) {
                alert('Please select at least one user.');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
        
        // Reset form handler
        document.querySelector('button[type="reset"]').addEventListener('click', function() {
            fileNameDisplay.textContent = 'No file selected';
            document.getElementById('note').value = '';
            
            // Reset checkboxes - only check the current user
            const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
            const currentUserId = <?php echo $current_user_id; ?>;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = (parseInt(checkbox.value) === currentUserId);
            });
        });
        
        // Collapse/expand form section
        document.querySelector('[data-bs-toggle="collapse"]').addEventListener('click', function(e) {
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-chevron-up')) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        });
    });
    </script>
    
    <?php
    // Close the connection at the end of the script
    $conn->close();
    ?>
</body>

</html>