<?php
session_start(); // Start the session at the very beginning
include 'db_connection.php'; // Include the database connection file

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message variable
$error_message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); // Check if "Remember Me" is checked

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Query to check if user exists with the given email
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check if user is active
            if ($user['status'] != 'active') {
                $error_message = "Your account is inactive. Please contact support.";
            } else {
                // Verify the hashed password
                if (password_verify($password, $user['password']) || $password == $user['password']) { // Second condition for testing only, remove in production
                    // Password is correct, start session
                    $_SESSION['user'] = $email;
                    $_SESSION['user_id'] = $user['id']; // Storing user ID in session
                    $_SESSION['role_id'] = $user['role_id']; // Storing role ID
                    $_SESSION['name'] = $user['name']; // Storing user name
                    $_SESSION['logged_in'] = true;

                    // Handle "Remember Me" by setting cookies
                    if ($remember) {
                        setcookie("email", $email, time() + (86400 * 30), "/"); // 30 days
                    } else {
                        // Clear cookie if "Remember Me" is unchecked
                        setcookie("email", "", time() - 3600, "/");
                    }

                    // Redirect based on user role
                    switch ($user['role_id']) {
                        case 1: // Superadmin
                            header("Location: index.php");
                            break;
                        case 2: // Regular user
                            header("Location: index.php"); // Fixed missing page name
                            break;
                        case 3: // Other user type
                            header("Location: index.php"); // Fixed missing page name
                            break;
                        default:
                            header("Location: index.php"); // Fixed missing page name
                    }
                    exit();
                } else {
                    $error_message = "Invalid password.";
                }
            }
        } else {
            $error_message = "No user found with that email.";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(45deg, #0d053b, #083b58);
            /* Gradient background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .signin-container {
            display: flex;
            width: 900px;
            max-width: 100%;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .signin-left {
            flex: 1;
            background: linear-gradient(135deg,  #032038, #1b277a);
            background-size: cover;
            background-position: center;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
        }

        .signin-left h2 {
            font-size: 36px;
            margin: 0;
        }

        .signin-left p {
            font-size: 18px;
            margin-top: 10px;
        }

        .signin-right {
            flex: 1;
            padding: 40px;
        }

        .signin-right h2 {
            font-size: 24px;
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }

        .input-group input {
            width: 93%;
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

        .input-group label a {
            font-size: 14px;
            color: #007BFF;
            text-decoration: none;
        }

        .signin-right button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background: linear-gradient(45deg, #2500f5, #0fc536);
            /* Gradient background */
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .signin-right button:hover {
            background-color: #45a049;
        }

        .signin-right p {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #555;
        }

        .signin-right p a {
            color: #007BFF;
            text-decoration: none;
        }

        .input-group.remember-me {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .input-group.remember-me label {
            display: flex;
            align-items: center;
            gap: 5px;
            /* Add some spacing between checkbox and text */
            font-size: 14px;
            color: #555;
        }

        .input-group.remember-me a {
            font-size: 14px;
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

        /* Improved password toggle styles */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #555;
            z-index: 1;
            background: none;
            border: none;
            padding: 0;
            font-size: 16px;
        }

        .password-container input {
            padding-right: 49px;
            width: 100%;
        }

        .hero-image {
            width: 209px;
            height: auto;
            margin-bottom: 30px;
            border-radius: 8px;
          
        }
    </style>
</head>

<body>
    <div class="signin-container">
        <div class="signin-left">
        <img src="img/system/stock.png" alt="Logo" class="hero-image">
            <h2>Welcome Back!</h2>
            <p>Log in to access your account.</p>
        </div>
        <div class="signin-right">
            <h2>Sign In</h2>
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                        value="<?php echo isset($_COOKIE['email']) ? $_COOKIE['email'] : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required>
                        <span class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="input-group remember-me">
                    <label>
                        <input type="checkbox" name="remember" <?php echo isset($_COOKIE['email']) ? 'checked' : ''; ?>>
                        Remember Me
                    </label>
                    <!-- <a href="forgot_password.php">Forgot Password?</a> -->
                </div>
                <button type="submit">Sign In</button>
            </form>
            <!-- <p>Need an account? <a href="signup.php">Sign up!</a></p> -->
        </div>
    </div>

    <script>
        // JavaScript to toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the eye icon
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>