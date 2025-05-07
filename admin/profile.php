<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>User Profile - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <?php 
    // // Start session if not already started
    // if (session_status() === PHP_SESSION_NONE) {
    //     session_start();
    // }
    
    // // Check if user is logged in
    // if (!isset($_SESSION['user_id'])) {
    //     // Redirect to login page
    //     header("Location: signin.php");
    //     exit();
    // }
    
    // // Include database connection
  
include 'db_connection.php';
include 'functions.php'; // Include helper functions
    
    // Generate CSRF token if not exists

    include 'navbar.php'; 
    ?>
    
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <br>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>User Profile</h2>
                    </div>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo htmlspecialchars($_SESSION['success_message']); 
                            unset($_SESSION['success_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo htmlspecialchars($_SESSION['error_message']); 
                            unset($_SESSION['error_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Profile Picture</h5>
                                </div>
                                <div class="card-body text-center">
                                    <?php if(isset($user['profile_image']) && !empty($user['profile_image'])): ?>
                                        <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="assets/img/default-avatar.png" alt="Default Profile" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <?php endif; ?>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name'] ?? 'Admin User'); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($user['role_name'] ?? 'Administrator'); ?></p>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updatePictureModal">
                                            <i class="fas fa-camera me-1"></i> Change Picture
                                        </button>
                                        <!-- <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            <i class="fas fa-key me-1"></i> Change Password
                                        </button> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal for Changing Password -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm" method="post" action="update_password.php">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div class="mb-3">
                            <label for="oldPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="oldPassword" name="old_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            <div class="form-text text-muted">Password must be at least 8 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal for Updating Profile Picture -->
    <div class="modal fade" id="updatePictureModal" tabindex="-1" aria-labelledby="updatePictureModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updatePictureModalLabel">Change Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updatePictureForm" method="post" action="update_picture.php" enctype="multipart/form-data">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div class="mb-3">
                            <label for="profilePicture" class="form-label">Select New Image</label>
                            <input type="file" class="form-control" id="profilePicture" name="profile_picture" accept="image/jpeg,image/png,image/jpg,image/gif" required>
                          div class="form-text">Recommended size: 300x300 pixels. Max file size: 2MB. Allowed formats: JPG, JPEG, PNG, GIF.</div>
                        </div>
                        <div class="mb-3">
                            <div id="imagePreview" class="text-center" style="display: none;">
                                <img src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Upload Image</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
    $(document).ready(function() {
       
        
        // Profile picture validation and preview
        $('#profilePicture').change(function() {
            var fileInput = this;
            var maxSize = 2 * 1024 * 1024; // 2MB
            var allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            
            if (fileInput.files.length > 0) {
                var file = fileInput.files[0];
                
                // Check file size
                if (file.size > maxSize) {
                    alert('File size exceeds the maximum limit (2MB)');
                    fileInput.value = '';
                    $('#imagePreview').hide();
                    return false;
                }
                
                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, JPEG, PNG, and GIF files are allowed');
                    fileInput.value = '';
                    $('#imagePreview').hide();
                    return false;
                }
                
                // Show image preview
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview img').attr('src', e.target.result);
                    $('#imagePreview').show();
                }
                reader.readAsDataURL(file);
            } else {
                $('#imagePreview').hide();
            }
        });
        
      
    </script>
</body>
</html>