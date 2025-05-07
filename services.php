<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IFS Services</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <!-- Icon -->
  <link rel="stylesheet" href="assets/fonts/line-icons.css">
  <!-- Owl carousel -->
  <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
  <link rel="stylesheet" href="assets/css/owl.theme.css">
  
  <link rel="stylesheet" href="assets/css/magnific-popup.css">
  <link rel="stylesheet" href="assets/css/nivo-lightbox.css">
  <!-- Animate -->
  <link rel="stylesheet" href="assets/css/animate.css">
  <!-- Main Style -->
  <link rel="stylesheet" href="assets/css/main.css">
  <!-- Responsive Style -->
  <link rel="stylesheet" href="assets/css/responsive.css">
  
  <style>
    body {
      background: linear-gradient(to right, #000000, #1f2f59) !important;
    }
    
    .header {
      padding: 20px 0;
      text-align: center;
    }
    
    .navbar img {
      width: 16%;
      max-width: 200px;
      transition: width 0.3s ease;
    }
    
    .content {
      padding: 0px;
      text-align: center;
      color: #fff;
    }
    
    h1 {
      font-size: 2.5rem;
      margin-bottom: 20px;
    }
    
    .highlight {
      background: linear-gradient(to right, #0040ca, #a4cae9);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;
      display: inline-block;
    }
    .subheading {
      font-size: 1.5rem;
      margin-bottom: 30px;
    }
    
    .industry-selections {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 30px;
    }
    
    .row_ser {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      width: 100%;
      margin-bottom: 20px;
    }
    
    .industry {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 300px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .industry:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    
    .industry img {
      width: 80px;
      height: 80px;
      margin-bottom: 15px;
    }
    
    .industry p {
      font-size: 1.1rem;
      margin-bottom: 15px;
      text-align: center;
    }
    
    .btn-com {
      background-color: #82a2c573;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 20px;
      text-decoration: none;
      font-size: 0.9rem;
      transition: background-color 0.3s ease;
    }
    
    .btn-com:hover {
      background-color: #0056b3;
      color: white;
    }
    
    /* Responsive styles */
    @media (max-width: 992px) {
      .navbar img {
        width: 25%;
      }
      
      h1 {
        font-size: 2.2rem;
      }
    }
    
    @media (max-width: 768px) {
      .navbar img {
        width: 30%;
      }
      
      h1 {
        font-size: 2rem;
      }
      
      .subheading {
        font-size: 1.3rem;
      }
      
      .row_ser {
        flex-direction: column;
        align-items: center;
      }
      
      .industry {
        width: 90%;
        max-width: 350px;
      }
    }
    
    @media (max-width: 480px) {
      .navbar img {
        width: 50%;
      }
      
      h1 {
        font-size: 1.8rem;
      }
      
      .content {
        padding: 15px 10px;
      }
      
      .industry {
        padding: 15px;
      }
      
      .industry img {
        width: 60px;
        height: 60px;
      }
    }
    .navbar {
    display: block;
    text-align: center;
  }
  
  .navbar img {
    width: 16%;
    /* or a fixed pixel width if you prefer */
  }
  </style>
</head>

<body>
  <div class="container">
    <header class="header">
    <a href="index.php" class="navbar"><img src="assets/img/FEIT.png" alt=""></a>
    </header>
    
    <main class="content">
      <h1>Be your best in your <span class="highlight">Moment of Service</span>.</h1>
      <p class="subheading">Select your Industry</p>
      
      <div class="industry-selections">
        <div class="row_ser">
          <div class="industry">
            <img src="assets/img/coll.png" alt="Collaboration">
            <p>ERP Software Development</p>
            <a href="erp_content.php" class="btn btn-com mt-3">SEE HOW IT WORKS</a>
          </div>
          
          <div class="industry">
            <img src="assets/img/slo.png" alt="Problem Solving">
            <p>Mobile Application Development</p>
            <a href="mobile_dev_content.php" class="btn btn-com mt-3">SEE HOW IT WORKS</a>
          </div>
          
          <div class="industry">
            <img src="assets/img/sec.png" alt="Security">
            <p>Website Development</p>
            <a href="web_dev.php" class="btn btn-com mt-3">SEE HOW IT WORKS</a>
          </div>
        </div>
        
        <div class="row_ser">
          <div class="industry">
            <img src="assets/img/vis.png" alt="Vision">
            <p>SEO</p>
            <a href="seo.php" class="btn btn-com mt-3">SEE HOW IT WORKS</a>
          </div>
          
          <div class="industry">
            <img src="assets/img/service.png" alt="Service">
            <p>IT Consultancy</p>
            <a href="consultancy.php" class="btn btn-com mt-3">SEE HOW IT WORKS</a>
          </div>
        </div>
      </div>
    </main>
  </div>
  
  <!-- Bootstrap & jQuery JS -->
  <script src="assets/js/jquery.min.js"></script>
  <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>