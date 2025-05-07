<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Gallery Slider</title>
    <style>
    /* Hero Slider Container */
.hero-slider {
    background: #080B1A;
    color: white;
    min-height: 600px;
    position: relative;
    overflow: hidden;
}

/* Glow Effects */
.glow {
    position: absolute;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(224, 224, 224, 0.2) 0%, rgba(46, 8, 84, 0) 70%);
    pointer-events: none;
    z-index: 1;
}

.glow-1 {
    top: -100px;
    right: -100px;
}

.glow-2 {
    bottom: -100px;
    left: -100px;
}

/* Slider Container */
.slider-container {
    max-width: 1430px;
    margin: 0 auto;
    padding: 40px 20px;
    position: relative;
    z-index: 2;
}

/* Slides */
.slides {
    display: flex;
    transition: transform 0.5s ease-in-out;
}

.slide {
    min-width: 100%;
    display: flex;
    align-items: center;
    gap: 40px;
    padding: 63px 54px;
}

/* Slide Content */
.slide-content {
    flex: 1;
    max-width: 600px;
}

.category {
    font-size: 18px;
    color: #e0e0e0;
    margin-bottom: 20px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.title {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 24px;
    line-height: 1.2;
}

.description {
    font-size: 20px;
    line-height: 1.6;
    margin-bottom: 32px;
    color: rgba(255, 255, 255, 0.9);
}

.learn-more {
    display: inline-block;
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.learn-more:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Slide Image */
.slide-image {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
}

.slide-image img {
    max-width: 300px;
    height: auto;
    filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.2));
}

/* Slides Container */
.slides {
    display: flex;
    transition: transform 0.5s ease;
    width: 100%;
}

.slide {
    flex: 0 0 100%;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    box-sizing: border-box;
}

.slide-images {
    max-width: 100%;
    height: auto;
    display: flex;
    justify-content: center;
    align-items: center;
}

.slide-images img {
    max-width: 100%;
    max-height: 500px;
    object-fit: contain;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* Navigation */
.slider-nav {
    position: absolute;
    bottom: -40px;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 38px;
    z-index: 3;
}

.nav-bt {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
    opacity: 0.7;
    transition: opacity 0.3s ease;
    font-size: 24px;
}

.nav-bt:hover {
    opacity: 1;
}

.slide-counter {
    font-size: 18px;
    color: rgba(255, 255, 255, 0.8);
}

/* Glow Effects */
.glow {
  position: absolute;
  width: 400px;
  height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(224, 224, 224, 0.2) 0%, rgba(46, 8, 84, 0) 70%);
  pointer-events: none;
  z-index: 1;
}

.glow-1 { 
  top: -100px; 
  right: -100px; 
}

.glow-2 { 
  bottom: -100px; 
  left: -100px; 
}


/* Responsive Adjustments */
@media (max-width: 1024px) {
    .slide {
        flex-direction: column;
        text-align: center;
    }

    .slide-content {
        max-width: 100%;
        margin-bottom: 90px;
    }

    .slide-images img {
        max-width: 250px;
    }
}

@media (max-width: 768px) {
    .hero-slider {
        min-height: 500px;
    }

    .slide {
        padding: 40px 22px;
    }

    .slide-images img {
        max-width: 320px;
    }

    .slider-nav {
        bottom: 20px;
    }
}

@media (max-width: 480px) {
    .slide-images img {
        max-height: 200px;
    }

    .nav-bt {
        padding: 6px 10px;
        font-size: 18px;
    }
}
    </style>
</head>
<body>
<section class="hero-slider">
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>
        <div class="slider-container">
            <div class="slides">
                <!-- Slide 1 -->
                <div class="slide">
                    <div class="slide-content">
                        <div class="category">PRODUCT GALLERY</div>
                        <div class="slide-images">
                            <img src="assets/img/slide6.png" alt="Product Gallery Image 1" />
                        </div>
                    </div>
                </div>
                <!-- Slide 2 -->
                <div class="slide">
                    <div class="slide-content">
                        <div class="category">PRODUCT GALLERY</div>
                        <div class="slide-images">
                            <img src="assets/img/slide1.png" alt="Product Gallery Image 2" />
                        </div>
                    </div>
                </div>
                <!-- Slide 3 -->
                <div class="slide">
                    <div class="slide-content">
                        <div class="category">PRODUCT GALLERY</div>
                        <div class="slide-images">
                            <img src="assets/img/slide2.png" alt="Product Gallery Image 3" />
                        </div>
                    </div>
                </div>
                <!-- Slide 4 -->
                <div class="slide">
                    <div class="slide-content">
                        <div class="category">PRODUCT GALLERY</div>
                        <div class="slide-images">
                            <img src="assets/img/slide3.png" alt="Product Gallery Image 4" />
                        </div>
                    </div>
                </div>
                <!-- Slide 5 -->
                <div class="slide">
                    <div class="slide-content">
                        <div class="category">PRODUCT GALLERY</div>
                        <div class="slide-images">
                            <img src="assets/img/slide4.png" alt="Product Gallery Image 5" />
                        </div>
                    </div>
                </div>
                <!-- Slide 6 -->
                <div class="slide">
                    <div class="slide-content">
                        <div class="category">PRODUCT GALLERY</div>
                        <div class="slide-images">
                            <img src="assets/img/slide5.png" alt="Product Gallery Image 6" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="slider-nav">
                <button class="nav-bt prev">←</button>
                <div class="slide-counter">1 / 6</div>
                <button class="nav-bt next">→</button>
            </div>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelector('.slides');
        const prevBtn = document.querySelector('.prev');
        const nextBtn = document.querySelector('.next');
        const counter = document.querySelector('.slide-counter');
        let currentSlide = 0;
        const totalSlides = 6;
        
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
    });
    </script>
</body>
</html>