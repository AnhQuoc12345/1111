<?php
// === PHẦN LOGIC THÊM GIỎ HÀNG NÀY ĐƯỢC GIỮ NGUYÊN ===
$user_id = $_SESSION['user_id'] ?? 1;

// Thêm vào giỏ
if($_SERVER['REQUEST_METHOD'] == "POST"){
    if (isset($_POST['hot_product_submit'])){ 
        if ($user_id == 1) {
            header('Location: login.php');
        } else {
            $Cart->addToCart($_POST['user_id'], $_POST['item_id']);
        }
    }
}

// =================================================================
// ĐÃ THAY ĐỔI: TRUY VẤN SẢN PHẨM HOT
// Lấy 5 sản phẩm có cột `product_views` cao nhất
// =================================================================

// Giả sử biến kết nối của bạn là $conn (từ file _new_phones.php)
$hot_product_query = mysqli_query($conn, "SELECT * FROM `products` ORDER BY `product_views` DESC LIMIT 5") or die('Query failed: '. mysqli_error($conn));
$hotProducts = mysqli_fetch_all($hot_product_query, MYSQLI_ASSOC);

?>

<section id="hot-products" class="mb-5">
    <div class="container">
        <h4 class="font-rubik font-size-20">Sản Phẩm Hot</h4> 
        <hr>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5">
            
            <?php 
            // Kiểm tra nếu không có sản phẩm hot nào
            if (empty($hotProducts)):
                echo "<p class='col-12 text-center'>Chưa có sản phẩm nào được xem.</p>";
            else:
                // Lặp qua các sản phẩm hot
                foreach ($hotProducts as $item): 
            ?>
                
                <div class="col mb-4">
                    <div class="product font-rale h-100 bg-light border"> 
                        <a href="<?php printf('%s?item_id=%s', 'product.php',  $item['item_id']); ?>"><img src="./assets/products/<?php echo $item['item_image'] ?? "./assets/products/1.png"; ?>" alt="product1" class="img-fluid"></a>
                        <div class="text-center p-2"> 
                            <h6 style="min-height: 39px;"><?php echo  $item['item_name'] ?? "Unknown";  ?></h6>
                            
                            <div class="text-muted font-size-12 mb-2">
                                Lượt xem: <?php echo $item['product_views'] ?? 0; ?>
                            </div>
                            
                            <div class="price py-2">
                                <?php echo number_format($item['item_price'], 0, ',', '.'); ?> đ
                            </div>
                            
                            <form method="post">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id'] ?? '1'; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $user_id ?>">
                                <?php
                                if (in_array($item['item_id'], $Cart->getCartId($product->getData('cart')) ?? [])){
                                    echo '<button type="submit" disabled class="btn btn-success font-size-12">Đã có trong giỏ</button>';
                                }else{
                                    echo '<button type="submit" name="hot_product_submit" class="btn btn-warning font-size-12">Thêm vào giỏ</button>';
                                }
                                ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach; // kết thúc foreach
            endif; // kết thúc if (empty)
            ?>
        </div>
    </div>
</section>