<?php

// ĐẶT Ở DÒNG ĐẦU TIÊN CỦA FILE
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Kiểm tra 1: Đã đăng nhập chưa?
// Kiểm tra 2: Vai trò có phải là 'admin' HOẶC 'user' không?
// if (!isset($_SESSION['admin_role']) || ($_SESSION['admin_role'] != 'admin' && $_SESSION['admin_role'] != 'user')) {
//     // Nếu không phải 2 vai trò trên -> đá về trang login
//     header('Location: ../login.php'); // Giả sử login.php ở thư mục ngoài
//     exit;
// }
if (!isset($_SESSION['admin_role']) || 
    ($_SESSION['admin_role'] != 'admin' && 
     $_SESSION['admin_role'] != 'staff' && 
     $_SESSION['admin_role'] != 'user')) 
{
    // Nếu không phải một trong 3 vai trò trên -> đá về trang login
    header('Location: ../login.php'); // Giả sử login.php ở thư mục ngoài
    exit;
}

// Phải include DBController SAU khi kiểm tra
// include 'database/DBController.php';

include '../database/DBController.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:../login.php');
    exit();
}

// ==========================================================
// BẮT ĐẦU: KHỐI LOGIC CẬP NHẬT TRẠNG THÁI (ĐÃ VIẾT LẠI)
// ==========================================================
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = (int)$_POST['status']; // Chuyển sang kiểu số để so sánh

    // 1. Lấy trạng thái cũ của đơn hàng
    $get_old_status_query = mysqli_query($conn, "SELECT status FROM `orders` WHERE id = '$order_id'") or die('Query failed');
    $order_data = mysqli_fetch_assoc($get_old_status_query);
    $old_status = (int)$order_data['status'];

    // 2. Chỉ thực hiện nếu trạng thái mới khác trạng thái cũ
    if ($new_status !== $old_status) {
        
        $deducted_states = [1, 2, 3]; // Các trạng thái ĐÃ trừ kho
        $pending_state = 0;           // Trạng thái CHỜ
        $canceled_state = 4;        // Trạng thái HỦY

        $can_update_status = true; // Biến cờ để kiểm tra xem có được phép cập nhật
        $stock_message = '';       // Tin nhắn đi kèm

        // ----------------------------------------------------
        // TRƯỜNG HỢP 1: TRỪ KHO (Khi chuyển từ 0 -> 1, 2, hoặc 3)
        // ----------------------------------------------------
        if ($old_status == $pending_state && in_array($new_status, $deducted_states)) {
            
            // Bắt đầu Transaction
            mysqli_begin_transaction($conn);
            
            try {
                $order_details_query = mysqli_query($conn, "SELECT * FROM `order_details` WHERE order_id = '$order_id'") or die('Query failed');
                
                while ($order_detail = mysqli_fetch_assoc($order_details_query)) {
                    $item_id = $order_detail['item_id'];
                    $quantity_to_subtract = $order_detail['quantity'];

                    // Kiểm tra kho (với FOR UPDATE để khóa dòng, đảm bảo an toàn)
                    $product_query = mysqli_query($conn, "SELECT item_quantity FROM `products` WHERE item_id = '$item_id' FOR UPDATE") or die('Query failed');
                    $product = mysqli_fetch_assoc($product_query);

                    if ($product && $product['item_quantity'] >= $quantity_to_subtract) {
                        // Đủ hàng -> Trừ
                        mysqli_query($conn, "UPDATE `products` SET item_quantity = item_quantity - $quantity_to_subtract WHERE item_id = '$item_id'") or die('Query failed');
                    } else {
                        // Không đủ hàng
                        throw new Exception("Không đủ số lượng sản phẩm (ID: $item_id) để trừ kho!");
                    }
                }
                
                // Nếu không có lỗi, commit (lưu) tất cả thay đổi
                mysqli_commit($conn);
                $stock_message = 'Đã xác nhận và trừ kho thành công!';

            } catch (Exception $e) {
                // Nếu có lỗi, rollback (hủy) tất cả
                mysqli_rollback($conn);
                $can_update_status = false; // Không cho phép cập nhật trạng thái đơn hàng
                $message[] = 'Cập nhật thất bại: ' . $e->getMessage();
            }

        // ----------------------------------------------------
        // TRƯỜNG HỢP 2: CỘNG LẠI KHO (Khi chuyển từ 1, 2, 3 -> 4)
        // ----------------------------------------------------
        } elseif (in_array($old_status, $deducted_states) && $new_status == $canceled_state) {
            
            $order_details_query = mysqli_query($conn, "SELECT * FROM `order_details` WHERE order_id = '$order_id'") or die('Query failed');
            while ($order_detail = mysqli_fetch_assoc($order_details_query)) {
                $item_id = $order_detail['item_id'];
                $quantity_to_add = $order_detail['quantity'];
                
                // CỘNG TRẢ LẠI SỐ LƯỢNG
                mysqli_query($conn, "UPDATE `products` SET item_quantity = item_quantity + $quantity_to_add WHERE item_id = '$item_id'") or die('Query failed');
            }
            $stock_message = 'Đã hủy đơn và hoàn trả kho thành công!';
        }

        // 3. Cập nhật trạng thái đơn hàng (Nếu được phép)
        if ($can_update_status) {
            $update_status_query = mysqli_query($conn, "UPDATE `orders` SET status = '$new_status' WHERE id = '$order_id'") or die('Query failed');
            if ($update_status_query) {
                $message[] = 'Cập nhật trạng thái đơn hàng thành công! ' . $stock_message;
            } else {
                $message[] = 'Cập nhật trạng thái đơn hàng thất bại!';
            }
        }
    }
}
// ==========================================================
// KẾT THÚC: KHỐI LOGIC CẬP NHẬT TRẠNG THÁI
// ==========================================================


