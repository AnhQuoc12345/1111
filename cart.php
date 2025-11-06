<?php
ob_start();
session_start();

// include header.php file
include ('header.php'); 

// 1. XÓA CÁC DÒNG INCLUDE TRÙNG LẶP
// File "Product.php" và "Cart.php" đã được "header.php" tải rồi.
// Xóa các dòng này đi để hết lỗi "already in use":
// include ('./database/DBController.php');
// include ('./database/Product.php');
// include ('./database/Cart.php');

// 2. KHỞI TẠO CÁC ĐỐI TƯỢNG ĐỂ SỬA LỖI GỐC "Undefined variable $product"
// Giả sử file header.php của bạn đã tạo biến kết nối $db (từ "new DBController()")

// Nếu header.php CHƯA tạo $product và $Cart, bạn phải tạo chúng ở đây:
if (!isset($product)){
    $product = new Product($db); // $db phải tồn tại từ header.php
}
if (!isset($Cart)){
    $Cart = new Cart($db);     // $db phải tồn tại từ header.php
}

?>

<?php

    /* include cart items if it is not empty */
    // Bây giờ $product đã tồn tại và sẽ chạy đúng
    count($product->getData('cart')) ? include ('Template/_cart-template.php') :  include ('Template/notFound/_cart_notFound.php');
    
?>

<?php
// include footer.php file
include ('footer.php');
?>