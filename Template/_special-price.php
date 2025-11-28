<?php
// QUAN TRỌNG: Đảm bảo session_start() được gọi ở ĐẦU TIÊN
// Nếu bạn đã có nó ở file header.php hoặc file config, thì không cần thêm lại.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = @$_SESSION['user_id'] ?? (isset($_SESSION['guest_id']) ? $_SESSION['guest_id'] : 1);

if($_SERVER['REQUEST_METHOD'] == "POST"){
    // PHP này kiểm tra 'special_price_submit'
    if (isset($_POST['special_price_submit'])){
        // Cho phép thêm vào giỏ hàng mà không cần đăng nhập
        $Cart->addToCart($_POST['user_id'], $_POST['item_id']);
    }
}

// --- BẮT ĐẦU LOGIC RANDOM 15 PHÚT ---

$current_time = time(); // Lấy thời gian hiện tại (dưới dạng giây)
$expiry_duration = 60 * 60; // 15 phút * 60 giây

// 1. Kiểm tra xem session có tồn tại VÀ còn hạn không
if (isset($_SESSION['special_products']) && 
    isset($_SESSION['special_products_expiry']) && 
    $_SESSION['special_products_expiry'] > $current_time) {
    
    // 2. Nếu còn hạn, chỉ cần lấy dữ liệu từ session
    $selectProducts = $_SESSION['special_products'];

} else {
    
    // 3. Nếu hết hạn (hoặc chưa có), query database
    $select_product =  mysqli_query($conn, "SELECT * FROM `products` ORDER BY RAND() LIMIT 5") or die('Query failed');
    $selectProducts = mysqli_fetch_all($select_product, MYSQLI_ASSOC);

    // 4. Lưu kết quả MỚI và thời gian hết hạn MỚI vào session
    $_SESSION['special_products'] = $selectProducts;
    $_SESSION['special_products_expiry'] = $current_time + $expiry_duration; // Đặt mốc hết hạn là 15 phút kể từ bây giờ
}

// --- KẾT THÚC LOGIC RANDOM 15 PHÚT ---

?>

<section id="special-price">
    <div class="container">
        <h4 class="font-rubik font-size-20">Giá Đặc Biệt Trong Hôm Nay</h4>
        <hr>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5">
            <?php foreach ($selectProducts as $item) { 
                
                // TÍNH TOÁN GIÁ GIẢM 10%
                $original_price = $item['item_price'] ?? 0;
                $discounted_price = $original_price * 0.90; // Giảm 10%

            ?>

            <div class="col mb-4">
                <div class="product font-rale h-100 bg-light border">
                    
                    <a href="<?php printf('%s?item_id=%s', 'product.php',  $item['item_id']); ?>"><img src="./assets/products/<?php echo $item['item_image'] ?? "./assets/products/13.png"; ?>" alt="product1" class="img-fluid" style="height: 200px; object-fit: cover;"></a>
                    
                    <div class="text-center p-2">
                        <h6 style="min-height: 39px;"><?php echo $item['item_name'] ?? "Unknown"; ?></h6>
                        
                        <div class="price py-2">
                            <div>
                                <strike class="text-muted font-size-14">
                                    <?php echo number_format($original_price, 0, ',', '.'); ?> đ
                                </strike>
                            </div>
                            
                            <div>
                                <span class="text-danger font-weight-bold" style="font-size: 1.1rem;">
                                    <?php echo number_format($discounted_price, 0, ',', '.'); ?> đ
                                </span>
                            </div>
                        </div>
                        <form method="post">
                            <input type="hidden" name="item_id" value="<?php echo $item['item_id'] ?? '1'; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <?php
                            if (in_array($item['item_id'], $Cart->getCartId($product->getCartData($user_id)) ?? [])){
                                echo '<button type="submit" disabled class="btn btn-success font-size-12">Đã có trong giỏ</button>';
                            }else{
                                echo '<button type="submit" name="special_price_submit" class="btn btn-warning font-size-12">Thêm vào giỏ</button>';
                            }
                            ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php } // Kết thúc vòng lặp foreach ?>
        </div>
        </div>
</section>