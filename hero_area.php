<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hero Slider with Swiper</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/8.4.5/swiper-bundle.min.css">
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: Arial, sans-serif;
    }
    
    .hero-slider {
      width: 100%;
      height: 100%;
      position: absolute;
      top: 0;
      left: 0;
      z-index: -1;
    }
    
    .hero-slider .swiper-slide {
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
    }
    
    .hero-slider .swiper-pagination-bullet {
      width: 12px;
      height: 12px;
      background: #fff;
      opacity: 0.5;
    }
    
    .hero-slider .swiper-pagination-bullet-active {
      opacity: 1;
      background: #4CAF50;
    }
    
    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: -1;
    }
    
    #hero-area {
      position: relative;
      overflow: hidden;
      height: 85vh;
      display: flex;
      align-items: center;
      padding: 125px 0 0px;
    }
    
    .hero-content {
      position: relative;
      z-index: 1;
      color: white;
      justify-content: center;
      align-items: center;
      height: 100%;
    }
    
    .hero-content h2, .hero-content p {
      color: #fff;
    }
    
    .swiper-button-next, .swiper-button-prev {
      color: #fff;
    }
    
    .hero-video {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: -1;
      display: none;
    }
    
    /* Blue and green gradient spans */
    .blue-gradient {
      background: linear-gradient(to right, #00bcd4, #2196f3);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      padding: 0 5px;
    }
    
    .green-gradient {
      background: linear-gradient(to right, #0fb916, #8BC34A);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      padding: 0 5px;
    }
    
    /* Button styling */
    .btn-common {
      background-color: #4CAF50;
      color: white;
      padding: 12px 30px;
      border-radius: 30px;
      text-decoration: none;
      display: inline-block;
      margin-top: 20px;
      font-weight: bold;
      transition: all 0.3s ease;
    }
    
    .btn-common:hover {
      background-color: #388E3C;
      transform: translateY(-3px);
    }
    
    .container {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 15px;
    }
    
    .row {
      display: flex;
      flex-wrap: wrap;
    }
    
    .col-md-12 {
      width: 100%;
    }
    
    /* For mobile view */
    @media (max-width: 768px) {
      .hero-slider {
        display: none;
      }
      
      .hero-video {
        display: block;
      }
      
      .hero-content h2 {
        font-size: 24px;
      }
      
      .hero-content p {
        font-size: 16px;
      }
    }
  </style>
</head>

<body>
  <!-- Hero Area Start -->
  <div id="hero-area" class="hero-area-bg">
    <!-- Hero Slider -->
    <div class="swiper hero-slider">
      <div class="swiper-wrapper">
        <!-- Using placeholder images since actual images might not be available -->
        <div class="swiper-slide" style="background-image: url('assets/img/slider1.png');"></div>
        <div class="swiper-slide" style="background-image: url('assets/img/slider2.png');"></div>
        <div class="swiper-slide" style="background-image: url('assets/img/slider3.png');"></div>
      </div>
      <!-- Add Pagination -->
      <div class="swiper-pagination"></div>
      <!-- Add Navigation - uncomment if needed -->
      <!-- <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div> -->
    </div>
    
    <!-- Overlay -->
    <div class="hero-overlay"></div>

    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="contents hero-content text-center">
            <h2 class="head-title">Transforming Enterprises <span class="blue-gradient">Through Strategic</span> <span class="green-gradient">Technology Solutions</span></h2>
            <p class="head-wrap">For your unique business requirements</p>
            <div class="header-button">
              <a href="contact.php" class="btn btn-common">Meet With Us</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Hero Area End -->

  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/8.4.5/swiper-bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const heroSwiper = new Swiper('.hero-slider', {
        // Enable loop mode
        loop: true,
        
        // Enable auto play with 5 second delay
        autoplay: {
          delay: 5000,
          disableOnInteraction: false,
        },
        
        // Enable pagination
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },
        
        // Enable navigation (uncomment if navigation buttons are needed)
        /*
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
        */
        
        // Make sure the slider affects its full container
        slidesPerView: 1,
        spaceBetween: 0,
        
        // Add effects
        effect: 'fade',
        fadeEffect: {
          crossFade: true
        },
        
        // Responsive breakpoints
        breakpoints: {
          768: {
            slidesPerView: 1,
          }
        }
      });
    });
  </script>
</body>
</html>