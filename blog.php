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
    <source src="assets/img/hero-blog.mp4" type="video/mp4">
  </video> -->
  <img src="assets/img/hero-blog.png" alt="Hero Image" class="hero-video">


  <div class="container">
        <div class="contents">
          <h2 class="head-title"> FE It Solutions Blog</h2>
          <p class="head-wrap">Blogs on Technology, Innovation and Creativity</p>
          <div class="header-button">
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
            <h1 class="custom-title">Latest News & Updates</h1>
            <p class="section-title wow fadeInDown" data-wow-delay="0.3s">   Explore the latest trends, insights, and expert opinions on our blog. 
            Stay informed on technology innovations and software development with valuable content and industry updates from FE IT Solutions.</p>
            <div class="shape wow fadeInDown" data-wow-delay="0.3s"></div>
        </div>
        </div>
        </section>



     <!-- Services Section Start -->
     <!-- <section class="custom-section">
      <div class="container">
        <h1 class="custom-title">Latest News & Updates</h1>
        <p class="custom-description">
          Explore the latest trends, insights, and expert opinions on our blog. 
          Stay informed on technology innovations and software development with valuable content and industry updates from FE IT Solutions.
        </p>
      </div>
    </section> -->
  <!-- Services Section End -->
<body>
  
      <style>
          .blog-post {
              background: white;
              border-radius: 8px;
              overflow: hidden;
              box-shadow: 0 2px 4px rgba(0,0,0,0.1);
              transition: transform 0.3s ease;
              padding: 1.5rem;
              margin-bottom: 2rem;
              height: 100%;
          }
  
          .blog-post:hover {
              transform: translateY(-5px);
              box-shadow: 0 4px 8px rgba(0,0,0,0.2);
          }
  
          .blog-post img {
              width: 100%;
              height: 200px;
              object-fit: cover;
              border-radius: 4px;
              margin-bottom: 1rem;
          }
  
          .blog-post h2 {
              font-size: 1.25rem;
              font-weight: bold;
              margin-bottom: 1rem;
              color: #333;
          }
  
          .blog-post p {
              color: #666;
              font-size: 0.95rem;
              line-height: 1.5;
              margin-bottom: 1rem;
          }
  
          .blog-post a {
              display: inline-block;
              color: #dc2626;
              text-decoration: none;
              font-weight: 500;
              transition: color 0.3s ease;
          }
  
          .blog-post a:hover {
              color: #b91c1c;
          }
  
          .blog-meta {
              font-size: 0.85rem;
              color: #888;
              margin-bottom: 1rem;
          }
.popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 9999;
}

.popup-content {
   
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    z-index: 10000;
}

.close-popup {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #333;
    transition: color 0.3s;
}

.close-popup:hover {
    color: #dc2626;
}

.popup-content img {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 20px;
}

.popup-content .blog-meta {
    margin-bottom: 20px;
}

.popup-content h2 {
  font-size: 18px;
    color: #333;
    margin-bottom: 20px;
}
</style>

<!-- Update your blog posts section -->
<div class="container my-5">
    <div class="row">
        <!-- Blog Post 1 -->
        <div class="col-md-6 mb-4">
            <div class="blog-post">
                <img src="assets/img/blog1.jpg" alt="IT Consulting">
                <div class="blog-meta">
                    <span>John Doe (2025-01-15)</span>
                </div>
                <h2>Why Your Business Needs IT Consulting</h2>
                <p>Learn how IT consulting services can streamline operations and boost productivity in your organization...</p>
                <a href="#" onclick="openPopup(1); return false;">Read More</a>
            </div>
        </div>

        <!-- Blog Post 2 -->
        <div class="col-md-6 mb-4">
            <div class="blog-post">
                <img src="assets/img/blog2.jpg" alt="IT Trends">
                <div class="blog-meta">
                    <span>Jane Smith (2025-01-20)</span>
                </div>
                <h2>Top 5 Emerging IT Trends in 2025</h2>
                <p>Discover the groundbreaking technologies shaping the future of the IT industry in 2025...</p>
                <a href="#" onclick="openPopup(2); return false;">Read More</a>
            </div>
        </div>

        <!-- Blog Post 3 -->
        <div class="col-md-6 mb-4">
            <div class="blog-post">
                <img src="assets/img/blog3.jpg" alt="Custom Software Solutions">
                <div class="blog-meta">
                    <span>Mark Johnson (2025-01-25)</span>
                </div>
                <h2>Enhancing Business Efficiency with Custom Software Solutions</h2>
                <p>At FE IT Solutions, we believe in the power of personalized software to transform businesses...</p>
                <a href="#" onclick="openPopup(3); return false;">Read More</a>
            </div>
        </div>

        <!-- Blog Post 4 -->
        <div class="col-md-6 mb-4">
            <div class="blog-post">
                <img src="assets/img/blog4.jpg" alt="Courier Technology">
                <div class="blog-meta">
                    <span>Sarah Williams (2025-01-30)</span>
                </div>
                <h2>ERP Systems in Sri Lanka: Revolutionizing the Way We Do Business</h2>
                <p>In recent years, Enterprise Resource Planning (ERP) systems have become a game-changer for businesses in Sri Lanka...</p>
                <a href="#" onclick="openPopup(4); return false;">Read More</a>
            </div>
        </div>
    </div>
</div>

