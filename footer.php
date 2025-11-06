</main>
<!-- !start #main-site -->

<!-- start #footer -->
<footer id="footer" class=" text-white py-5" style="background-color: #959595;">
    <div class="container">
        <div class="row">
            <div class="col-lg-2 col-12">
                <img width="100" src="./assets/logo.png" alt="logo" class="logo">
            </div>
            <div class="col-lg-4 col-12">
                <h4 class="font-rubik font-size-20">About</h4>
                <div class="d-flex flex-column flex-wrap">
                    <p class="font-size-14 font-rale text-white-50">
                        REAL TECH thành lập năm 2025. Chúng tôi là cửa hàng bán đồ gốm sứ uy tín tại Việt Nam , chuyên cung cấp các sản phẩm gốm sứ đẹp, chất lượng, giá rẻ nhất thị trường.
                    </p>
                </div>

            </div>
            <div class="col-lg-3 col-12">
                <h4 class="font-rubik font-size-20">Feature</h4>
                <div class="d-flex flex-column flex-wrap">
                    <a href="#" class="font-rale font-size-14 text-white-50 pb-1">Giỏ hàng</a>
                    <a href="#" class="font-rale font-size-14 text-white-50 pb-1">Admin</a>
                </div>
            </div>
            <div class="col-lg-3 col-12">
                <h4 class="font-rubik font-size-20">Contact</h4>
                <div class="d-flex flex-column flex-wrap">
                    <p class="font-rale font-size-14 text-white-50 pb-1">Hotline: 0774420704</p>
                    <p class="font-rale font-size-14 text-white-50 pb-1">Address: Đà Nẵng-Việt Nam</p>
                </div>
            </div>
        </div>
    </div>
</footer>
<div class="copyright text-center bg-dark text-white py-2">
    <p class="font-rale font-size-14">&copy; Copyrights 2025. Desing By <span class="color-second">REAL TECH</span></p>
</div>
<!-- !start #footer -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.0/jquery.min.js" integrity="sha256-xNzN2a4ltkB44Mc/Jz3pT4iU1cmeR0FkXs4pru/JxaQ=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

<!-- Owl Carousel Js file -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js" integrity="sha256-pTxD+DSzIwmwhOqTFN+DB+nHjO4iAsbgfyFq5K5bcE0=" crossorigin="anonymous"></script>

<!--  isotope plugin cdn  -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.isotope/3.0.6/isotope.pkgd.min.js" integrity="sha256-CBrpuqrMhXwcLLUd5tvQ4euBHCdh7wGlDfNz8vbu/iI=" crossorigin="anonymous"></script>

<!-- Custom Javascript -->
<script src="index.js"></script>

<script>
$(document).ready(function(){
    var userRating = <?php echo $user_rating_value; ?>; // Lấy đánh giá cũ của user
    var stars = $('.user-rating-stars i');
    
    // Hàm để tô màu sao
    function setStars(rating) {
        stars.removeClass('fas selected').addClass('far'); // Reset tất cả
        if (rating > 0) {
            stars.slice(0, rating).removeClass('far').addClass('fas selected');
        }
    }
    
    // 1. Hover
    stars.on('mouseover', function(){
        var onStar = parseInt($(this).data('rating'), 10);
        stars.slice(0, onStar).removeClass('far').addClass('fas hover');
        stars.slice(onStar).removeClass('fas hover').addClass('far');
    });
    
    stars.on('mouseout', function(){
        stars.removeClass('hover');
        setStars(userRating); // Trả về trạng thái đã lưu
    });

    // 2. Click (Gửi đánh giá)
    stars.on('click', function(){
        var rating = parseInt($(this).data('rating'), 10);
        var product_id = $('.user-rating-stars').data('product-id');
        var messageDiv = $('#rating-message');

        // Cập nhật rating đã chọn
        userRating = rating;
        setStars(userRating);
        
        messageDiv.text('Đang gửi đánh giá...').removeClass('text-danger').addClass('text-success');

        // Gửi bằng AJAX
        $.ajax({
            url: 'submit_rating.php',
            method: 'POST',
            data: {
                product_id: product_id,
                rating: rating
            },
            dataType: 'json', // Mong đợi nhận về JSON
            success: function(response){
                if(response.success){
                    messageDiv.text(response.message).removeClass('text-danger').addClass('text-success');
                    
                    // Cập nhật lại số sao trung bình và số lượt đánh giá
                    $('#product-rating-count').text(response.new_count);
                    
                    // Cập nhật lại sao trung bình (HTML)
                    var avgStarsHtml = '';
                    var avgRating = Math.round(response.new_average);
                    for (var i = 1; i <= 5; i++) {
                        if (i <= avgRating) {
                            avgStarsHtml += '<i class="fas fa-star"></i>';
                        } else {
                            avgStarsHtml += '<i class="far fa-star"></i>';
                        }
                    }
                    $('#product-average-rating').html(avgStarsHtml);

                } else {
                    messageDiv.text(response.message).removeClass('text-success').addClass('text-danger');
                }
            },
            error: function(){
                messageDiv.text('Lỗi kết nối. Vui lòng thử lại.').removeClass('text-success').addClass('text-danger');
            }
        });
    });
    
    // Khởi tạo sao khi tải trang
    setStars(userRating);
});
</script>
</body>

</html>