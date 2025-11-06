<?php
// File này được include từ checkout.php
?>

<style>
    /* ... (Toàn bộ CSS của bạn giữ nguyên) ... */
    .checkout-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .checkout-section { padding: 40px 0; }
    .checkout-details, .checkout-form { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
    .cart-items { list-style-type: none; margin-bottom: 20px; padding-left: 0; }
    .cart-item { display: flex; align-items: center; border-bottom: 1px solid #e0e0e0; padding: 15px 0; }
    .cart-item:last-child { border-bottom: none; }
    .cart-item img { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; margin-right: 20px; }
    .item-info { flex-grow: 1; }
    .item-info h5 { font-size: 1.1rem; margin-bottom: 5px; color: #555; }
    .item-info p { margin: 5px 0; color: #666; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
    .modal-body img { border: 2px solid #007bff; border-radius: 8px; }
    .modal-body p { font-size: 1rem; color: #333; }
    .text-danger { color: #dc3545 !important; }
    .text-muted { color: #6c757d !important; }
    .text-success { color: #198754 !important; }

    /* CSS để hiển thị % giảm giá (GIỐNG TRONG GIỎ HÀNG) */
    .price-container {
        display: flex;
        align-items: center;
    }
    .price-container p {
        margin-bottom: 0;
    }
    .discount-badge {
        background-color: #dc3545;
        color: white;
        padding: 3px 8px;
        font-size: 0.8rem;
        font-weight: bold;
        border-radius: 4px;
        margin-left: 10px;
    }
</style>

<section class="checkout-section">
    <div class="checkout-container">
        <h2 class="font-baloo">Thanh toán</h2>

        <?php
        if (!empty($messages)) {
            foreach ($messages as $msg) {
                echo "<div class='alert alert-danger'>$msg</div>";
            }
        }
        ?>

        <div class="checkout-details">
            <h3 class="font-baloo">Sản phẩm trong giỏ hàng</h3>
            <ul class="cart-items">
                <?php if (empty($displayCartItems)): ?>
                    <li class="cart-item">Giỏ hàng của bạn đang trống.</li>
                <?php else: ?>
                    <?php foreach ($displayCartItems as $item): ?>
                        <li class="cart-item">
                            <img src="./assets/products/<?php echo htmlspecialchars($item['item_image']); ?>" alt="Product Image">
                            <div class="item-info">
                                <h5><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                <p>Số lượng: <?php echo $item['quantity']; ?></p>
                                
                                <?php 
                                $price_to_show = (float)$item['price_to_use'];
                                $original_price = (float)$item['item_price'];
                                
                                if ($price_to_show < $original_price): 
                                ?>
                                    <div class="price-container">
                                        <p>Giá: <strong class="text-danger"><?php echo number_format($price_to_show, 0, ',', '.'); ?> đ</strong>
                                            <s class="text-muted" style="font-size: 0.9rem; margin-left: 5px;"><?php echo number_format($original_price, 0, ',', '.'); ?> đ</s>
                                        </p>
                                        <span class="discount-badge">-10%</span>
                                    </div>
                                <?php else: ?>
                                    <div class="price-container">
                                        <p>Giá: <?php echo number_format($price_to_show, 0, ',', '.'); ?> đ</p>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        </li>
                    <?php endforeach;?>
                <?php endif; ?>
            </ul>
            <hr>
            <h5 class="font-baloo">Tạm tính (giá gốc): <?php echo number_format($displaySubTotal, 0, ',', '.'); ?> đ</h5>
            <h5 class="font-baloo text-success">Giảm giá: - <?php echo number_format($displayTotalDiscount, 0, ',', '.'); ?> đ</h5>
            <h4 class="font-baloo">Tổng tiền thanh toán: <?php echo number_format($displayTotalPrice, 0, ',', '.'); ?> đ</h4>
        </div>

        <h1 class="text-center font-baloo">Nhập thông tin mua hàng</h1>
        
       <form method="POST" action="checkout.php" class="checkout-form">
            <div class="form-group">
                <label for="name">Họ và tên</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="method">Phương thức thanh toán</label>
                <select name="method" id="method" required onchange="handlePaymentMethodChange(this.value)">
                    <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                    <option value="Bank" data-bs-toggle="modal" data-bs-target="#bankTransferModal">Chuyển khoản ngân hàng</option>
                </select>
            </div>
            <div class="form-group">
                <label for="address">Địa chỉ giao hàng</label>
                <input type="text" name="address" id="address" required>
            </div>
            <div class="form-group">
                <label for="note">Ghi chú (nếu có)</label>
                <textarea name="note" id="note" rows="4"></textarea>
            </div>
            
            <?php if (!empty($displayCartItems)): ?>
                <button type="submit" name="checkout" class="btn btn-primary">Đặt Hàng</button>
            <?php else: ?>
                <button type="button" class="btn btn-secondary" disabled>Giỏ hàng trống</button>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="modal fade" id="bankTransferModal" ...>
        </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ... (Hàm JS của bạn giữ nguyên) ...
</script>