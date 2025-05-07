<?php
// Include necessary files
require_once 'db_connection.php'; // Your database connection file
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if invoice_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invoice ID is missing.");
}

$invoice_id = $_GET['id'];

// Fetch invoice details from the database
$query = "SELECT * FROM invoices WHERE invoice_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invoice not found.");
}

$invoice = $result->fetch_assoc();

// Prepare email content
$to_email = $invoice['customer_email']; // Assuming you store the customer's email in the database
$subject = "Your Invoice #" . $invoice['invoice_number'];
$message = "Dear " . $invoice['customer_name'] . ",\n\n";
$message .= "Please find your invoice attached.\n\n";
$message .= "Invoice Number: " . $invoice['invoice_number'] . "\n";
$message .= "Amount: $" . $invoice['amount'] . "\n";
$message .= "Due Date: " . $invoice['due_date'] . "\n\n";
$message .= "Thank you for your business!";

// Send the email
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com'; // Your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@example.com'; // Your email
    $mail->Password = 'your_email_password'; // Your email password
    $mail->SMTPSecure = 'tls'; // Encryption
    $mail->Port = 587; // Port

    // Recipients
    $mail->setFrom('your_email@example.com', 'Your Company Name');
    $mail->addAddress($to_email); // Customer's email

    // Content
    $mail->isHTML(false); // Set to true if you want to send HTML emails
    $mail->Subject = $subject;
    $mail->Body = $message;

    $mail->send();
    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>