<?php
// Start session and setup
session_start();

// CSRF token generation
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Database connection
require_once 'db_connection.php';

// User data retrieval
$user = null;
$roles = [];

if (isset($_SESSION['user_id'])) {
    // Get user with role in a single query
    $stmt = $conn->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    // Get roles in a simpler query
    $roles = $conn->query("SELECT * FROM roles ORDER BY id")->fetch_all(MYSQLI_ASSOC);
}

// Fetch branding information for the logo
$branding_query = "SELECT logo_url FROM branding LIMIT 1";
$branding_result = $conn->query($branding_query);
$logo_path = 'img/system/stock.png'; // Default logo path

if ($branding_result && $branding_result->num_rows > 0) {
    $branding_row = $branding_result->fetch_assoc();
    if (!empty($branding_row['logo_url'])) {
        $logo_path = $branding_row['logo_url'];
    }
}

// Helper function for profile image
function getProfileImage($user) {
    if (isset($user['profile_image']) && !empty($user['profile_image'])) {
        return "uploads/profiles/" . htmlspecialchars($user['profile_image']);
    }
    return "https://static.vecteezy.com/system/resources/previews/009/267/048/non_2x/user-icon-design-free-png.png";
}
?>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand with Logo -->
    <a class="navbar-brand ps-3" href="index.php">
        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Company Logo" height="36">
    </a>
    
    <!-- Sidebar Toggle -->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Navbar Search -->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <!-- Search form content -->
    </form>
    
    <!-- User Info Display -->
    <?php if($user): ?>
    <div class="d-none d-lg-flex align-items-center me-3">
        <div class="text-end me-2">
            <div class="text-white fw-bold"><?= htmlspecialchars($user['name'] ?? 'Admin User') ?></div>
            <div class="text-white-50 small"><?= htmlspecialchars($user['role_name'] ?? 'Administrator') ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- User Dropdown -->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?= $user ? getProfileImage($user) : 'https://static.vecteezy.com/system/resources/previews/009/267/048/non_2x/user-icon-design-free-png.png' ?>" 
                     alt="Profile" class="rounded-circle" 
                     style="width: 32px; height: 32px; object-fit: cover;">
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <!--<li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle fa-fw me-2"></i>Profile</a></li>-->
                <li><a class="dropdown-item" href="branding.php"><i class="fas fa-cog fa-fw me-2"></i>Settings</a></li>
                <!--<li><a class="dropdown-item" href="activity_log.php"><i class="fas fa-list fa-fw me-2"></i>Activity Log</a></li>-->
                <!--<li><hr class="dropdown-divider" /></li>-->
                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>