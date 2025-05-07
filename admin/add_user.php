<?php
// add_user.php
// Start session at the very beginning
session_start();

// Include necessary files
include 'db_connection.php';
include 'functions.php';

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
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

// Initialize error message variable
$errorMsg = '';
$successMsg = '';

// Fetch available roles dynamically
$roles = [];
$roleQuery = "SELECT id, name FROM roles";
$roleResult = $conn->query($roleQuery);

// Collect roles into an array
while ($roleRow = $roleResult->fetch_assoc()) {
    $roles[] = $roleRow;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add New User</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            padding: 25px;
            background-color: #fff;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .section-header {
            border-left: 4px solid #1565C0;
            padding-left: 10px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 500;
        }

        .form-floating .form-control {
            height: calc(3.5rem + 2px);
        }

        .save-btn {
            background-color: #1565C0;
            float: right;
            padding: 8px 25px;
        }
        
        .error-feedback {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
            display: none;
        }
        
        .is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .is-valid {
            border-color: #198754;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        /* Email validation specific */
        .email-suggestions {
            margin-top: 4px;
            font-size: 0.875em;
            color: #6c757d;
        }

        /* Commission fields styling */
        .commission-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border: 1px solid #e9ecef;
        }
        
        .input-group-text {
            background-color: #e9ecef;
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
                    <h4 class="mt-3">Create New User</h4>
                    
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
                     
                    <div class="form-container shadow">
                        <form method="POST" action="update_user.php" id="addUserForm" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <!-- User Details Section -->
                                <div class="col-md-6">
                                    <div class="section-header">User Details</div>

                                    <!-- Name Field -->
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-user"></i> Full Name
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            placeholder="Full Name" required>
                                        <div class="error-feedback" id="name-error"></div>
                                    </div>

                                    <!-- Email Field -->
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope"></i> Email Address
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="name@example.com" required>
                                        <div class="error-feedback" id="email-error"></div>
                                        <div class="email-suggestions" id="email-suggestions"></div>
                                    </div>

                                    <!-- Password Field -->
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock"></i> Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password"
                                                name="password" placeholder="Password" required>
                                            <button class="btn btn-outline-secondary toggle-password"
                                                type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="error-feedback" id="password-error"></div>
                                    </div>

                                    <!-- Mobile Field -->
                                    <div class="mb-3">
                                        <label for="mobile" class="form-label">
                                            <i class="fas fa-mobile-alt"></i> Mobile Number
                                        </label>
                                        <input type="tel" class="form-control" id="mobile" name="mobile"
                                            placeholder="Enter 10-digit mobile number">
                                        <div class="error-feedback" id="mobile-error"></div>
                                    </div>
                                    
                                    <!-- Commission Section -->
                                    <div class="commission-section">
                                        <div class="section-header">Commission Settings</div>
                                        
                                        <!-- Commission per Parcel Field -->
                                        <div class="mb-3">
                                            <label for="commission_per_parcel" class="form-label">
                                                <i class="fas fa-dollar-sign"></i> Commission per Parcel
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rs</span>
                                                <input type="number" step="0.01" min="0" class="form-control" 
                                                    id="commission_per_parcel" name="commission_per_parcel" 
                                                    placeholder="0.00">
                                            </div>
                                            <div class="error-feedback" id="commission-parcel-error"></div>
                                            <small class="text-muted">Fixed amount for each parcel processed</small>
                                        </div>

                                        <!-- Percentage Drawdown Field -->
                                        <div class="mb-3">
                                            <label for="percentage_drawdown" class="form-label">
                                                <i class="fas fa-percent"></i> Percentage Drawdown
                                            </label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                                    id="percentage_drawdown" name="percentage_drawdown" 
                                                    placeholder="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="error-feedback" id="percentage-drawdown-error"></div>
                                            <small class="text-muted">Percentage commission from total amount</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Configuration Details Section -->
                                <div class="col-md-6">
                                    <!-- NIC Field -->
                                    <div class="mb-3">
                                        <label for="nic" class="form-label">
                                            <i class="fas fa-id-card"></i> NIC Number
                                        </label>
                                        <input type="text" class="form-control" id="nic" name="nic"
                                            placeholder="Enter NIC Number">
                                        <div class="error-feedback" id="nic-error"></div>
                                    </div>

                                    <!-- Address Field -->
                                    <div class="mb-3">
                                        <label for="address" class="form-label">
                                            <i class="fas fa-map-marker-alt"></i> Address
                                        </label>
                                        <textarea class="form-control" id="address" name="address"
                                            placeholder="Enter Full Address" rows="3"></textarea>
                                        <div class="error-feedback" id="address-error"></div>
                                    </div>

                                    <!-- Status Field -->
                                    <div class="mb-3">
                                        <label for="status" class="form-label">
                                            <i class="fas fa-toggle-on"></i> Status
                                        </label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>

                                    <!-- Role Field - Dynamically Populated -->
                                    <div class="mb-3">
                                        <label for="role_id" class="form-label">
                                            <i class="fas fa-user-tag"></i> Role
                                        </label>
                                        <select class="form-select" id="role_id" name="role_id" required>
                                            <option value="" selected>Select Role...</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= htmlspecialchars($role['id']) ?>">
                                                    <?= htmlspecialchars($role['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="error-feedback" id="role-error"></div>
                                    </div>
                                    
                                    <!-- Commission Type Field -->
                                    <div class="mb-3">
                                        <label for="commission_type" class="form-label">
                                            <i class="fas fa-hand-holding-usd"></i> Commission Type
                                        </label>
                                        <select class="form-select" id="commission_type" name="commission_type">
                                            <option value="fixed">Fixed Amount Only</option>
                                            <option value="percentage">Percentage Only</option>
                                            <option value="none">No Commission</option>
                                        </select>
                                        <small class="text-muted">Determines how commission is calculated</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary save-btn" id="submitBtn">
                                        <i class="fas fa-user-plus"></i> Add User
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
    /**
     * Enhanced Email Validation Function
     * Performs comprehensive email validation with detailed error messages
     */
    function validateEmail(email) {
        // First check if email is empty
        if (email.trim() === '') {
            return {
                valid: false,
                message: 'Email address cannot be empty'
            };
        }
        
        // Check total length
        if (email.length > 254) {
            return {
                valid: false,
                message: 'Email address is too long (maximum 254 characters allowed)'
            };
        }
        
        // Check if original email contains uppercase letters
        const lowerEmail = email.toLowerCase();
        if (email !== lowerEmail) {
            return {
                valid: false,
                message: 'Email address must be in lowercase only'
            };
        }
        
        // Split email into parts for detailed validation
        const parts = email.split('@');
        if (parts.length !== 2) {
            return {
                valid: false,
                message: 'Email must contain exactly one @ symbol'
            };
        }
        
        const username = parts[0];
        const domain = parts[1];
        
        // Username part validation
        if (username.length === 0) {
            return {
                valid: false,
                message: 'Username part of email cannot be empty'
            };
        }
        
        if (username.length > 64) {
            return {
                valid: false,
                message: 'Username part of email is too long (maximum 64 characters allowed)'
            };
        }
        
        // Check for invalid patterns in username
        if (/^\.|\.$|\.\./.test(username)) {
            return {
                valid: false,
                message: 'Username cannot start or end with a period or contain consecutive periods'
            };
        }
        
        // Check for invalid characters in username
        if (!/^[a-z0-9.!#$%&'*+/=?^_`{|}~-]+$/i.test(username)) {
            return {
                valid: false,
                message: 'Username contains invalid characters'
            };
        }
        
        // Domain part validation
        if (domain.length === 0) {
            return {
                valid: false,
                message: 'Domain part of email cannot be empty'
            };
        }
        
        if (!domain.includes('.')) {
            return {
                valid: false,
                message: 'Email domain must include at least one period'
            };
        }
        
        // Check for invalid patterns in domain
        if (/^-|-$/.test(domain)) {
            return {
                valid: false,
                message: 'Domain cannot start or end with a hyphen'
            };
        }
        
        // Domain parts validation
        const domainParts = domain.split('.');
        
        // Check domain name (part before TLD)
        if (domainParts[0].length > 63) {
            return {
                valid: false,
                message: 'Domain name is too long (maximum 63 characters allowed)'
            };
        }
        
        // Check for invalid characters in domain
        if (!/^[a-z0-9.-]+$/i.test(domain)) {
            return {
                valid: false,
                message: 'Domain contains invalid characters'
            };
        }
        
        // Check TLD (last part)
        const tld = domainParts[domainParts.length - 1];
        if (tld.length < 2 || tld.length > 10) {
            return {
                valid: false,
                message: 'Email TLD (domain ending) is invalid'
            };
        }
        
        // Check if TLD contains only letters (no numbers or special chars)
        if (!/^[a-z]+$/i.test(tld)) {
            return {
                valid: false,
                message: 'TLD can only contain letters'
            };
        }
        
        // Complex email regex pattern for final validation
        const emailRegex = /^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i;
        if (!emailRegex.test(email)) {
            return {
                valid: false,
                message: 'Please enter a valid email address format (e.g., name@example.com)'
            };
        }

        return {
            valid: true,
            message: ''
        };
    }

    /**
     * Email suggestion function
     * Provides suggestions for common email typos
     */
    function suggestEmail(email) {
        if (!email || email.trim() === '' || !email.includes('@')) {
            return null;
        }
        
        const commonDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'aol.com', 'icloud.com'];
        const parts = email.split('@');
        const username = parts[0];
        const domain = parts[1];
        
        // Check for common typos in domains
        const typos = {
            'gamil.com': 'gmail.com',
            'gmail.co': 'gmail.com',
            'gmail.cm': 'gmail.com',
            'gmal.com': 'gmail.com',
            'gmail.comm': 'gmail.com',
            'gmail.cpm': 'gmail.com',
            'yahooo.com': 'yahoo.com',
            'yaho.com': 'yahoo.com',
            'yahoo.co': 'yahoo.com',
            'yahoo.cm': 'yahoo.com',
            'hotmai.com': 'hotmail.com',
            'hotmail.co': 'hotmail.com',
            'hotmai.co': 'hotmail.com',
            'hotmail.cm': 'hotmail.com',
            'outlok.com': 'outlook.com',
            'outlook.co': 'outlook.com',
            'outlookcom': 'outlook.com',
            'outlook.cm': 'outlook.com'
        };
        
        // Check for typos
        if (typos[domain]) {
            return username + '@' + typos[domain];
        }
        
        // Check for close matches
        for (const commonDomain of commonDomains) {
            // Simple Levenshtein distance heuristic (very basic)
            if (domain !== commonDomain && 
                (domain.includes(commonDomain.slice(0, -1)) || 
                 commonDomain.includes(domain.slice(0, -1)))) {
                return username + '@' + commonDomain;
            }
        }
        
        return null;
    }

    // Name validation function
    function validateName(name) {
        if (name.trim() === '') {
            return {
                valid: false,
                message: 'Name cannot be empty'
            };
        }
        
        if (name.length > 100) {
            return {
                valid: false,
                message: 'Name is too long (maximum 100 characters allowed)'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Address validation function
    function validateAddress(address) {
        if (address.trim() === '' && !document.getElementById('address').hasAttribute('required')) {
            return {
                valid: true,
                message: ''
            };
        }
        
        if (address.length > 255) {
            return {
                valid: false,
                message: 'Address is too long (maximum 255 characters allowed)'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Password validation function
    function validatePassword(password) {
        if (password.trim() === '') {
            return {
                valid: false,
                message: 'Password cannot be empty'
            };
        }
        
        if (password.length < 8) {
            return {
                valid: false,
                message: 'Password must be at least 8 characters long'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Mobile validation function
    function validateMobile(mobile) {
        if (mobile.trim() === '' && !document.getElementById('mobile').hasAttribute('required')) {
            return {
                valid: true,
                message: ''
            };
        }
        
        // Clean the mobile number - remove all non-digit characters
        const digits = mobile.replace(/\D/g, '');
        
        if (digits.length !== 10) {
            return {
                valid: false,
                message: 'Mobile number must be exactly 10 digits'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // NIC validation function
    function validateNIC(nic) {
        if (nic.trim() === '' && !document.getElementById('nic').hasAttribute('required')) {
            return {
                valid: true,
                message: ''
            };
        }
        
        const nicRegex = /^([0-9]{9}[vVxX]?|[0-9]{12})$/;
        if (!nicRegex.test(nic)) {
            return {
                valid: false,
                message: 'Please enter a valid NIC number (9 digits + V/X or 12 digits)'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Role validation function
    function validateRole(roleId) {
        if (roleId.trim() === '') {
            return {
                valid: false,
                message: 'Please select a role'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Commission per parcel validation function
    function validateCommissionParcel(amount) {
        if (amount.trim() === '' && document.getElementById('commission_type').value !== 'percentage') {
            return {
                valid: true,
                message: ''
            };
        }
        
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount)) {
            return {
                valid: false,
                message: 'Commission must be a valid number'
            };
        }
        
        if (numAmount < 0) {
            return {
                valid: false,
                message: 'Commission cannot be negative'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Percentage drawdown validation function
    function validatePercentageDrawdown(percentage) {
        if (percentage.trim() === '' && document.getElementById('commission_type').value !== 'fixed') {
            return {
                valid: true,
                message: ''
            };
        }
        
        const numPercentage = parseFloat(percentage);
        if (isNaN(numPercentage)) {
            return {
                valid: false,
                message: 'Percentage must be a valid number'
            };
        }
        
        if (numPercentage < 0 || numPercentage > 100) {
            return {
                valid: false,
                message: 'Percentage must be between 0 and 100'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Setup validation for input fields with real-time feedback
    function setupValidation(inputId, validationFunction, errorId, suggestionId = null) {
        const inputElement = document.getElementById(inputId);
        const errorElement = document.getElementById(errorId);
        const suggestionElement = suggestionId ? document.getElementById(suggestionId) : null;
        
        if (!inputElement || !errorElement) return () => true;
        
        // Real-time validation as user types (with a small delay for better UX)
        let typingTimer;
        const doneTypingInterval = 500; // half a second
        
        inputElement.addEventListener('keyup', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement);
            }, doneTypingInterval);
        });
        
        // Immediate validation on blur (when user leaves the field)
        inputElement.addEventListener('blur', function() {
            clearTimeout(typingTimer);
            validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement);
        });
        
        // Return a function that can be called to validate the field programmatically
        return function() {
            return validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement);
        };
    }
    
    function validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement) {
        // Reset validation state
        inputElement.classList.remove('is-invalid');
        inputElement.classList.remove('is-valid');
        errorElement.style.display = 'none';
        
        if (suggestionElement) {
            suggestionElement.textContent = '';
        }
        
        const value = inputElement.value.trim();
        
        // Empty check for required fields
        if (inputElement.hasAttribute('required') && value === '') {
            inputElement.classList.add('is-invalid');
            errorElement.textContent = `${inputElement.previousElementSibling.textContent.trim()} is required`;
            errorElement.style.display = 'block';
            return false;
        }
        
        // Skip further validation if empty and not required
        if (value === '' && !inputElement.hasAttribute('required')) {
            return true;
        }
        
        // Format check
        const validationResult = validationFunction(value);
        if (!validationResult.valid) {
            inputElement.classList.add('is-invalid');
            errorElement.textContent = validationResult.message;
            errorElement.style.display = 'block';
            
            // Add email suggestion if applicable
            if (inputElement.id === 'email' && suggestionElement) {
                const suggestion = suggestEmail(value);
                if (suggestion) {
                    suggestionElement.textContent = `Did you mean: ${suggestion}?`;
                    
                    // Make the suggestion clickable
                    suggestionElement.style.cursor = 'pointer';
                    suggestionElement.style.color = '#0d6efd';
                    suggestionElement.style.textDecoration = 'underline';
                    
                    suggestionElement.onclick = function() {
                        inputElement.value = suggestion;
                        validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement);
                    };
                }
            }
            
            return false;
        } else {
            // Show valid feedback
            inputElement.classList.add('is-valid');
            return true;
        }
    }

    // Password toggle visibility
    document.querySelector('.toggle-password').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    // Commission Type Change Handler
    document.getElementById('commission_type').addEventListener('change', function() {
        const commissionParcelField = document.getElementById('commission_per_parcel');
        const percentageDrawdownField = document.getElementById('percentage_drawdown');
        const selectedType = this.value;
        
        // Reset validation
        commissionParcelField.classList.remove('is-invalid', 'is-valid');
        percentageDrawdownField.classList.remove('is-invalid', 'is-valid');
        
        // Handle validation based on commission

        // Enable/disable fields based on selection
        switch(selectedType) {
            case 'fixed':
                commissionParcelField.disabled = false;
                commissionParcelField.required = true;
                percentageDrawdownField.disabled = true;
                percentageDrawdownField.required = false;
                percentageDrawdownField.value = '';
                break;
            case 'percentage':
                commissionParcelField.disabled = true;
                commissionParcelField.required = false;
                commissionParcelField.value = '';
                percentageDrawdownField.disabled = false;
                percentageDrawdownField.required = true;
                break;
            case 'both':
                commissionParcelField.disabled = false;
                commissionParcelField.required = true;
                percentageDrawdownField.disabled = false;
                percentageDrawdownField.required = true;
                break;
            case 'none':
                commissionParcelField.disabled = true;
                commissionParcelField.required = false;
                commissionParcelField.value = '';
                percentageDrawdownField.disabled = true;
                percentageDrawdownField.required = false;
                percentageDrawdownField.value = '';
                break;
        }
    });

    // Initialize validation functions for each field
    const validateEmailField = setupValidation('email', validateEmail, 'email-error', 'email-suggestions');
    const validateNameField = setupValidation('name', validateName, 'name-error');
    const validatePasswordField = setupValidation('password', validatePassword, 'password-error');
    const validateMobileField = setupValidation('mobile', validateMobile, 'mobile-error');
    const validateNICField = setupValidation('nic', validateNIC, 'nic-error');
    const validateAddressField = setupValidation('address', validateAddress, 'address-error');
    const validateRoleField = setupValidation('role_id', validateRole, 'role-error');
    const validateCommissionParcelField = setupValidation('commission_per_parcel', validateCommissionParcel, 'commission-parcel-error');
    const validatePercentageDrawdownField = setupValidation('percentage_drawdown', validatePercentageDrawdown, 'percentage-drawdown-error');

    // Auto-convert email to lowercase
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('input', function() {
        // Get cursor position before change
        const start = this.selectionStart;
        const end = this.selectionEnd;
        
        // Convert to lowercase
        this.value = this.value.toLowerCase();
        
        // Restore cursor position
        this.setSelectionRange(start, end);
    });

    // Mobile handling - strip non-digits as user types
    const mobileInput = document.getElementById('mobile');
    mobileInput.addEventListener('input', function(e) {
        // Get only digits from the input
        let digits = this.value.replace(/\D/g, '');
        
        // Store cursor position
        const cursorPos = this.selectionStart;
        const oldLength = this.value.length;
        
        // Limit to 10 digits
        if (digits.length > 10) {
            digits = digits.substring(0, 10);
        }
        
        // Update the input value with only digits
        this.value = digits;
        
        // Adjust cursor position if text changed
        const newLength = this.value.length;
        const cursorAdjust = newLength - oldLength;
        
        // Only set selection range if the element is focused
        if (document.activeElement === this) {
            let newPos = cursorPos + cursorAdjust;
            if (newPos < 0) newPos = 0;
            if (newPos > this.value.length) newPos = this.value.length;
            this.setSelectionRange(newPos, newPos);
        }
    });

    // Format commission fields to 2 decimal places when focus is lost
    const formatDecimalField = function(element) {
        if (element.value.trim() !== '') {
            const value = parseFloat(element.value);
            if (!isNaN(value)) {
                element.value = value.toFixed(2);
            }
        }
    };

    document.getElementById('commission_per_parcel').addEventListener('blur', function() {
        formatDecimalField(this);
    });

    document.getElementById('percentage_drawdown').addEventListener('blur', function() {
        formatDecimalField(this);
    });

    // Client-side form validation
    document.getElementById('addUserForm').addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validate all fields
        if (!validateNameField()) isValid = false;
        if (!validateEmailField()) isValid = false;
        if (!validatePasswordField()) isValid = false;
        if (!validateMobileField()) isValid = false;
        if (!validateNICField()) isValid = false;
        if (!validateAddressField()) isValid = false;
        if (!validateRoleField()) isValid = false;
        
        // Validate commission fields based on selected commission type
        const commissionType = document.getElementById('commission_type').value;
        
        if (commissionType === 'fixed' || commissionType === 'both') {
            if (!validateCommissionParcelField()) isValid = false;
        }
        
        if (commissionType === 'percentage' || commissionType === 'both') {
            if (!validatePercentageDrawdownField()) isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
            
            // Scroll to the first error
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    </script>
    
    <?php
    // Close the connection at the end of the script
    $conn->close();
    ?>
</body>
</html>