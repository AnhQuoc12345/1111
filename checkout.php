<?php
ob_start();

// 1. LUÔN KIỂM TRA SESSION TRƯỚC
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. DÙNG REQUIRE_ONCE
require_once('header.php');
require_once './database/DBController.php'; // giả sử DBController tạo $db và $db->con

// Nếu bạn có helper SMS (Twilio) đặt ở includes/helpers_sms.php, uncomment dòng sau:
// require_once __DIR__ . '/includes/helpers_sms.php';

if (!isset($conn) && isset($db) && isset($db->con)) {
    $conn = $db->con;
}
// note
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$messages = []; // Mảng chứa các thông báo lỗi

// -------------------- HÀM GỬI SMS (STUB / REPLACE WHEN READY) --------------------
// Nếu bạn require helper Twilio, hàm send_sms sẽ được nạp từ helper.
// Nếu không, stub bên dưới sẽ ghi message vào error_log và trả true.
if (!function_exists('send_sms')) {
    function send_sms($phone, $message) {
        // Development stub: log message. Replace with real provider in production.
        error_log("[send_sms stub] To: {$phone} | Msg: " . $message);
        return true;
    }
}
// -------------------------------------------------------------------------------

/**
 * Kiểm tra xem một cột có tồn tại trong bảng hay không
 */
function column_exists($conn, $table, $column) {
    $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = false;
        if ($res && $row = $res->fetch_assoc()) {
            $exists = ((int)$row['cnt'] > 0);
        }
        $stmt->close();
        return $exists;
    }
    return false;
}

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
//  LẤY THÔNG TIN USER (CHỈ NHỮNG CỘT TỒN TẠI) ĐỂ AUTOFILL NAME/EMAIL/PHONE
// ===================================================================
$prefill_name = $prefill_email = $prefill_phone = '';

if ($user_id > 0 && isset($conn)) {
    $sql_user = "SELECT user_id, username, email, phone FROM users WHERE user_id = ? LIMIT 1";
    if ($stmt_user = $conn->prepare($sql_user)) {
        $stmt_user->bind_param('i', $user_id);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();
        if ($res_user && $row_user = $res_user->fetch_assoc()) {
            $prefill_name  = $row_user['username'] ?? '';
            $prefill_email = $row_user['email'] ?? '';
            $prefill_phone = $row_user['phone'] ?? '';
        }
        $stmt_user->close();
    } else {
        error_log('Prepare user failed in checkout.php: ' . ($conn->error ?? 'no conn'));
    }
}

