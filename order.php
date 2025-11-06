<?php
ob_start();

// Sửa lỗi session: Luôn kiểm tra trước
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
$user_id = @$_SESSION['user_id'];
if (!isset($user_id)) {
    header('location:./login.php');
    exit();
}

// Dùng require_once để an toàn
require_once('header.php');
require_once('./database/DBController.php');
?>




<?php
/* include phần nội dung chính của trang đơn hàng */
    include ('Template/_orders.php');
?>

<?php
// include footer.php file
require_once('footer.php');
?>