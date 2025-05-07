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
    <source src="assets/img/conn.mp4" type="video/mp4">
  </video> -->

  
  <img src="assets/img/conn.png" alt="Hero Image" class="hero-video">

  <div class="container">
        <div class="contents">
          <h2 class="head-title"> IT Consultation</h2>
          <p class="head-wrap">Driving Business Success with Professional IT Consulting</p>
          <div class="header-button">
          <a href="services.php" class="btn btn-border video-popup">See our Services</a>
          </div>
      </div>
  </div>
</div>
<!-- Hero Area End -->
    </header>
<!--     
    <section class="custom-section">
    <div class="container">
        <h1 class="custom-title">STRATEGIC IT CONSULTATION FOR BUSINESS GROWTH</h1>
        <p class="custom-description">
            At Fardar It Solutions, we provide expert IT consultation designed to optimize your technology infrastructure.
            Our tailored solutions help businesses enhance efficiency, reduce costs, and embrace innovation. By leveraging 
            industry expertise and technology, we align IT strategies with your goals, ensuring seamless operations, improved performance, and long-term success.
        </p>
    </div>
</section> -->

<section id="services" class="section-padding">
    <div class="container">
        <div class="section-header text-center">
            <h1 class="custom-title">STRATEGIC IT CONSULTATION FOR BUSINESS GROWTH</h1>
            <p class="section-title wow fadeInDown" data-wow-delay="0.3s">
            At Fardar It Solutions, we provide expert IT consultation designed to optimize your technology infrastructure.
            Our tailored solutions help businesses enhance efficiency, reduce costs, and embrace innovation. By leveraging 
            industry expertise and technology, we align IT strategies with your goals, ensuring seamless operations, improved performance, and long-term success.
            </p>
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
        </div>
        <div class="row">
            <!-- Technology Optimization Service -->
            <div class="col-md-6 col-lg-4 col-xs-12">
                <div class="services-ecom wow fadeInRight" data-wow-delay="0.3s">
                    <div class="services-content">
                        <i class="fas fa-cogs fa-3x"></i>
                        <h3><a href="#">Technology Optimization</a></h3>
                        <p>Enhance your IT infrastructure with tailored solutions that improve efficiency, reduce costs, maximize performance, and ensure reliability.</p>
                    </div>
                </div>
            </div>
            
            <!-- Strategic Innovation Service -->
            <div class="col-md-6 col-lg-4 col-xs-12">
                <div class="services-ecom wow fadeInRight" data-wow-delay="0.6s">
                    <div class="services-content">
                        <i class="fas fa-lightbulb fa-3x"></i>
                        <h3><a href="#">Strategic Innovation</a></h3>
                        <p>Leverage cutting-edge technology and industry expertise to align IT strategies with your business goals for sustainable growth.</p>
                    </div>
                </div>
            </div>
            
            <!-- Seamless Operations Service -->
            <div class="col-md-6 col-lg-4 col-xs-12">
                <div class="services-ecom wow fadeInRight" data-wow-delay="0.9s">
                    <div class="services-content">
                    <i class="fas fa-project-diagram fa-3x"></i>
                        <h3><a href="#">Seamless Operations</a></h3>
                        <p>Ensure smooth business processes with IT solutions designed to streamline workflows, boost productivity, and enhance security.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
            <div class="category">reamlined IT Infrastructure</div>
            <h2 class="title" style="color: aliceblue;">Scalable IT Solutions</h2>
            <p class="description">We specialize in optimizing your technology systems to enhance business efficiency. Our solutions help simplify your IT environment, improve system reliability, and ensure scalable growth.</p>
            <a href="#" class="learn-more">Learn more →</a>
          </div>
          <div class="slide-image">
            <img src="assets/img/see1.png" alt="Energy Solution" />
          </div>
        </div>
  
        <!-- Slide 2 -->
        <div class="slide">
          <div class="slide-content">
            <div class="category">Innovative Technology Strategies</div>
            <h2 class="title" style="color: aliceblue;">Tech-Driven Growth</h2>
            <p class="description">We empower your business with cutting-edge technology strategies that not only support current needs but also drive future growth. By utilizing AI, cloud solutions, and data analytics, we help you stay ahead in an ever-evolving digital landscape.</p>
            <a href="#" class="learn-more">Learn more →</a>
          </div>
          <div class="slide-image">
            <img src="assets/img/se2.png" alt="Manufacturing Solution" />
          </div>
        </div>
  
        <!-- Slide 3 -->
        <div class="slide">
          <div class="slide-content">
            <div class="category">End-to-End IT Support</div>
            <h2 class="title" style="color: aliceblue;">Proactive IT Management</h2>
            <p class="description">We go beyond reactive support by providing proactive IT management services. Our team continuously monitors and optimizes your systems, ensuring maximum uptime and preventing issues before they disrupt your business.</p>
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
