<?php
// THÊM 2 DÒNG NÀY VÀO ĐỂ KIỂM TRA:
// echo "DEBUG SESSION: <br>";
// var_dump($_SESSION);
// echo "<hr>";
// // --- KẾT THÚC THÊM ---

$user_id = $_SESSION['user_id'] ?? 1;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (Toàn bộ code POST của bạn giữ nguyên, tôi ẩn đi cho gọn) ...
    if (isset($_POST['delete-cart-submit'])) {
        $deletedrecord = $Cart->deleteCart($_POST['item_id']);
    }
    if (isset($_POST['update-quantity'])) {
        $updatedQuantity = $Cart->updateQuantity($_POST['item_id'], $_POST['quantity']);
    }
    if (isset($_POST['delete-all-cart'])) {
        $Cart->deleteAllCart($user_id);
    }
    if (isset($_POST['wishlist-submit'])) {
        $Cart->saveForLater($_POST['item_id']);
    }
    if (isset($_POST['go-buy'])) {
        if (isset($_SESSION['user_id'])) {
            header('Location: checkout.php');
        } else {
            $message[] = 'Vui lòng đăng nhập để mua hàng.';
            header('Location: login.php');
        }
    }
}

// // --- BẮT ĐẦU LOGIC LẤY ID SẢN PHẨM ĐẶC BIỆT ---
$special_products_list = []; // Mảng để chứa các ID sản phẩm đặc biệt
$current_time = time();

// 1. Lấy danh sách ID sản phẩm đặc biệt (nếu session còn hạn)
if (isset($_SESSION['special_products']) &&
    isset($_SESSION['special_products_expiry']) &&
    $_SESSION['special_products_expiry'] > $current_time) {

    // Dùng array_column để lấy một mảng chỉ chứa các 'item_id'
    $special_products_list = array_column($_SESSION['special_products'], 'item_id');
}
// --- KẾT THÚC LOGIC LẤY ID SẢN PHẨM ĐẶC BIỆT ---
?>

<section id="cart" class="py-3 mb-5">
    <div class="container-fluid w-75">
        <h1 class="font-baloo text-center">Giỏ hàng</h1>

        <div class="row">
            <div class="col-sm-9">
                <?php
                // Giả sử bạn đã lấy dữ liệu giỏ hàng từ cơ sở dữ liệu hoặc session
                $cartItems = $product->getCartData($user_id); // Lấy dữ liệu giỏ hàng

                // // KHỞI TẠO TỔNG TIỀN MỚI ĐỂ TÍNH TOÁN THỦ CÔNG
                $new_total_price = 0;

                if ($cartItems):
                    foreach ($cartItems as $cart) :
                        // Lấy chi tiết sản phẩm từ database
                        $item = $product->getProduct($cart['item_id']); // Lấy thông tin sản phẩm

                        // Vì $item là mảng chứa một phần tử (mảng sản phẩm)
                        foreach ($item as $singleItem):
                            $item_id = $singleItem['item_id']; // Lấy ID của sản phẩm trong giỏ
                            $item_name = $singleItem['item_name']; // Tên sản phẩm
                            $item_price = $singleItem['item_price']; // Giá sản phẩm
                            $item_image = $singleItem['item_image']; // Hình ảnh sản phẩm
                            $item_quantity = $cart['quantity']; // Số lượng trong giỏ hàng

                            // // --- TÍNH TOÁN GIÁ SALE ---
                            $original_price = $item_price; // Giữ lại giá gốc
                            $price_to_use = $original_price; // Mặc định là giá gốc

                            if (in_array($item_id, $special_products_list)) {
                                // Nếu ĐÚNG, áp dụng giảm giá 10%
                                $price_to_use = $original_price * 0.90;
                            }
                            
                            // --- TÍNH TỔNG TIỀN ---
                            // Tính tổng tiền cho món hàng này (giá * số lượng)
                            $sub_total_item = $price_to_use * $item_quantity;
                            // Cộng vào tổng tiền cuối cùng
                            $new_total_price += $sub_total_item;
                            // --- KẾT THÚC TÍNH TOÁN ---
                ?>

                            <div class="row border-top py-3 mt-3">
                                <div class="col-sm-2">
                                    <img src="./assets/products/<?php echo $item_image; ?>" style="height: 120px;" alt="cart1"
                                        class="img-fluid">
                                </div>
                                <div class="col-sm-8">
                                    <h5 class="font-baloo font-size-20"><?php echo $item_name ?? "Unknown"; ?></h5>

                                    <div class="price d-flex">
                                        <span class="font-rale font-size-16">
                                            <?php echo number_format($price_to_use, 0, ',', '.'); ?> đ
                                        </span>
                                        
                                        <?php if ($price_to_use != $original_price) { ?>
                                            <strike class="text-muted font-size-14 ml-2"><?php echo number_format($original_price, 0, ',', '.'); ?> đ</strike>
                                            <span class="badge badge-danger ml-2">-10%</span>
                                        <?php } ?>
                                    </div>
                                    <div class="qty d-flex pt-2">
                                        <form method="POST" class="update-form">
                                            <input type="hidden" value="<?php echo $item_id; ?>" name="item_id">
                                            <input type="number" name="quantity" value="<?php echo $item_quantity; ?>" min="1" max="<?php echo $singleItem['item_quantity']; ?>"
                                                class="form-control" required>
                                            <button type="submit" name="update-quantity" class="btn btn-primary mt-2">Cập
                                                nhật</button>
                                        </form>

                                        <form method="post">
                                            <input type="hidden" value="<?php echo $item_id; ?>" name="item_id">
                                            <button type="submit" name="delete-cart-submit"
                                                class="btn btn-danger text-white ml-4 font-baloo px-3">Xóa</button>
                                        </form>
                                    </div>
                                    </div>

                                <div class="col-sm-2 text-right">
                                    <div class="font-size-20 text-danger font-baloo">
                                        <span class="product_price" data-id="<?php echo $item_id; ?>"
                                            data-price="<?php echo $price_to_use; /* <-- Cũng sửa ở đây */ ?>">
                                            
                                            <?php echo number_format($sub_total_item, 0, ',', '.') . ' đ'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php
                        endforeach; // Kết thúc foreach $item
                    endforeach; // Kết thúc foreach $cartItems
                else:
                    echo "<p>Giỏ hàng của bạn hiện tại không có sản phẩm nào.</p>";
                endif;
                ?>

                <form method="post">
                    <button type="submit" name="delete-all-cart"
                        class="btn btn-danger font-baloo mt-4 px-3">Xóa tất cả</button>
                </form>

            </div>

            <div class="col-sm-3">
                <div class="sub-total border text-center mt-2">
                    <h6 class="font-size-12 font-rale text-success py-3"><i class="fas fa-check"></i> Đơn hàng của bạn
                        được Giao hàng MIỄN PHÍ.</h6>
                    <div class="border-top py-4">
                        <h5 class="font-baloo font-size-20">Tổng giỏ hàng
                            (<?php echo isset($cartItems) ? count($cartItems) : 0; ?> item) <br> <span
                                class="text-danger" id="deal-price">
                                
                                <?php echo number_format($new_total_price, 0, ',', '.'); ?> đ
                            </span>
                        </h5>
                        <form method="post">
                            <button type="submit" name="go-buy" class="btn btn-warning mt-3">Mua hàng</button>
                        </form>
                    </div>

                </div>
            </div>
            </div>
        </div>
</section>