// ===================================================================
//  PHẦN XỬ LÝ ĐẶT HÀNG (LOGIC POST)
//  - User logged in: tạo order như hiện tại
//  - Guest: tạo order NGAY (không OTP) + gửi SMS tóm tắt đơn cho cửa hàng
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {

    if (!isset($conn)) {
        $messages[] = 'Lỗi kết nối cơ sở dữ liệu.';
    } else {
        // Lấy input & trim
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $method  = trim($_POST['method'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $note    = trim($_POST['note'] ?? '');

        // Validate cơ bản
        if ($name === '' || $phone === '' || $address === '') {
            $messages[] = 'Vui lòng điền đầy đủ các trường bắt buộc (Họ tên, Số điện thoại, Địa chỉ).';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messages[] = 'Email không hợp lệ.';
        } else {
            // Validate phone đơn giản: cho phép số, dấu +, -, space; độ dài 7-20
            if (!preg_match('/^[0-9\+\-\s]{7,20}$/', $phone)) {
                $messages[] = 'Số điện thoại không hợp lệ. Vui lòng nhập 7–20 ký tự, gồm chữ số và có thể có dấu + hoặc -';
            }
        }

        // Nếu có lỗi validate thì không tiếp tục
        if (empty($messages)) {
            // --- TÍNH TOÁN LẠI GIỎ HÀNG (dành cho cả user & guest) ---
            $cartItems_processing = [];
            $totalPrice_processing = 0.0;

            if ($user_id > 0) {
                // Nếu user đăng nhập, lấy cart từ DB (như code hiện tại)
                $sql_cart = "SELECT c.*, p.item_name, p.item_price 
                             FROM cart c 
                             JOIN products p ON c.item_id = p.item_id 
                             WHERE c.user_id = ?";
                if ($stmt_cart = $conn->prepare($sql_cart)) {
                    $stmt_cart->bind_param('i', $user_id);
                    $stmt_cart->execute();
                    $res_cart = $stmt_cart->get_result();
                    if ($res_cart && $res_cart->num_rows > 0) {
                        while ($row = $res_cart->fetch_assoc()) {
                            $item_id = (int)$row['item_id'];
                            $original_price = (float)$row['item_price'];

                            // LOGIC TÍNH GIÁ GIẢM (GIỐNG HỆT cart.php)
                            $price_to_use = $original_price;
                            if (in_array($item_id, $special_products_list)) {
                                $price_to_use = $original_price * 0.90; // Giảm 10%
                            }

                            $row['price_to_use'] = $price_to_use;
                            $cartItems_processing[] = $row;

                            $quantity = isset($row['quantity']) ? (int)$row['quantity'] : 1;
                            $totalPrice_processing += $price_to_use * $quantity;
                        }
                    } else {
                        $messages[] = 'Giỏ hàng của bạn trống!';
                    }
                    $stmt_cart->close();
                } else {
                    $messages[] = 'Lỗi khi lấy giỏ hàng: ' . htmlspecialchars($conn->error);
                }
            } else {
                // Guest: lấy giỏ hàng từ session (guest_cart)
                $session_cart = $_SESSION['guest_cart'] ?? [];
                if (empty($session_cart)) {
                    $messages[] = 'Giỏ hàng của bạn trống!';
                } else {
                    // normalize numeric indices
                    $session_cart = array_values($session_cart);
                    foreach ($session_cart as $ci) {
                        $item_id = (int)($ci['item_id'] ?? 0);
                        $qty = (int)($ci['quantity'] ?? 1);
                        if ($item_id <= 0) continue;

                        // lấy giá thực tế từ DB để tránh giả giá client-side
                        $price = 0.0;
                        $iname = '';
                        $pstmt = $conn->prepare("SELECT item_price, item_name FROM products WHERE item_id = ? LIMIT 1");
                        if ($pstmt) {
                            $pstmt->bind_param('i', $item_id);
                            $pstmt->execute();
                            $pres = $pstmt->get_result();
                            if ($prow = $pres->fetch_assoc()) {
                                $price = (float)$prow['item_price'];
                                $iname = $prow['item_name'];
                            }
                            $pstmt->close();
                        }

                        $price_to_use = $price;
                        if (in_array($item_id, $special_products_list)) {
                            $price_to_use = $price * 0.90;
                        }

                        $row = [
                            'item_id' => $item_id,
                            'quantity' => $qty,
                            'item_price' => $price,
                            'item_name' => $iname,
                            'price_to_use' => $price_to_use
                        ];
                        $cartItems_processing[] = $row;
                        $totalPrice_processing += $price_to_use * $qty;
                    }
                }
            }

            // --- nếu không có lỗi và có item thì tiếp tục ---
            if (empty($messages) && !empty($cartItems_processing)) {

                if ($user_id > 0) {
                    // ===========  EXISTING LOGIC: lưu order ngay cho user đã đăng nhập ===========
                    $conn->begin_transaction();
                    try {
                        // 1) Lưu vào bảng 'orders' (đã thêm phone)
                        $sql_insert_order = "INSERT INTO orders (user_id, name, email, phone, method, address, note, total_price, status) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt_order = $conn->prepare($sql_insert_order);
                        if (!$stmt_order) {
                            throw new Exception('Prepare order failed: ' . $conn->error);
                        }

                        // types: i (user_id), s name, s email, s phone, s method, s address, s note, d total_price, i status
                        $types = "issssssdi"; // i + 6s + d + i

                        $u_user_id     = $user_id;
                        $u_name        = $name;
                        $u_email       = $email;
                        $u_phone       = $phone;
                        $u_method      = $method;
                        $u_address     = $address;
                        $u_note        = $note;
                        $u_totalPrice  = (float)$totalPrice_processing;
                        $u_status      = 0;

                        if (!$stmt_order->bind_param($types,
                            $u_user_id,
                            $u_name,
                            $u_email,
                            $u_phone,
                            $u_method,
                            $u_address,
                            $u_note,
                            $u_totalPrice,
                            $u_status
                        )) {
                            throw new Exception('Bind order params failed: ' . $stmt_order->error);
                        }

                        if (!$stmt_order->execute()) {
                            throw new Exception('Execute order failed: ' . $stmt_order->error);
                        }

                        $order_id = $conn->insert_id;
                        $stmt_order->close();

                        // 2) Lưu vào 'order_details'
                        $stmt_detail = $conn->prepare("INSERT INTO order_details (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
                        if (!$stmt_detail) {
                            throw new Exception('Prepare order_details failed: ' . $conn->error);
                        }

                        foreach ($cartItems_processing as $item) {
                            $o_order_id = (int)$order_id;
                            $o_item_id = (int)$item['item_id'];
                            $o_quantity = (int)$item['quantity'];
                            $o_price = (float)$item['price_to_use'];

                            if (!$stmt_detail->bind_param("iiid", $o_order_id, $o_item_id, $o_quantity, $o_price)) {
                                throw new Exception('Bind order_detail failed: ' . $stmt_detail->error);
                            }
                            if (!$stmt_detail->execute()) {
                                throw new Exception('Execute order_detail failed: ' . $stmt_detail->error);
                            }
                        }
                        $stmt_detail->close();

                        // 3) Xóa giỏ hàng
                        $stmt_del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                        if ($stmt_del) {
                            $stmt_del->bind_param('i', $user_id);
                            $stmt_del->execute();
                            $stmt_del->close();
                        } else {
                            // fallback query (less safe)
                            $conn->query("DELETE FROM cart WHERE user_id = " . (int)$user_id);
                        }

                        $conn->commit();

                        // 4) CHUYỂN HƯỚNG VỀ order.php
                        header('Location: order.php?success=1&order_id=' . (int)$order_id);
                        exit();

                    } catch (Exception $e) {
                        $conn->rollback();
                        error_log('Order transaction failed: ' . $e->getMessage());
                        $messages[] = 'Đặt hàng thất bại! Vui lòng thử lại sau hoặc liên hệ quản trị.';
                    }
                    // =======================================================================
                } else {
                    // =========== GUEST FLOW: tạo đơn hàng ngay (user_id = 0 hoặc NULL) ================
                    $conn->begin_transaction();
                    try {
                        // 1) Lưu vào bảng 'orders' với user_id = 0 (hoặc NULL nếu cho phép)
                        $sql_insert_order = "INSERT INTO orders (user_id, name, email, phone, method, address, note, total_price, status) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt_order = $conn->prepare($sql_insert_order);
                        if (!$stmt_order) {
                            throw new Exception('Prepare order failed: ' . $conn->error);
                        }

                        // types: i (user_id), s name, s email, s phone, s method, s address, s note, d total_price, i status
                        $types = "issssssdi"; // i + 6s + d + i

                        $u_user_id     = 0; // Guest có user_id = 0
                        $u_name        = $name;
                        $u_email       = $email;
                        $u_phone       = $phone;
                        $u_method      = $method;
                        $u_address     = $address;
                        $u_note        = $note;
                        $u_totalPrice  = (float)$totalPrice_processing;
                        $u_status      = 0;

                        if (!$stmt_order->bind_param($types,
                            $u_user_id,
                            $u_name,
                            $u_email,
                            $u_phone,
                            $u_method,
                            $u_address,
                            $u_note,
                            $u_totalPrice,
                            $u_status
                        )) {
                            throw new Exception('Bind order params failed: ' . $stmt_order->error);
                        }

                        if (!$stmt_order->execute()) {
                            throw new Exception('Execute order failed: ' . $stmt_order->error);
                        }

                        $order_id = $conn->insert_id;
                        $stmt_order->close();

                        // 2) Lưu vào 'order_details'
                        $stmt_detail = $conn->prepare("INSERT INTO order_details (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
                        if (!$stmt_detail) {
                            throw new Exception('Prepare order_details failed: ' . $conn->error);
                        }

                        foreach ($cartItems_processing as $item) {
                            $o_order_id = (int)$order_id;
                            $o_item_id = (int)$item['item_id'];
                            $o_quantity = (int)$item['quantity'];
                            $o_price = (float)$item['price_to_use'];

                            if (!$stmt_detail->bind_param("iiid", $o_order_id, $o_item_id, $o_quantity, $o_price)) {
                                throw new Exception('Bind order_detail failed: ' . $stmt_detail->error);
                            }
                            if (!$stmt_detail->execute()) {
                                throw new Exception('Execute order_detail failed: ' . $stmt_detail->error);
                            }
                        }
                        $stmt_detail->close();

                        // 3) Xóa giỏ hàng guest khỏi session
                        if (isset($_SESSION['guest_cart'])) {
                            unset($_SESSION['guest_cart']);
                        }

                        $conn->commit();

                        // 4) CHUYỂN HƯỚNG VỀ trang thành công (có thể tạo trang riêng hoặc dùng order.php)
                        header('Location: order.php?success=1&order_id=' . (int)$order_id . '&guest=1');
                        exit();

                    } catch (Exception $e) {
                        $conn->rollback();
                        error_log('Guest order transaction failed: ' . $e->getMessage());
                        $messages[] = 'Đặt hàng thất bại! Vui lòng thử lại sau hoặc liên hệ quản trị.';
                    }
                    // =======================================================================
                } // end guest/user branch
            }
        }
    }
}

// ===================================================================
//  PHẦN LẤY DỮ LIỆU ĐỂ HIỂN THỊ (LOGIC GET)
// ===================================================================
$displayCartItems = [];
$displayTotalPrice = 0.0;
$displaySubTotal = 0.0;
$displayTotalDiscount = 0.0;

if (isset($conn)) {
    if ($user_id > 0) {
        // User đã đăng nhập: lấy từ database
        $sql_display = "SELECT c.*, p.item_name, p.item_price, p.item_image 
                        FROM cart c 
                        JOIN products p ON c.item_id = p.item_id 
                        WHERE c.user_id = ?";
        if ($stmt_disp = $conn->prepare($sql_display)) {
            $stmt_disp->bind_param('i', $user_id);
            $stmt_disp->execute();
            $res_disp = $stmt_disp->get_result();
            if ($res_disp) {
                while ($row = $res_disp->fetch_assoc()) {
                    $item_id = (int)$row['item_id'];
                    $original_price = (float)$row['item_price'];

                    // LOGIC TÍNH GIÁ GIẢM (GIỐNG HỆT cart.php)
                    $price_to_use = $original_price;
                    if (in_array($item_id, $special_products_list)) {
                        $price_to_use = $original_price * 0.90; // Giảm 10%
                    }

                    $row['price_to_use'] = $price_to_use;
                    $displayCartItems[] = $row;

                    $qty = isset($row['quantity']) ? (int)$row['quantity'] : 1;
                    $displayTotalPrice += $price_to_use * $qty;
                    $displaySubTotal += $original_price * $qty;
                }
            }
            $stmt_disp->close();
        } else {
            error_log('Prepare display cart failed: ' . $conn->error);
        }
    } else {
        // Guest: lấy từ session
        $guest_cart = $_SESSION['guest_cart'] ?? [];
        foreach ($guest_cart as $cart_item) {
            $item_id = (int)($cart_item['item_id'] ?? 0);
            $quantity = (int)($cart_item['quantity'] ?? 1);
            
            if ($item_id <= 0) continue;
            
            // Lấy thông tin sản phẩm từ database
            $sql_product = "SELECT item_name, item_price, item_image FROM products WHERE item_id = ? LIMIT 1";
            if ($stmt_prod = $conn->prepare($sql_product)) {
                $stmt_prod->bind_param('i', $item_id);
                $stmt_prod->execute();
                $res_prod = $stmt_prod->get_result();
                if ($res_prod && $row_prod = $res_prod->fetch_assoc()) {
                    $original_price = (float)$row_prod['item_price'];
                    
                    // LOGIC TÍNH GIÁ GIẢM
                    $price_to_use = $original_price;
                    if (in_array($item_id, $special_products_list)) {
                        $price_to_use = $original_price * 0.90; // Giảm 10%
                    }
                    
                    $row = [
                        'item_id' => $item_id,
                        'quantity' => $quantity,
                        'item_name' => $row_prod['item_name'],
                        'item_price' => $original_price,
                        'item_image' => $row_prod['item_image'],
                        'price_to_use' => $price_to_use
                    ];
                    $displayCartItems[] = $row;
                    $displayTotalPrice += $price_to_use * $quantity;
                    $displaySubTotal += $original_price * $quantity;
                }
                $stmt_prod->close();
            }
        }
    }
}
$displayTotalDiscount = $displaySubTotal - $displayTotalPrice;

// ===================================================================
//  LẤY PROVINCES (API) ĐỂ VIEW SỬ DỤNG
// ===================================================================
function get_vietnam_provinces_data() {
    $url = 'https://vietnamlabs.com/api/vietnamprovince';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success'] && isset($data['data'])) {
            return $data['data'];
        }
    }
    return [];
}

$provincesData = get_vietnam_provinces_data();
if (!empty($provincesData)) {
    usort($provincesData, function($a, $b) {
        return strcmp($a['province'], $b['province']);
    });
}

// ===================================================================
//  GỌI FILE HIỂN THỊ (bạn giữ nguyên Template/_checkout.php)
// ===================================================================
include('Template/_checkout.php');

// ===================================================================
//  IN SCRIPT AUTOFILL (chỉ sửa ở controller) - sẽ đổ vào inputs có id name/email/phone
//  (chỉ khi data tồn tại)
// =====================================
