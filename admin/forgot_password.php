<?php
// Database connection (replace with your actual database details)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fe_it";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email exists in the database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Simulate sending a reset email (replace this with actual email sending logic)
        echo "<script>alert('A password reset link has been sent to your email address.');</script>";
    } else {
        echo "<script>alert('Email address not found.');</script>";
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
</head>
<body style="font-family: 'Arial', sans-serif; background-color: #f4f7fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;">

    <div class="forgot-password-container" style="background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); padding: 40px; width: 100%; max-width: 400px;">
        <h2 style="font-size: 24px; text-align: center; margin-bottom: 30px; color: #333;">Forgot Password</h2>
        <form action="forgotpassword.php" method="POST">
            <div class="input-group" style="margin-bottom: 20px;">
                <label for="email" style="font-size: 14px; color: #555; margin-bottom: 8px; display: block;">Enter Your Email Address</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 12px; font-size: 16px; border: 1px solid #ccc; border-radius: 4px; transition: border-color 0.3s ease;">
            </div>
            <button type="submit" style="width: 100%; padding: 12px; font-size: 16px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s ease;">Send Reset Link</button>
        </form>
        <div style="margin-top: 20px; text-align: center;">
            <a href="signin.php" style="font-size: 14px; color: #4CAF50; text-decoration: none;">Back to Sign In</a>
        </div>
    </div>

</body>
</html>
