<style>
    .star-rating {
        font-size: 0; /* Loại bỏ khoảng trắng giữa các inline-block */
        white-space: nowrap;
        display: inline-block;
    }
    .star-rating i {
        font-size: 20px; /* Kích thước sao */
        color: #ddd; /* Màu sao rỗng */
        margin: 0 1px;
    }
    .star-rating .fas {
        color: #f7b000; /* Màu sao đầy (vàng) */
    }
    /* CSS cho phần đánh giá của người dùng */
    .user-rating-stars {
        padding: 10px 0;
    }
    .user-rating-stars i {
        font-size: 24px;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }
    .user-rating-stars i:hover,
    .user-rating-stars i.hover {
        color: #f7b000; /* Màu khi hover */
    }
    .user-rating-stars i.selected {
        color: #f7b000; /* Màu khi đã chọn */
    }
    #rating-message {
        margin-top: 10px;
        font-size: 14px;
    }
    #rating-message.text-success { color: #28a745; }
    #rating-message.text-danger { color: #dc3545; }
</style>

<?php
$user_id = $_SESSION['user_id'] ?? 1;
    $item_id = $_GET['item_id'] ?? 1;

// (Code đếm lượt xem của bạn vẫn được giữ nguyên)
    $item_id_safe = (int)$item_id;
    $update_views_query = "UPDATE `products` SET `product_views` = `product_views` + 1 WHERE `item_id` = $item_id_safe";
    $db->con->query($update_views_query);


    foreach ($product->getData() as $item) :
        if ($item['item_id'] == $item_id) :
            
            // Lấy đánh giá của riêng user này cho sản phẩm này (nếu có)
            $user_rating_value = 0;
            if ($user_id != 1) { // Chỉ kiểm tra nếu user đã đăng nhập
                $rating_query = "SELECT rating_value FROM ratings WHERE product_id = $item_id_safe AND user_id = $user_id";
                $user_rating_result = $db->con->query($rating_query);
                if ($user_rating_result && $user_rating_result->num_rows > 0) {
                    $user_rating_value = (int)$user_rating_result->fetch_assoc()['rating_value'];
                }
            }
?>
<section id="product" class="py-3">
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <img src="./assets/products/<?php echo $item['item_image'] ?>" alt="product" class="img-fluid">
                <div class="form-row pt-4 font-size-16 font-baloo">
                    <form method="post">
                        <input type="hidden" name="item_id" value="<?php echo $item['item_id'] ?? '1'; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user_id ?>">
                        <?php
                        if (in_array($item['item_id'], $Cart->getCartId($product->getData('cart')) ?? [])){
                            echo '<button type="submit" disabled class="btn btn-success font-size-16 form-control">Đã có trong giỏ</button>';
                        }else{
                            echo '<button type="submit" name="top_sale_submit" class="btn btn-warning font-size-16 form-control">Thêm vào giỏ</button>';
                        }
                        ?>
                    </form>
                </div>
            </div>
            <div class="col-sm-6 py-5">
                <h5 class="font-baloo font-size-20"><?php echo $item['item_name'] ?? "Unknown"; ?></h5>
                
                <div class="d-flex">
                    <div class="star-rating" id="product-average-rating">
                        <?php
                        $avg_rating = round($item['average_rating'] ?? 0); // Làm tròn điểm trung bình
                        for ($i = 1; $i <= 5; $i++):
                            if ($i <= $avg_rating):
                        ?>
                            <i class="fas fa-star"></i><?php else: ?>
                            <i class="far fa-star"></i><?php 
                            endif;
                        endfor; 
                        ?>
                    </div>
                    <a href="#" class="px-2 font-rale font-size-14">
                        <span id="product-rating-count"><?php echo $item['rating_count'] ?? 0; ?></span> đánh giá
                    </a>
                </div>
                <hr class="m-0">

                <table class="my-3">
                    <tr class="font-rale font-size-14">
                        <td>Giá:</td>
                        <td class="font-size-20 text-danger">
                            <span><?php echo number_format($item['item_price'], 0, ',', '.'); ?> đ</span><small
                                class="text-dark font-size-12">&nbsp;&nbsp;Bao gồm thuế</small></td>
                    </tr>
                </table>

                <div id="policy">
                    </div>
                <hr>
                
                <?php if ($user_id != 1): // Chỉ hiển thị nếu user đã đăng nhập ?>
                <div class="user-rating-section">
                    <h6 class="font-baloo">Đánh giá của bạn:</h6>
                    <div class="user-rating-stars" data-product-id="<?php echo $item_id_safe; ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="far fa-star rating-star" 
                               data-rating="<?php echo $i; ?>"
                               <?php echo ($i <= $user_rating_value) ? 'class="fas fa-star rating-star selected"' : ''; ?>></i>
                        <?php endfor; ?>
                    </div>
                    <div id="rating-message"></div>
                </div>
                <?php else: // Nếu là khách (user_id == 1) ?>
                <div class="text-muted">
                    Vui lòng <a href="login.php">đăng nhập</a> để đánh giá sản phẩm này.
                </div>
                <?php endif; ?>
                </div>

            <div class="col-12">
                <h6 class="font-rubik mt-4">Mô tả sản phẩm</h6>
                <hr>
                <p class="font-rale font-size-14">
                    <?php echo $item['item_desc'] ?? "Unknown"; ?>
                </p>
            </div>
        </div>
    </div>
</section>
<?php
        endif;
        endforeach;
?>