<!-- Popup containers for each blog post -->
<div id="popup1" class="popup-overlay">
    <div class="popup-content">
        <span class="close-popup" onclick="closePopup(1)">&times;</span>
        <img src="assets/img/blog1.jpg" alt="IT Consulting">
        <div class="blog-meta">
            <span>John Doe (2025-01-15)</span>
        </div>
        <h2>Why Your Business Needs IT Consulting</h2>
        <div class="blog-content">
            <p>Learn how IT consulting services can streamline operations and boost productivity in your organization. In today's rapidly evolving technological landscape, businesses need expert guidance to navigate the complexities of digital transformation.</p>
            <br>
            <h2>Key Benefits of IT Consulting</h2>
            <p>Professional IT consulting services offer numerous advantages to businesses of all sizes:</p>
            <ul>
                <li>Strategic technology planning and implementation</li>
                <li>Cost-effective solutions for business challenges</li>
                <li>Enhanced security measures and risk management</li>
                <li>Improved operational efficiency</li>
                <li>Access to expert knowledge and industry best practices</li>
            </ul>
            <br>
            <h2>Why Choose Professional IT Consulting?</h2>
            <p>Working with experienced IT consultants helps organizations stay competitive in today's digital landscape while minimizing risks and maximizing returns on technology investments.</p>
        </div>
    </div>
</div>

<div id="popup2" class="popup-overlay">
    <div class="popup-content">
        <span class="close-popup" onclick="closePopup(2)">&times;</span>
        <img src="assets/img/blog2.jpg" alt="IT Trends">
        <div class="blog-meta">
            <span>Jane Smith (2025-01-20)</span>
        </div>
        <h2>Top 5 Emerging IT Trends in 2025</h2>
        <div class="blog-content">
            <p>Discover the groundbreaking technologies shaping the future of the IT industry in 2025. As technology continues to evolve at an unprecedented pace, staying ahead of the curve is crucial for business success.</p><br>
            
            <h2>1. Artificial Intelligence and Machine Learning</h2>
            <p>AI and ML continue to revolutionize business processes and decision-making capabilities.</p><br>
            
            <h2>2. Edge Computing</h2>
            <p>The rise of edge computing is transforming how data is processed and analyzed in real-time.</p><br>
            
            <h2>3. Cybersecurity Evolution</h2>
            <p>Advanced threat detection and prevention systems are becoming increasingly sophisticated.</p><br>
            
            <h2>4. Quantum Computing</h2>
            <p>The practical applications of quantum computing are beginning to emerge across industries.</p><br>
            
            <h2>5. Sustainable IT</h2>
            <p>Green technology and sustainable IT practices are becoming essential for business operations.</p>
        </div>
    </div>
</div>

<div id="popup3" class="popup-overlay">
    <div class="popup-content">
        <span class="close-popup" onclick="closePopup(3)">&times;</span>
        <img src="assets/img/blog3.jpg" alt="Custom Software Solutions">
        <div class="blog-meta">
            <span>Mark Johnson (2025-01-25)</span>
        </div>
        <h2>Enhancing Business Efficiency with Custom Software Solutions</h2>
        <div class="blog-content">
            <p>At FE IT Solutions, we believe in the power of personalized software to transform businesses. By providing custom-tailored software solutions, we help companies streamline their processes, improve operational efficiency, and enhance overall performance.</p>
            <br>
            <h2>Benefits of Custom Software</h2>
            <ul>
                <li>Tailored to specific business needs</li>
                <li>Improved efficiency and productivity</li>
                <li>Better integration with existing systems</li>
                <li>Scalability for future growth</li>
                <li>Enhanced security and control</li>
            </ul>
        </div>
    </div>
</div>

<div id="popup4" class="popup-overlay">
    <div class="popup-content">
        <span class="close-popup" onclick="closePopup(4)">&times;</span>
        <img src="assets/img/blog4.jpg" alt="ERP Systems">
        <div class="blog-meta">
            <span>Sarah Williams (2025-01-30)</span>
        </div>
        <h2>ERP Systems in Sri Lanka: Revolutionizing the Way We Do Business</h2>
        <div class="blog-content">
            <p>In recent years, Enterprise Resource Planning (ERP) systems have become a game-changer for businesses in Sri Lanka. As local businesses strive for greater efficiency and competitiveness, ERP systems offer integrated solutions that streamline operations across various departments—finance, HR, inventory, and more.</p>
            <br>
            <h2>Impact on Sri Lankan Businesses</h2>
            <ul>
                <li>Improved operational efficiency</li>
                <li>Better resource management</li>
                <li>Enhanced decision-making capabilities</li>
                <li>Increased competitiveness in global markets</li>
                <li>Streamlined business processes</li>
            </ul>
        </div>
    </div>
</div>

<!-- Add this JavaScript before the closing body tag -->
<script>
function openPopup(postId) {
    document.getElementById('popup' + postId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePopup(postId) {
    document.getElementById('popup' + postId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close popup when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('popup-overlay')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close popup with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.popup-overlay').forEach(popup => {
            popup.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }
});
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
      
      <!-- jQuery first, then Popper.js, then Bootstrap JS -->
      <script src="assets/js/jquery-min.js"></script>
      <script src="assets/js/popper.min.js"></script>
      <script src="assets/js/bootstrap.min.js"></script>
      <script src="assets/js/owl.carousel.min.js"></script>
      <script src="assets/js/wow.js"></script>
      <script src="assets/js/jquery.nav.js"></script>
      <script src="assets/js/scrolling-nav.js"></script>
      <script src="assets/js/jquery.easing.min.js"></script>
      <script src="assets/js/jquery.counterup.min.js"></script>      
      <script src="assets/js/waypoints.min.js"></script>   
      <script src="assets/js/main.js"></script>
        
    </body>
  </html>
  