<?php
// Include database configuration
require_once 'db_config.php';

// Function to validate and sanitize input
function validateInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Initialize response for AJAX
$response = array(
    'success' => false,
    'message' => ''
);

// Process form only on AJAX POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    try {
        // Connect to database using the function from db_config.php
        $conn = connectDB();

        // Validate and sanitize inputs
        $first_name = validateInput($_POST['first-name']);
        $last_name = validateInput($_POST['last-name']);
        $email = validateInput($_POST['email']);
        $company = validateInput($_POST['company']);
        $mesage = validateInput($_POST['mesage']); // Kept as 'mesage'

        // Validate inputs
        if (empty($first_name) || empty($last_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please fill in all required fields correctly.");
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO user_form_data (first_name, last_name, email, company, mesage) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $company, $mesage);
        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }
        $stmt->close();

        $response['success'] = true;
        $response['message'] = "Form submitted successfully!";
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    } finally {
        if (isset($conn)) $conn->close();
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>FE IT Solutions</title>
  <?php include 'header.php'; ?>
  
  <style>
    /* Additional Styling for Contact Form */
    .form-section {
      background-color: #040614;
      padding: 60px 0;
      position: relative;
    }
    
    .form-container {
    max-width: 738px;
    max-height: 670;
    margin: 0 auto;
    padding: 40px;
    background-color: #0a0f2e;
    border-radius: 27px;
}
    .form-title {
      color: white;
      font-size: 2.0rem;
      font-weight: bold;
      margin-bottom: 15px;
      text-align: center;
    }
    
    .form-control {
      background-color: transparent;
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 0;
      padding: 15px;
      height: auto;
      color: white;
      margin-bottom: -8px;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      background-color: rgba(255, 255, 255, 0.1);
      border-color: #1a43bf;
      box-shadow: none;
      color: white;
    }
    
    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.6);
    }
    
    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }
    
    .form-label {
      color: white;
      font-weight: 500;
      margin-bottom: 8px;
    }
    
    .form-field {
      margin-bottom: 20px;
    }
    
    .btn-submit {
      background-color: #1a43bf;
      color: white;
      padding: 10px 40px;
      border: none;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      transition: all 0.3s ease;
      margin-top: 10px;
      border-radius: 24px;
    }
    
    .btn-submit:hover {
      background-color: #0d2982;
      transform: translateY(-2px);
    }
    
    .required-field::after {
      content: " *";
      color: #ff4757;
    }
    
    /* For two-column layout */
    .form-row {
      display: flex;
      flex-wrap: wrap;
      margin-right: -15px;
      margin-left: -15px;
    }
    
    .form-col {
      flex: 0 0 50%;
      max-width: 50%;
      padding-right: 15px;
      padding-left: 15px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .form-col {
        flex: 0 0 100%;
        max-width: 100%;
      }
      
      .form-container {
        padding: 20px;
      }
    }
    
    /* Glow effects */
    .glow {
      position: absolute;
      border-radius: 50%;
      opacity: 0.15;
      filter: blur(60px);
      z-index: 0;
    }
    
    .glow-1 {
      background: #1a43bf;
      width: 300px;
      height: 300px;
      top: -100px;
      left: -100px;
    }
    
    .glow-2 {
      background: #4e7cff;
      width: 250px;
      height: 250px;
      bottom: -50px;
      right: -50px;
    }
  </style>
</head>

<header id="header-wrap">
  <?php include 'navbar.php'; ?>

  <!-- Hero Area Start -->
  <div id="hero-area" class="hero-area-bg">
    <!-- <video autoplay muted loop playsinline class="hero-video">
    <source src="assets/img/cc.mp4" type="video/mp4">
  </video> -->

      
  <img src="assets/img/cc.png" alt="Hero Image" class="hero-video">

    <div class="container">
      <div class="contents">
        <h2 class="head-title"> Contact Us</h2>
        <p class="head-wrap">For your unique business requirements</p>
        <div class="header-button">
          <!--  <a rel="nofollow" href="about.html" class="btn btn-common">Learn More</a>
            <a href="#solutions" class="btn btn-border video-popup">See our Services</a> -->
        </div>
      </div>
    </div>
  </div>
  <!-- Hero Area End -->
   
  <?php include 'info.php'; ?>


  <!-- Google Map Section -->
  <section class="map-section">
    <div class="container-fluid p-0">
      <div class="map-container">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.213870136491!2d79.8854542!3d6.864954799999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae25a5079323159%3A0xe8b0ef6875ebadd6!2s4%20Wijayamangalarama%20Rd%2C%20Colombo!5e0!3m2!1sen!2slk!4v1741080735236!5m2!1sen!2slk"
          width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>
  </section>
<!-- Google Map Section End -->
 
</header>

<body>
      
<?php include 'form.php'; ?>
    
  <?php include 'footer.php'; ?>

  <!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="background-color: #1a1a35; color: white; border: 1px solid #0f0f25;">
      <div class="modal-header" style="border-bottom: 1px solid #2a2a45;">
        <h5 class="modal-title" id="successModalLabel">Form Submission</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalMessage" style="color: #4cff4c;">Form submitted successfully!</div>
      </div>
      <div class="modal-footer" style="border-top: 1px solid #2a2a45;">
        <button type="button" class="btn" style="background: linear-gradient(to right, #2500f5, #0fc536); color: white; font-weight: bold;" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
  <!-- Go to Top Link -->
  <a href="#" class="back-to-top">
    <i class="lni-arrow-up"></i>
  </a>

  <?php include 'script.php'; ?>
  
  <!-- Add Bootstrap JS if not already included in script.php -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
  $(document).ready(function() {
      // Form submission with AJAX
      $("#contactForm").submit(function(e) {
          e.preventDefault(); // Prevent default form submission
          
          $.ajax({
              type: "POST",
              url: "<?php echo $_SERVER['PHP_SELF']; ?>",
              data: $(this).serialize(),
              dataType: "json",
              headers: {
                  'X-Requested-With': 'XMLHttpRequest'
              },
              beforeSend: function() {
                  // You can add a loading spinner here if needed
                  $("#submitBtn").prop("disabled", true).html('Submitting...');
              },
              success: function(response) {
                  if (response.success) {
                      // Clear the form on success
                      $("#contactForm")[0].reset();
                      
                      // Show success message in modal
                      $("#modalMessage").html('<div class="alert alert-success">' + response.message + '</div>');
                  } else {
                      // Show error message in modal
                      $("#modalMessage").html('<div class="alert alert-danger">' + response.message + '</div>');
                  }
                  
                  // Show the modal
                  var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                  successModal.show();
                  
                  // Re-enable submit button
                  $("#submitBtn").prop("disabled", false).html('Submit');
              },
              error: function(xhr, status, error) {
                  // Show error message in modal
                  $("#modalMessage").html('<div class="alert alert-danger">An error occurred. Please try again later.</div>');
                  
                  // Show the modal
                  var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                  successModal.show();
                  
                  // Re-enable submit button
                  $("#submitBtn").prop("disabled", false).html('Submit');
              }
          });
      });
  });
  </script>
</body>
</html>