<?php
// Start session only if it's not already active
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

// Include helper functions
include 'functions.php';

// Initialize message variables
$success_message = '';
$error_message = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $web_name = mysqli_real_escape_string($conn, $_POST['web_name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $hotline = mysqli_real_escape_string($conn, $_POST['hotline']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if these form fields exist before accessing them
    $primary_color = isset($_POST['primary_color']) ? mysqli_real_escape_string($conn, $_POST['primary_color']) : '#3498db';
    $secondary_color = isset($_POST['secondary_color']) ? mysqli_real_escape_string($conn, $_POST['secondary_color']) : '#2ecc71';
    $font_family = isset($_POST['font_family']) ? mysqli_real_escape_string($conn, $_POST['font_family']) : 'Arial, sans-serif';
    $delivery_fee = mysqli_real_escape_string($conn, $_POST['delivery_fee']);
    
    // Logo upload handling
    $logo_url = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        $filename = $_FILES['logo']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $new_name = 'logo_' . time() . '.' . $ext;
            $destination = 'uploads/' . $new_name;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                $logo_url = $destination;
            }
        }
    }
    
    // Favicon upload handling
    $fav_icon_url = '';
    if (isset($_FILES['fav_icon']) && $_FILES['fav_icon']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'ico');
        $filename = $_FILES['fav_icon']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $new_name = 'favicon_' . time() . '.' . $ext;
            $destination = 'uploads/' . $new_name;
            
            if (move_uploaded_file($_FILES['fav_icon']['tmp_name'], $destination)) {
                $fav_icon_url = $destination;
            }
        }
    }
    
    // Check if we need to update or insert
    $check_query = "SELECT * FROM branding LIMIT 1";
    $check_result = $conn->query($check_query);
    
    if ($check_result && $check_result->num_rows > 0) {
        // Update existing record
        $row = $check_result->fetch_assoc();
        $branding_id = $row['branding_id'];
        
        $update_sql = "UPDATE branding SET 
                      company_name = '$company_name',
                      web_name = '$web_name',
                      address = '$address',
                      hotline = '$hotline',
                      email = '$email',
                      primary_color = '$primary_color',
                      secondary_color = '$secondary_color',
                      font_family = '$font_family',
                      delivery_fee = '$delivery_fee'";
        
        // Only update logo if a new one was uploaded
        if (!empty($logo_url)) {
            $update_sql .= ", logo_url = '$logo_url'";
        }
        
        // Only update favicon if a new one was uploaded
        if (!empty($fav_icon_url)) {
            $update_sql .= ", fav_icon_url = '$fav_icon_url'";
        }
        
        $update_sql .= " WHERE branding_id = $branding_id";
        
        if ($conn->query($update_sql) === TRUE) {
            $success_message = "Branding settings updated successfully!";
        } else {
            $error_message = "Error updating branding settings: " . $conn->error;
        }
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO branding (company_name, web_name, address, hotline, email, logo_url, fav_icon_url, primary_color, secondary_color, font_family, delivery_fee, active)
                      VALUES ('$company_name', '$web_name', '$address', '$hotline', '$email', '$logo_url', '$fav_icon_url', '$primary_color', '$secondary_color', '$font_family', '$delivery_fee', 1)";
        
        if ($conn->query($insert_sql) === TRUE) {
            $success_message = "Branding settings saved successfully!";
        } else {
            $error_message = "Error saving branding settings: " . $conn->error;
        }
    }
}

// Fetch current branding settings
$branding = array(
    'company_name' => '',
    'web_name' => '',
    'address' => '',
    'hotline' => '',
    'email' => '',
    'logo_url' => '',
    'fav_icon_url' => '',
    'primary_color' => '#3498db',
    'secondary_color' => '#2ecc71',
    'font_family' => 'Arial, sans-serif',
    'delivery_fee' => '0.00'
);