// ==========================================================
// BẮT ĐẦU: THÊM CODE PHÂN TRANG VÀ ĐẾM TỔNG SỐ
// ==========================================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Giới hạn 10 đơn hàng mỗi trang
$offset = ($page - 1) * $limit; 

$total_orders_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `orders`") or die('Query failed');
$total_orders = mysqli_fetch_assoc($total_orders_query)['total']; // <-- Sẽ dùng biến này

$total_pages = ceil($total_orders / $limit);
// ==========================================================
// KẾT THÚC: CODE PHÂN TRANG
// ==========================================================

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/admin_style.css"> </head>

<body>
    <div class="d-flex">
        <?php include 'admin_navbar.php'; ?>
        <div class="manage-container">
            <?php
            // Hiển thị thông báo sau khi thao tác
            if (isset($message)) {
                foreach ($message as $msg) {
                    echo '
                    <div class=" alert alert-info alert-dismissible fade show" role="alert">
                        <span style="font-size: 16px;">' . $msg . '</span>
                        <i style="font-size: 20px; cursor: pointer" class="fas fa-times" onclick="this.parentElement.remove();"></i>
                    </div>';
                }
            }
            ?>
            <div class="bg-primary text-white text-center py-2 mb-4 shadow">
                <h1 class="mb-0">Quản lý Đơn hàng</h1>
            </div>
            <section class="show-orders">
                <div class="container">
                    <h1 class="text-center">Danh sách Đơn hàng</h1>
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Tên khách hàng</th>
                                <th>Email</th>
                                <th>Địa chỉ</th>
                                <th>Phương thức thanh toán</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php
                            // THAY ĐỔI 1: Thêm "LIMIT $limit OFFSET $offset"
                            $select_orders = mysqli_query($conn, "SELECT * FROM `orders` ORDER BY id DESC LIMIT $limit OFFSET $offset") or die('Query failed');
                            if (mysqli_num_rows($select_orders) > 0) {
                                
                                // THAY ĐỔI 2: Tính STT đếm lùi
                                $stt = $total_orders - $offset; 
                                
                                while ($order = mysqli_fetch_assoc($select_orders)) {
                            ?>
                                    <tr>
                                        <td><?php echo $stt; ?></td>
                                        <td><?php echo $order['name']; ?></td>
                                        <td><?php echo $order['email']; ?></td>
                                        <td><?php echo $order['address']; ?></td>
                                        <td><?php echo $order['method']; ?></td>
                                        <td><?php echo number_format($order['total_price'], 0, ',', '.'); ?> VND</td>
                                        <td>
                                            <?php 
                                            // Sửa logic hiển thị trạng thái
                                            if ($order['status'] == 4) {
                                                echo '<span style="color: red;"> Đã hủy</span>';
                                            } elseif ($order['status'] == 3) {
                                                echo '<span style="color: green;">Đã hoàn thành</span>';
                                            } else { 
                                            ?>
                                            <form action="admin_orders.php?page=<?php echo $page; ?>" method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="status" class="form-select">
                                                    <?php
                                                    $status = $order['status'];
                                                    if ($status == 0) {
                                                        echo '
                                                        <option value="0" selected>Chờ xác nhận</option>
                                                        <option value="1">Xác nhận</option>
                                                        <option value="2">Vận chuyển</option>
                                                        <option value="3">Hoàn thành</option>
                                                        <option value="4">Hủy</option>';
                                                    } elseif ($status == 1) {
                                                        echo '
                                                        <option value="1" selected> Đã xác nhận</option>
                                                        <option value="2">Vận chuyển</option>
                                                        <option value="3">Hoàn thành</option>
                                                        <option value="4">Hủy</option>'; // Thêm tùy chọn Hủy
                                                    } elseif ($status == 2) {
                                                        echo '
                                                        <option value="2" selected>Đang vận chuyển</option>
                                                        <option value="3">Hoàn thành</option>
                                                        <option value="4">Hủy</option>'; // Thêm tùy chọn Hủy
                                                    }
                                                    ?>
                                                </select>
                                                <input type="submit" name="update_status" value="Cập nhật" class="btn btn-success btn-sm mt-2" style="width: 100%;">
                                            </form>
                                            <?php } ?>
                                        </td>
                                    </tr>
                            <?php
                                    $stt--; // THAY ĐỔI 5: Đếm lùi STT
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center">Chưa có đơn hàng nào.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>

                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="admin_orders.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="admin_orders.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php } ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="admin_orders.php?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                </div>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>