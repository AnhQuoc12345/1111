<?php
ob_start();

// 1. LUÔN KIỂM TRA SESSION TRƯỚC
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. DÙNG REQUIRE_ONCE
require_once('header.php'); 
require_once './database/DBController.php'; 

// Khởi tạo $conn
if (!isset($conn) && isset($db)) {
    $conn = $db->con;
}

$user_id = $_SESSION['user_id'] ?? 0;
$messages = []; // Mảng chứa các thông báo lỗi

// ===================================================================
// LẤY DANH SÁCH SẢN PHẨM GIẢM GIÁ TỪ SESSION (GIỐNG HỆT cart.php)
// ===================================================================
$special_products_list = []; 
$current_time = time();

if (isset($_SESSION['special_products']) &&
    isset($_SESSION['special_products_expiry']) &&
    $_SESSION['special_products_expiry'] > $current_time) {
    
    $special_products_list = array_column($_SESSION['special_products'], 'item_id');
}

// ===================================================================
//   PHẦN XỬ LÝ ĐẶT HÀNG (LOGIC POST)
// ===================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $method = $conn->real_escape_string($_POST['method']);
    $address = $conn->real_escape_string($_POST['address']);
    $note = $conn->real_escape_string($_POST['note']);

    // --- TÍNH TOÁN LẠI GIỎ HÀNG (DÙNG LOGIC 10% TỪ SESSION) ---
    $cartItems_processing = [];
    $totalPrice_processing = 0;
    
    $result = $conn->query("SELECT c.*, p.item_name, p.item_price 
                            FROM cart c 
                            JOIN products p ON c.item_id = p.item_id 
                            WHERE c.user_id = $user_id");

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $item_id = $row['item_id'];
            $original_price = (float)$row['item_price'];
            
            // LOGIC TÍNH GIÁ GIẢM (GIỐNG HỆT cart.php)
            $price_to_use = $original_price;
            if (in_array($item_id, $special_products_list)) {
                $price_to_use = $original_price * 0.90; // Giảm 10%
            }
            
            $row['price_to_use'] = $price_to_use; 
            $cartItems_processing[] = $row;
            
            $totalPrice_processing += $price_to_use * $row['quantity'];
        }
    } else {
        $messages[] = 'Giỏ hàng của bạn trống!';
    }

    // --- LƯU VÀO CSDL ---
    if (!empty($cartItems_processing)) {
        
        $conn->begin_transaction();
        try {
            // 3a. Lưu vào bảng 'orders'
            $stmt = $conn->prepare("INSERT INTO orders (user_id, name, email, method, address, note, total_price, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $status = 0;
            $stmt->bind_param("isssssdi", $user_id, $name, $email, $method, $address, $note, $totalPrice_processing, $status);
            $stmt->execute();
            
            $order_id = $stmt->insert_id;

            // 3b. Lưu vào 'order_details'
            $stmt_detail = $conn->prepare("INSERT INTO order_details (order_id, item_id, quantity, price) 
                                            VALUES (?, ?, ?, ?)");
            
            foreach ($cartItems_processing as $item) {
                $stmt_detail->bind_param("iiid", $order_id, $item['item_id'], $item['quantity'], $item['price_to_use']);
                $stmt_detail->execute();
            }

            // 3c. Xóa giỏ hàng
            $conn->query("DELETE FROM cart WHERE user_id = $user_id");
            
            $conn->commit();
            
            // 3d. CHUYỂN HƯỚNG VỀ _order.php (ĐÃ SỬA)
            header('Location: order.php?success=1&order_id=' . $order_id);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $messages[] = 'Đặt hàng thất bại! Lỗi: ' . $e->getMessage();
        }
    }
}

// ===================================================================
//   PHẦN LẤY DỮ LIỆU ĐỂ HIỂN THỊ (LOGIC GET)
// ===================================================================

$displayCartItems = [];
$displayTotalPrice = 0;
$displaySubTotal = 0;
$displayTotalDiscount = 0;

$result_display = $conn->query("SELECT c.*, p.item_name, p.item_price, p.item_image 
                                FROM cart c 
                                JOIN products p ON c.item_id = p.item_id 
                                WHERE c.user_id = $user_id");
if ($result_display) {
    while ($row = $result_display->fetch_assoc()) {
        $item_id = $row['item_id'];
        $original_price = (float)$row['item_price'];
        
        // LOGIC TÍNH GIÁ GIẢM (GIỐNG HỆT cart.php)
        $price_to_use = $original_price;
        if (in_array($item_id, $special_products_list)) {
            $price_to_use = $original_price * 0.90; // Giảm 10%
        }
        
        $row['price_to_use'] = $price_to_use;
        $displayCartItems[] = $row;
        
        $displayTotalPrice += $price_to_use * $row['quantity'];
        $displaySubTotal += $original_price * $row['quantity'];
    }
}
$displayTotalDiscount = $displaySubTotal - $displayTotalPrice;

// ===================================================================
//   GỌI FILE HIỂN THỊ VÀ FOOTER
// ===================================================================

include('Template/_checkout.php');
require_once('footer.php');
?>