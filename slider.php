
<section class="hero-slider">
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>
    
    <div class="slider-container">
      <div class="slides">
        <!-- Slide 1 -->
        <div class="slide">
          <div class="slide-content">
            <div class="category">Optimize Operations with ERP for Energy Sector</div>
            <h2 class="title" style="color: aliceblue;">Power Your Business with Efficiency</h2>
            <p class="description">Our ERP solutions integrate all energy operations into one system, improving efficiency and reducing waste by streamlining workflows and resource allocation.</p>
            <a href="erp_content.php" class="learn-more">Learn more →</a>
          </div>
          <div class="slide-image">
            <img src="assets/img/see1.png" alt="Energy Solution" />
          </div>
        </div>
  
        <!-- Slide 2 -->
        <div class="slide">
          <div class="slide-content">
            <div class="category">Manufacturing Excellence with ERP Integration</div>
            <h2 class="title" style="color: aliceblue;">Transform Production with Seamless ERP Solutions</h2>
            <p class="description">Access live data on energy usage, enabling smarter decisions, cost savings, and optimized energy distribution through detailed analytics and reports.</p>
            <a href="erp_content.php" class="learn-more">Learn more →</a>
          </div>
          <div class="slide-image">
            <img src="assets/img/se2.png" alt="Manufacturing Solution" />
          </div>
        </div>
  
        <!-- Slide 3 -->
        <div class="slide">
          <div class="slide-content">
            <div class="category">Drive Digital Innovation with ERP Technology</div>
            <h2 class="title" style="color: aliceblue;">Accelerate Your Business Digital Transformation</h2>
            <p class="description">Use predictive tools to accurately forecast energy demand, prevent equipment failures, and reduce downtimes, ensuring smoother, more reliable operations.</p>
            <a href="erp_content.php" class="learn-more">Learn more →</a>
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
  