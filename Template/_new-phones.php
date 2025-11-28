<!-- New Phones -->
<?php
$user_id = @$_SESSION['user_id'] ?? (isset($_SESSION['guest_id']) ? $_SESSION['guest_id'] : 1);

// Thêm vào giỏ
    if($_SERVER['REQUEST_METHOD'] == "POST"){
        if (isset($_POST['new_phone_submit'])){ 
            // Cho phép thêm vào giỏ hàng mà không cần đăng nhập
            $Cart->addToCart($_POST['user_id'], $_POST['item_id']);
        }
    }

// Đã xóa "limit 15"
$select_product =  mysqli_query($conn, "SELECT * FROM `products` order by item_id desc") or die('Query failed');
$selectProducts = mysqli_fetch_all($select_product, MYSQLI_ASSOC);
?>
<section id="new-phones">
    <div class="container">
        <h4 class="font-rubik font-size-20">Sản Phẩm Mới</h4>
        <hr>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5">
            <?php foreach ($selectProducts as $item) { ?>
                
                <div class="col mb-4">
                    <div class="product font-rale h-100 bg-light border"> 
                        <a href="<?php printf('%s?item_id=%s', 'product.php',  $item['item_id']); ?>"><img src="./assets/products/<?php echo $item['item_image'] ?? "./assets/products/1.png"; ?>" alt="product1" class="img-fluid"></a>
                        <div class="text-center p-2"> 
                            <h6 style="min-height: 39px;"><?php echo  $item['item_name'] ?? "Unknown";  ?></h6>
                            <div class="price py-2">
                                <?php echo number_format($item['item_price'], 0, ',', '.'); ?> đ
                            </div>
                            <form method="post">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id'] ?? '1'; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $user_id ?>">
                                <?php
                                if (in_array($item['item_id'], $Cart->getCartId($product->getCartData($user_id)) ?? [])){
                                    echo '<button type="submit" disabled class="btn btn-success font-size-12">Đã có trong giỏ</button>';
                                }else{
                                    echo '<button type="submit" name="new_phone_submit" class="btn btn-warning font-size-12">Thêm vào giỏ</button>';
                                }
                                ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } // closing foreach function ?>
        </div>
        </div>
</section>
<!-- !New Phones -->