$query = "SELECT * FROM branding LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $branding = $result->fetch_assoc();
    
    // Make sure all keys exist in the branding array
    if (!isset($branding['primary_color']) || empty($branding['primary_color'])) {
        $branding['primary_color'] = '#3498db';
    }
    if (!isset($branding['secondary_color']) || empty($branding['secondary_color'])) {
        $branding['secondary_color'] = '#2ecc71';
    }
    if (!isset($branding['font_family']) || empty($branding['font_family'])) {
        $branding['font_family'] = 'Arial, sans-serif';
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="dark" data-toggled="close">

<head>
    <?php include('header.php'); ?>
    <title>Branding Settings</title>
    <link rel="icon" href="<?php echo !empty($branding['fav_icon_url']) ? $branding['fav_icon_url'] : 'img/system/letter-f.png'; ?>" type="image/png">
    <style>
        .form-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-section h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #333;
        }
        .logo-preview {
            max-width: 150px;
            max-height: 150px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        .favicon-preview {
            max-width: 64px;
            max-height: 64px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        .navbar-logo-preview {
            max-height: 36px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
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
                    <h1 class="mt-4">Branding Settings</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Branding Settings</li>
                    </ol>
                    
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Company Settings Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-building me-2"></i>Company Settings</h3>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($branding['company_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="web_name" class="form-label">Web Name</label>
                                    <input type="text" class="form-control" id="web_name" name="web_name" value="<?php echo htmlspecialchars($branding['web_name']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($branding['address']); ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="hotline" class="form-label">Hotline</label>
                                    <input type="text" class="form-control" id="hotline" name="hotline" value="<?php echo htmlspecialchars($branding['hotline']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($branding['email']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery_fee" class="form-label">Delivery Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs</span>
                                    <input type="number" class="form-control" id="delivery_fee" name="delivery_fee" step="0.01" value="<?php echo htmlspecialchars($branding['delivery_fee']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logo Settings Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-images me-2"></i>Logo Settings</h3>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="logo" class="form-label">Main Logo (Header)</label>
                                    <input type="file" class="form-control" id="logo" name="logo">
                                    <small class="text-muted">Recommended size: 150x36 pixels</small>
                                    
                                    <?php if (!empty($branding['logo_url'])): ?>
                                    <div class="mt-2">
                                        <p>Current Logo:</p>
                                        <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="Company Logo" class="logo-preview">
                                        <div class="mt-2">
                                            <p>Navbar Preview:</p>
                                            <div class="bg-dark p-2" style="width: fit-content;">
                                                <img src="<?php echo htmlspecialchars($branding['logo_url']); ?>" alt="Company Logo" class="navbar-logo-preview">
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="mt-2">
                                        <p>Current Default Logo:</p>
                                        <img src="img/system/stock.png" alt="Default Logo" class="logo-preview">
                                        <div class="mt-2">
                                            <p>Navbar Preview:</p>
                                            <div class="bg-dark p-2" style="width: fit-content;">
                                                <img src="img/system/stock.png" alt="Default Logo" class="navbar-logo-preview">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="fav_icon" class="form-label">Favicon</label>
                                    <input type="file" class="form-control" id="fav_icon" name="fav_icon">
                                    <small class="text-muted">Recommended size: 32x32 pixels</small>
                                    
                                    <?php if (!empty($branding['fav_icon_url'])): ?>
                                    <div class="mt-2">
                                        <p>Current Favicon:</p>
                                        <img src="<?php echo htmlspecialchars($branding['fav_icon_url']); ?>" alt="Favicon" class="favicon-preview">
                                    </div>
                                    <?php else: ?>
                                    <div class="mt-2">
                                        <p>Current Default Favicon:</p>
                                        <img src="img/system/letter-f.png" alt="Default Favicon" class="favicon-preview">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i> After updating the logo, you may need to refresh your browser or clear your cache to see the changes on all pages.
                            </div>
                        </div>
                        
                        <!-- Color Settings Section -->
                        <!-- <div class="form-section">
                            <h3><i class="fas fa-palette me-2"></i>Theme Settings</h3>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="primary_color" class="form-label">Primary Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($branding['primary_color']); ?>">
                                        <input type="text" class="form-control" id="primary_color_text" value="<?php echo htmlspecialchars($branding['primary_color']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="secondary_color" class="form-label">Secondary Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars($branding['secondary_color']); ?>">
                                        <input type="text" class="form-control" id="secondary_color_text" value="<?php echo htmlspecialchars($branding['secondary_color']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="font_family" class="form-label">Font Family</label>
                                    <select class="form-select" id="font_family" name="font_family">
                                        <option value="Arial, sans-serif" <?php echo ($branding['font_family'] == 'Arial, sans-serif') ? 'selected' : ''; ?>>Arial</option>
                                        <option value="'Helvetica Neue', Helvetica, sans-serif" <?php echo ($branding['font_family'] == "'Helvetica Neue', Helvetica, sans-serif") ? 'selected' : ''; ?>>Helvetica</option>
                                        <option value="'Open Sans', sans-serif" <?php echo ($branding['font_family'] == "'Open Sans', sans-serif") ? 'selected' : ''; ?>>Open Sans</option>
                                        <option value="'Roboto', sans-serif" <?php echo ($branding['font_family'] == "'Roboto', sans-serif") ? 'selected' : ''; ?>>Roboto</option>
                                        <option value="'Lato', sans-serif" <?php echo ($branding['font_family'] == "'Lato', sans-serif") ? 'selected' : ''; ?>>Lato</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                         -->
                        <div class="text-end mb-4">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Include JavaScript dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        // Update color text input when color picker changes
        document.getElementById('primary_color').addEventListener('input', function() {
            document.getElementById('primary_color_text').value = this.value;
        });
        
        document.getElementById('secondary_color').addEventListener('input', function() {
            document.getElementById('secondary_color_text').value = this.value;
        });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>