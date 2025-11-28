<?php
// File này được include từ checkout.php

/**
 * Hàm tính phần trăm giảm giá. (Giữ nguyên)
 * ...
 */
function calculate_discount_percentage($originalPrice, $discountedPrice) {
    if ($originalPrice <= 0 || $discountedPrice >= $originalPrice) {
        return 0;
    }
    $discountAmount = $originalPrice - $discountedPrice;
    $percentage = ($discountAmount / $originalPrice) * 100;
    return floor($percentage);
}


// GỌI HÀM LẤY DỮ LIỆU VÀ SẮP XẾP
$provincesData = get_vietnam_provinces_data();

if (!empty($provincesData)) {
    usort($provincesData, function($a, $b) {
        return strcmp($a['province'], $b['province']);
    });
}
?>

<style>
    /* ... (CSS giữ nguyên) ... */
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
    .modal-body img { border: 2px solid #007bff; border-radius: 8px; max-width: 250px; height: auto; display: block; margin: 15px auto; }
    .modal-body p { font-size: 1rem; color: #333; }
    .text-danger { color: #dc3545 !important; }
    .text-muted { color: #6c757d !important; }
    .text-success { color: #198754 !important; }

    /* CSS để hiển thị % giảm giá */
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
    /* Thêm một chút CSS để đảm bảo nhóm form-group con hoạt động tốt trong grid */
    .address-group .form-group {
        margin-bottom: 0; /* Xóa khoảng cách dưới cho form-group con */
    }
</style>

<section class="checkout-section">
    <div class="checkout-container">
        <h2 class="font-baloo">Thanh toán</h2>

        <?php
        if (!empty($messages)) {
            foreach ($messages as $msg) {
                echo "<div class='alert alert-danger'>" . htmlspecialchars($msg) . "</div>";
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
                                    $discount_percent = calculate_discount_percentage($original_price, $price_to_show);
                                ?>
                                    <div class="price-container">
                                        <p>Giá: <strong class="text-danger"><?php echo number_format($price_to_show, 0, ',', '.'); ?> đ</strong>
                                            <s class="text-muted" style="font-size: 0.9rem; margin-left: 5px;"><?php echo number_format($original_price, 0, ',', '.'); ?> đ</s>
                                        </p>
                                        <span class="discount-badge">-<?php echo $discount_percent; ?>%</span>
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
                <input type="email" name="email" id="email">
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại <span class="text-danger">*</span></label>
                <input 
                    type="tel" 
                    name="phone" 
                    id="phone" 
                    required 
                    placeholder="Nhập số điện thoại (10-11 chữ số)"
                >
            </div>
            
            <div class="form-group address-group"> 
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <label for="city">Tỉnh / Thành phố</label>
                        <select name="city" id="city" required onchange="loadWards()">
                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                            <?php 
                            foreach ($provincesData as $province) {
                                $provinceName = htmlspecialchars($province['province']);
                                echo "<option value='{$provinceName}'>{$provinceName}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6 col-sm-12">
                        <label for="ward">Xã / Phường</label>
                        <select name="ward" id="ward" required>
                            <option value="">-- Chọn Xã/Phường --</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="address">Địa chỉ chi tiết (Số nhà, Tên đường)</label>
                <input type="text" name="address" id="address" required>
            </div>
            
            <div class="form-group">
                <label for="method">Phương thức thanh toán</label>
                <select name="method" id="method" required onchange="handlePaymentMethodChange(this.value)">
                    <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                    <option value="Bank">Chuyển khoản ngân hàng</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="note">Ghi chú (nếu có)</label>
                <textarea name="note" id="note" rows="4"></textarea>
            </div>
            
            <?php if (!empty($displayCartItems)): ?>
                <button type="submit" name="checkout" class="btn btn-primary btn-lg" style="width: 100%; padding: 15px; font-size: 1.2rem; margin-top: 20px;">Đặt Hàng</button>
            <?php else: ?>
                <div class="alert alert-warning">
                    <p>Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm vào giỏ hàng trước khi đặt hàng.</p>
                    <a href="index.php" class="btn btn-primary">Quay lại trang chủ</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="modal fade" id="bankTransferModal" tabindex="-1" aria-labelledby="bankTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"> 
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bankTransferModalLabel">Thông tin Chuyển khoản Ngân hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body text-center">
                    <p class="text-danger">**LƯU Ý: Vui lòng chuyển khoản ĐÚNG SỐ TIỀN và NỘI DUNG**</p>
                    
                    <p style="font-size: 1.1rem; font-weight: bold; color: #dc3545;">
                        Tổng tiền cần chuyển: **<?php echo number_format($displayTotalPrice, 0, ',', '.'); ?> đ**
                    </p>

                    <hr>
                    <h6 class="font-baloo text-primary">THÔNG TIN TÀI KHOẢN</h6>
                    <p><strong>Ngân hàng:</strong> Ngân hàng MB BANK</p>
                    <p><strong>Số tài khoản:</strong> 0774420704</p>
                    <p><strong>Tên chủ tài khoản:</strong> BÙI VĂN ANH QUỐC</p>
                    <p><strong>Nội dung:</strong> [Tên bạn] - [Số điện thoại] (VD: LE THI B - 0987654321)</p>
                    
                    <hr>

                    <p class="font-baloo" style="margin-top: 15px;">**QUÉT MÃ QR CODE**</p>
                    <img src="./assets/qr.jpg" alt="">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Xong</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 2. LƯU DỮ LIỆU API VÀO BIẾN JAVASCRIPT
    const PROVINCES_DATA = <?php echo json_encode($provincesData); ?>;

    // 3. HÀM XỬ LÝ LỌC XÃ/PHƯỜNG DỰA TRÊN TỈNH/THÀNH PHỐ ĐƯỢC CHỌN (Giữ nguyên)
    function loadWards() {
        const citySelect = document.getElementById('city');
        const wardSelect = document.getElementById('ward');
        const selectedProvinceName = citySelect.value;

        wardSelect.innerHTML = '<option value="">-- Chọn Xã/Phường --</option>';

        if (!selectedProvinceName) {
            wardSelect.disabled = false;
            return;
        }

        const selectedProvince = PROVINCES_DATA.find(p => p.province === selectedProvinceName);

        if (selectedProvince && selectedProvince.wards) {
            selectedProvince.wards.forEach(ward => {
                const option = document.createElement('option');
                option.value = ward.name;
                option.textContent = ward.name;
                wardSelect.appendChild(option);
            });
        }
    }

    // 4. HÀM XỬ LÝ KHI THAY ĐỔI PHƯƠNG THỨC THANH TOÁN (Giữ nguyên)
    function handlePaymentMethodChange(value) {
        if (value === 'Bank') {
            var bankTransferModalElement = document.getElementById('bankTransferModal');
            var myModal = new bootstrap.Modal(bankTransferModalElement);
            myModal.show();
        }
    }

    // 5. LẮNG NGHE SỰ KIỆN MODAL ĐÓNG ĐỂ ĐẶT LẠI GIÁ TRỊ CHỌN (Giữ nguyên)
    document.addEventListener('DOMContentLoaded', function() {
        var bankTransferModal = document.getElementById('bankTransferModal');
        var methodSelect = document.getElementById('method');

        bankTransferModal.addEventListener('hidden.bs.modal', function() {
            if (methodSelect.value === 'Bank') {
                methodSelect.value = 'COD';
            }
        });

        // Xử lý form submit
        var checkoutForm = document.querySelector('.checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(e) {
                // Kiểm tra giỏ hàng
                var cartItems = <?php echo json_encode($displayCartItems); ?>;
                if (!cartItems || cartItems.length === 0) {
                    e.preventDefault();
                    alert('Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm vào giỏ hàng trước khi đặt hàng.');
                    return false;
                }

                // Kiểm tra các trường bắt buộc
                var name = document.getElementById('name').value.trim();
                var phone = document.getElementById('phone').value.trim();
                var address = document.getElementById('address').value.trim();
                var city = document.getElementById('city').value;
                var ward = document.getElementById('ward').value;

                if (!name) {
                    e.preventDefault();
                    alert('Vui lòng nhập họ và tên.');
                    document.getElementById('name').focus();
                    return false;
                }

                if (!phone) {
                    e.preventDefault();
                    alert('Vui lòng nhập số điện thoại.');
                    document.getElementById('phone').focus();
                    return false;
                }

                if (!address) {
                    e.preventDefault();
                    alert('Vui lòng nhập địa chỉ chi tiết.');
                    document.getElementById('address').focus();
                    return false;
                }

                if (!city) {
                    e.preventDefault();
                    alert('Vui lòng chọn tỉnh/thành phố.');
                    document.getElementById('city').focus();
                    return false;
                }

                if (!ward) {
                    e.preventDefault();
                    alert('Vui lòng chọn xã/phường.');
                    document.getElementById('ward').focus();
                    return false;
                }

                // Nếu tất cả đều hợp lệ, cho phép submit
                return true;
            });
        }
    });
</script>