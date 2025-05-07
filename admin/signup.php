<?php
session_start();
include 'db_connection.php'; // Include the database connection file which contains session_start()

// Initialize error message variable
$error_message = "";
$success_message = "";

// Handling the sign-up form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize user inputs
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $email = trim($_POST['email']); // Don't sanitize yet to avoid modifying the email before validation
    $password = $_POST['password'];
    
    // Enhanced email validation using a more comprehensive approach
    if (!validateEmail($email)) {
        $error_message = "Invalid email format. Please enter a valid email address.";
    } 
    // Now sanitize the email after validation
    else {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Double check with PHP's built-in validator
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format. Please enter a valid email address.";
        }
        // Check for blocked email domains
        else if (isBlockedEmailDomain($email)) {
            $error_message = "This email domain is not allowed for registration.";
        } 
        // Check for minimum password length
        else if (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } 
        else {
            // Check if the email already exists
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error_message = "Email is already registered.";
            } else {
                // Insert new user into the database
                $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password
                $role_id = 2; // Default role ID for regular users
                $status = 'active'; // Set status to active by default

                $sql = "INSERT INTO users (name, email, password, role_id, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssis", $name, $email, $hashed_password, $role_id, $status);

                if ($stmt->execute()) {
                    // Get the new user's ID
                    $user_id = $conn->insert_id;
                    
                    // Store user information in session
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user'] = $email;
                    $_SESSION['name'] = $name;
                    $_SESSION['role_id'] = $role_id;
                    $_SESSION['logged_in'] = true;

                    // Success message
                    $success_message = "Registration successful! Redirecting to homepage...";
                    
                    // Redirect after a brief delay
                    echo "<script>
                            setTimeout(function() {
                                window.location.href = 'signin.php';
                            }, 2000);
                          </script>";
                } else {
                    $error_message = "Error: " . $conn->error;
                }
            }

            $stmt->close();
        }
    }
}

// Comprehensive email validation function
function validateEmail($email) {
    // Check basic format with regex
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        return false;
    }
    
    // Check if domain has valid TLD (Top Level Domain)
    $domain_parts = explode('.', substr(strrchr($email, "@"), 1));
    $tld = end($domain_parts);
    
    // Check TLD length (most TLDs are between 2-6 characters)
    if (strlen($tld) < 2 || strlen($tld) > 6) {
        return false;
    }
    
    // Check domain name length (prevent unreasonably long domains)
    $domain = substr(strrchr($email, "@"), 1);
    $domain_name = substr($domain, 0, strpos($domain, '.'));
    if (strlen($domain_name) > 30) {
        return false;
    }
    
    // Check if domain has valid characters for hostname
    if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
        return false;
    }
    
    // Additional check to prevent double dots and starting/ending with dots or hyphens
    if (strpos($domain, '..') !== false || 
        strpos($domain, '-.') !== false || 
        strpos($domain, '.-') !== false ||
        substr($domain, 0, 1) === '.' || 
        substr($domain, -1) === '.' ||
        substr($domain, 0, 1) === '-' || 
        substr($domain, -1) === '-') {
        return false;
    }
    
    return true;
}

// Function to check if email domain is blocked
function isBlockedEmailDomain($email) {
    // Extract domain from email
    $domain = substr(strrchr($email, "@"), 1);
    
    // List of blocked domains
    $blocked_domains = [
        'tempmail.com', 
        'fakeemail.com', 
        'disposable.com',
        'temp-mail.org',
        'guerrillamail.com',
        'yopmail.com',
        'mailinator.com',
        // Add more blocked domains as needed
    ];
    
    return in_array(strtolower($domain), $blocked_domains);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(45deg, #0d053b, #083b58);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .signup-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .signup-container h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 20px;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            border-color: #007BFF;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background: linear-gradient(45deg, #2500f5, #0fc536);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background: linear-gradient(45deg, #1e00c4, #0da42d);
        }

        p {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #555;
        }

        p a {
            color: #007BFF;
            text-decoration: none;
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* For form validation feedback */
        .input-error {
            border-color: #dc3545 !important;
        }
        
        .error-feedback {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Sign Up</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return validateForm()">
            <div class="input-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                <div id="nameError" class="error-feedback">Please enter your name.</div>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                <div id="emailError" class="error-feedback">Please enter a valid email address (e.g., example@domain.com).</div>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="password-requirements">Password must be at least 8 characters long.</div>
                <div id="passwordError" class="error-feedback">Password must be at least 8 characters long.</div>
            </div>
            <button type="submit">Sign Up</button>
        </form>
        <p>Already have an account? <a href="signin.php">Sign in!</a></p>
    </div>

    <script>
        // Add event listeners for real-time validation
        document.getElementById('email').addEventListener('input', validateEmailField);
        document.getElementById('password').addEventListener('input', validatePasswordField);
        
        function validateEmailField() {
            const email = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            
            if (isValidEmail(email.value)) {
                email.classList.remove('input-error');
                emailError.style.display = 'none';
                return true;
            } else {
                email.classList.add('input-error');
                emailError.style.display = 'block';
                return false;
            }
        }
        
        function validatePasswordField() {
            const password = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');
            
            if (password.value.length >= 8) {
                password.classList.remove('input-error');
                passwordError.style.display = 'none';
                return true;
            } else {
                password.classList.add('input-error');
                passwordError.style.display = 'block';
                return false;
            }
        }
        
        function isValidEmail(email) {
            // Basic regex pattern
            const basicPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            
            if (!basicPattern.test(email)) {
                return false;
            }
            
            // Extract domain part
            const domainPart = email.substring(email.lastIndexOf('@') + 1);
            
            // Check domain format
            if (!/^[a-zA-Z0-9][a-zA-Z0-9.-]{1,28}[a-zA-Z0-9]\.[a-zA-Z]{2,6}$/.test(domainPart)) {
                return false;
            }
            
            // Check for consecutive dots or hyphens
            if (domainPart.includes('..') || domainPart.includes('--') || 
                domainPart.includes('-.') || domainPart.includes('.-')) {
                return false;
            }
            
            // Check domain name length (prevent unreasonably long domain names)
            const domainName = domainPart.substring(0, domainPart.lastIndexOf('.'));
            if (domainName.length > 30) {
                return false;
            }
            
            return true;
        }
        
        function validateForm() {
            // Validate name
            const name = document.getElementById('name');
            const nameError = document.getElementById('nameError');
            
            if (name.value.trim() === '') {
                name.classList.add('input-error');
                nameError.style.display = 'block';
                return false;
            } else {
                name.classList.remove('input-error');
                nameError.style.display = 'none';
            }
            
            // Validate email and password
            const isEmailValid = validateEmailField();
            const isPasswordValid = validatePasswordField();
            
            return isEmailValid && isPasswordValid;
        }
    </script>
</body>
</html>