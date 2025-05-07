<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>FE IT Solutions</title>

  <?php include 'header.php'; ?>
</head>

<header id="header-wrap">
  <?php include 'navbar.php'; ?>
    

<!-- Hero Area Start -->
<div id="hero-area" class="hero-area-bg">
    <!-- <video autoplay muted loop playsinline class="hero-video">
    <source src="assets/img/web-dew.mp4" type="video/mp4">
  </video> -->

  <img src="assets/img/web-dew.png" alt="Hero Image" class="hero-video">


  <div class="container">
        <div class="contents">
          <h2 class="head-title"> Web Development Service</h2>
          <p class="head-wrap">Building Intuitive and Effortless Digital Experiences </p>
          <div class="header-button">
          <a href="services.php" class="btn btn-border video-popup">See our Services</a>
           <!--  <a rel="nofollow" href="about.html" class="btn btn-common">Learn More</a>
            <a href="#solutions" class="btn btn-border video-popup">See our Services</a> -->
          </div>
      </div>
  </div>
</div>
<!-- Hero Area End -->

    </header>
<!-- Services Section Start -->
<section id="services" class="section-padding">
    <div class="container">
      
        <div class="section-header text-center">
            <h1 class="custom-title">EXPLORE OUR WEB SOLUTIONS</h1>
            <p class="section-title wow fadeInDown" data-wow-delay="0.3s">   At Fardar IT Solutions, we specialize in transforming your mobile app ideas into reality with cutting-edge innovation, technical expertise, and a user-centric approach. 
            Our team is dedicated to delivering high-performance, scalable, and visually stunning applications that drive success for your business.</p>
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
        </div>
        <div class="section-header text-center">
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
        </div>
        <div class="row">
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
                <div class="services-ecom wow fadeInRight" data-wow-delay="0.3s">
                    <div class="services-content">
                        <i class="fas fa-desktop fa-3x"></i>
                        <h3><a href="#"> Smart Business Websites</a></h3>
                        <p>Create professional, high-performance websites tailored to your brand's needs. Whether it's a corporate site, portfolio, or service-based platform, we ensure seamless functionality and a visually compelling experience.</p>
                    </div>
                </div>
            </div>
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
                <div class="services-ecom wow fadeInRight" data-wow-delay="0.6s">
                    <div class="services-content">
                        <i class="fas fa-shopping-cart fa-3x"></i>
                        <h3><a href="#">E-Commerce & Online Stores</a></h3>
                        <p>Launch a powerful online store with secure payment gateways, intuitive navigation, and conversion-driven design. We integrate advanced e-commerce features to enhance sales and customer engagement.</p>
                    </div>
                </div>
            </div>
            <!-- Services item -->
            <div class="col-md-6 col-lg-4 col-xs-12">
                <div class="services-ecom wow fadeInRight" data-wow-delay="0.9s">
                    <div class="services-content">
                        <i class="fas fa-code fa-3x"></i>
                        <h3><a href="#">Custom Web Applications</a></h3>
                        <p>Go beyond standard websites with interactive, feature-rich web apps. From customer portals to real-time dashboards, we develop scalable, innovative, and customized solutions to optimize your business processes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Services Section End -->
      <?php include 'step.php'; ?>

<?php include 'call_action.php'; ?>
    
 
<section class="hero-slider">
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>
    
    <div class="slider-container">
      <div class="slides">
        <!-- Slide 1 -->
        <div class="slide">
          <div class="slide-content">
            <div class="category">Business Websites</div>
            <h2 class="title" style="color: aliceblue;">Establish Your Online Presence</h2>
            <p class="description">Leading brands trust our expertly designed business websites to create a strong digital identity, attract customers, and enhance credibility.</p>
            <a href="#" class="learn-more">Learn more →</a>
          </div>
          <div class="slide-image">
            <img src="assets/img/see1.png" alt="Energy Solution" />
          </div>
        </div>
  
        <!-- Slide 2 -->
        <div class="slide">
          <div class="slide-content">
            <div class="category">E-Commerce Solutions</div>
            <h2 class="title" style="color: aliceblue;">Sell Smarter, Grow Faster</h2>
            <p class="description">Top online retailers rely on our e-commerce platforms for seamless transactions, secure payments, and engaging shopping experiences.</p>
            <a href="#" class="learn-more">Learn more →</a>
          </div>
          <div class="slide-image">
            <img src="assets/img/se2.png" alt="Manufacturing Solution" />
          </div>
        </div>
  
        <!-- Slide 3 -->
        <div class="slide">
          <div class="slide-content">
            <div class="category">Custom Web Applications</div>
            <h2 class="title" style="color: aliceblue;">Innovate with Scalable Solutions</h2>
            <p class="description">Industry leaders choose our custom web apps to streamline operations, automate workflows, and drive business efficiency.</p>
            <a href="#" class="learn-more">Learn more →</a>
          </div>
          <div class="slide-image">
            <img src="assets/img/se3.png" alt="Digital Solution" />
          </div>
        </div>
      </div>
  
      <div class="slider-nav">
        <button class="nav-bt prev">←</button>
        <div class="slide-counter">1 / 3</div>
        <button class="nav-bt next">→</button>
      </div>
    </div>
  </section>
  
  <script>
  const slides = document.querySelector('.slides');
  const prevBtn = document.querySelector('.prev');
  const nextBtn = document.querySelector('.next');
  const counter = document.querySelector('.slide-counter');
  let currentSlide = 0;
  const totalSlides = 3;
  
  function updateSlider() {
    slides.style.transform = `translateX(-${currentSlide * 100}%)`;
    counter.textContent = `${currentSlide + 1} / ${totalSlides}`;
  }
  
  prevBtn.addEventListener('click', () => {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    updateSlider();
  });
  
  nextBtn.addEventListener('click', () => {
    currentSlide = (currentSlide + 1) % totalSlides;
    updateSlider();
  });
  
  // Auto-slide
  setInterval(() => {
    currentSlide = (currentSlide + 1) % totalSlides;
    updateSlider();
  }, 5000);
  </script>
  

<?php include 'footer.php'; ?>

    <!-- Go to Top Link -->
    <a href="#" class="back-to-top">
    	<i class="lni-arrow-up"></i>
    </a>
    
    <!-- Preloader -->
    <div id="preloader">
      <div class="loader" id="loader-1"></div>
    </div>
    <!-- End Preloader -->
    <?php include 'script.php'; ?>
      
  </body>
</html>
