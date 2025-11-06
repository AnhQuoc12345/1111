<?php
// Bắt đầu session
session_start();

// ==========================================================
// SỬA LỖI 1: Include tệp của bạn để lấy biến $conn
// ==========================================================
require_once './database/DBController.php';
// Bây giờ chúng ta đã có biến $conn (từ file DBController.php)

// ==========================================================
// SỬA LỖI 2: Xóa bỏ dòng khởi tạo $db = new DBController();
// ==========================================================
// $db = new DBController(); // <-- DÒNG NÀY GÂY LỖI, XÓA BỎ

// Mặc định phản hồi là lỗi
$response = [
    'success' => false,
    'message' => 'Lỗi không xác định.'
];

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 1) {
    $response['message'] = 'Bạn cần đăng nhập để đánh giá.';
    echo json_encode($response);
    exit;
}

// Kiểm tra xem có đủ dữ liệu POST không
if (isset($_POST['product_id']) && isset($_POST['rating'])) {
    $product_id = (int)$_POST['product_id'];
    $rating_value = (int)$_POST['rating'];
    $user_id = (int)$_SESSION['user_id'];

    if ($rating_value < 1 || $rating_value > 5) {
        $response['message'] = 'Giá trị đánh giá không hợp lệ.';
        echo json_encode($response);
        exit;
    }

    try {
        // Sử dụng "INSERT ... ON DUPLICATE KEY UPDATE"
        $query = "INSERT INTO `ratings` (product_id, user_id, rating_value) 
                  VALUES ($product_id, $user_id, $rating_value)
                  ON DUPLICATE KEY UPDATE rating_value = $rating_value";
        
        // ==========================================================
        // SỬA LỖI 3: Dùng hàm mysqli_query($conn, ...)
        // ==========================================================
        mysqli_query($conn, $query);

        // --- Sau khi cập nhật đánh giá, tính toán lại điểm trung bình ---
        
        // 1. Lấy điểm trung bình (AVG) và tổng số (COUNT)
        $avg_query = "SELECT AVG(rating_value) as avg_rating, COUNT(*) as total_ratings 
                      FROM `ratings` 
                      WHERE product_id = $product_id";
        
        // ==========================================================
        // SỬA LỖI 4: Dùng hàm mysqli_query và mysqli_fetch_assoc
        // ==========================================================
        $result = mysqli_query($conn, $avg_query);
        $data = mysqli_fetch_assoc($result);
        
        $new_average = (float)($data['avg_rating'] ?? 0);
        $new_count = (int)($data['total_ratings'] ?? 0);

        // 2. Cập nhật lại vào bảng 'products'
        $update_product_query = "UPDATE `products` 
                                 SET average_rating = $new_average, rating_count = $new_count 
                                 WHERE item_id = $product_id";
        
        // ==========================================================
        // SỬA LỖI 5: Dùng hàm mysqli_query($conn, ...)
        // ==========================================================
        mysqli_query($conn, $update_product_query);

        // Gửi phản hồi thành công về cho JavaScript
        $response['success'] = true;
        $response['message'] = 'Cảm ơn bạn đã đánh giá!';
        $response['new_average'] = round($new_average, 1);
        $response['new_count'] = $new_count;

    } catch (mysqli_sql_exception $e) {
        $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Thiếu thông tin sản phẩm hoặc đánh giá.';
}

// Trả về kết quả (dạng JSON)
header('Content-Type: application/json');
echo json_encode($response);
exit;