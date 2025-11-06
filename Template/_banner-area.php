<!-- Banner Area -->
<section id="banner-area">
    <div class="slider">
        <div class="item"><img src="./assets/1.png" alt="Banner1"></div>
        <div class="item"><img src="./assets/2.png" alt="Banner2"></div>
        <div class="item"><img src="./assets/3.jpg" alt="Banner3"></div>
    </div>
    <div class="dots">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
</section>
<!-- !Banner Area -->

<style>
    #banner-area {
        /* 1. Đặt chiều rộng cố định */
        width: 1200px; 
        
        /* 2. Đặt chiều cao tối đa */
        max-height: 440px; 

        /* Vẫn giữ căn giữa tự động */
        margin-left: auto;
        margin-right: auto;

        /* Vẫn giữ khoảng cách với header */
        margin-top: 20px; 

        position: relative;
        overflow: hidden; 
        background: #f0f0f0; 
        border-radius: 8px; 
    }

    .slider {
        width: 300%; 
        
        /* 3. Thay đổi chiều cao khung */
        height: 440px; 
        
        display: flex;
        transition: transform 0.7s ease-in-out;
    }

    .slider .item {
        width: 33.3333%;
        height: 100%;
    }

    .slider .item img {
        width: 100%;

        /* 4. Thay đổi chiều cao ảnh */
        height: 440px; 

        object-fit: cover; 
        display: block; 
    }
    
    /* CSS cho các dấu chấm (dots) giữ nguyên */
    .dots {
        position: absolute;
        bottom: 25px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px; 
        z-index: 10;
    }

    .dot {
        width: 10px; 
        height: 10px;
        background: rgba(255, 255, 255, 0.6); 
        border-radius: 50%; 
        cursor: pointer;
        transition: all 0.4s ease;
    }

    .dot.active {
        background: #ffffff; 
        width: 30px; 
        border-radius: 10px;
    }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function(){
        const slider = $('.slider');
        const dots = $('.dot');
        let currentSlide = 0;
        let totalSlides = $('.slider .item').length;
        let slideInterval;

        // Hàm chuyển slide
        function goToSlide(index) {
            currentSlide = index;
            slider.css('transform', `translateX(-${33.33 * index}%)`);
            dots.removeClass('active');
            dots.eq(index).addClass('active');
        }

        // Bắt đầu tự động chuyển slide
        function startSlideShow() {
            slideInterval = setInterval(() => {
                currentSlide = (currentSlide + 1) % totalSlides;
                goToSlide(currentSlide);
            }, 5000);
        }

        // Xử lý sự kiện click vào dot
        dots.click(function() {
            clearInterval(slideInterval);
            goToSlide($(this).index());
            startSlideShow();
        });

        // Khởi động slideshow
        goToSlide(0);
        startSlideShow();
    });
</script>
