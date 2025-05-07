<!-- Navbar Start -->
<nav class="navbar navbar-expand-md bg-inverse fixed-top scrolling-navbar">
        <div class="container">
          <a href="index.php" class="navbar-brand"><img src="assets/img/FEIT.png" alt=""></a>
          <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <i class="lni-menu"></i>
          </button>
          <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav mr-auto w-100 justify-content-end clearfix">
              <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
              <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="services.php" id="aboutDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Services</a>
                <div class="dropdown-menu" aria-labelledby="aboutDropdown">
                  <a class="dropdown-item" href="erp.php">ERP Software Development</a>
                  <a class="dropdown-item" href="mobile_dev.php">Mobile Application Development</a>
                  <a class="dropdown-item" href="web_dev.php">Website Development</a>
                  <a class="dropdown-item" href="seo.php">SEO</a>
                  <a class="dropdown-item" href="consultancy.php">IT Consultancy</a>
                </div>
              </li>
              <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="services.html" id="aboutDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Products</a>
    <div class="dropdown-menu" aria-labelledby="aboutDropdown">
        <div class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle" href="courier_management.php">Courier Management System </a>
           
        </div>
    </div>
</li>

              <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
              <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>

              <?php
/*
if (isset($_SESSION['user_id'])) {
    echo '<li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>';
} else {
    echo '<li class="nav-item">
    <a class="nav-link btn btn-outline-light btn-sm signin-btn" href="signin.html">Sign In</a></li>';
    echo '<li class="nav-item">
    <a class="nav-link btn btn-outline-light btn-sm signin-btn" href="signup.html">Sign Up</a></li>';
}
*/
?>          </ul>
          </div>
        </div>
      </nav>
      <!-- Navbar End -->
