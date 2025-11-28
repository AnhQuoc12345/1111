<?php
// orders.php (Toàn bộ file hiển thị danh sách đơn hàng của user)

// 1) Khởi session an toàn
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Kết nối DB (DBController nên gán $db->con hoặc $conn)
require_once __DIR__ . '/../database/DBController.php';
if (!isset($conn) && isset($db)) {
    $conn = $db->con;
}

// 3) Lấy user_id (guest sẽ có 0)
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// 4) Map trạng thái hiển thị
$status_map = [
    0 => ['text' => 'Chờ xác nhận', 'color' => '#ffc107'],
    1 => ['text' => 'Đã xác nhận', 'color' => 'blue'],
    2 => ['text' => 'Đang vận chuyển', 'color' => 'orange'],
    3 => ['text' => 'Hoàn thành', 'color' => 'green'],
    4 => ['text' => 'Đã hủy', 'color' => 'red']
];
$default_status = ['text' => 'Không rõ', 'color' => 'black'];

// 5) XỬ LÝ POST: Hủy đơn / Hoàn thành (chỉ khi user có quyền)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Hủy đơn
    if (isset($_POST['cancel_order'])) {
        $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        if ($order_id > 0 && $user_id > 0) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
            if ($stmt) {
                $status = 4;
                $stmt->bind_param("iii", $status, $order_id, $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Đánh dấu đã nhận hàng / hoàn thành
    if (isset($_POST['complete_order'])) {
        $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        if ($order_id > 0 && $user_id > 0) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
            if ($stmt) {
                $status = 3;
                $stmt->bind_param("iii", $status, $order_id, $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 6) LẤY TẤT CẢ ĐƠN HÀNG CỦA USER (sắp xếp DESC)
$orders_all = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $orders_all[] = $row;
    }
    $stmt->close();
}

// 7) Tạo map ID đếm ngược (display id)
$relative_id_map = [];
$total_orders = count($orders_all);
$counter = $total_orders;
foreach ($orders_all as $order) {
    $relative_id_map[$order['id']] = $counter;
    $counter--;
}

// 8) Phân loại đơn hàng theo status
$orders_pending = $orders_confirmed = $orders_shipping = $orders_completed = $orders_cancelled = [];
foreach ($orders_all as $row) {
    switch ((int)$row['status']) {
        case 0: $orders_pending[] = $row; break;
        case 1: $orders_confirmed[] = $row; break;
        case 2: $orders_shipping[] = $row; break;
        case 3: $orders_completed[] = $row; break;
        case 4: $orders_cancelled[] = $row; break;
        default: break;
    }
}

// 9) Hàm render card (hiển thị tên, sđt, địa chỉ, tổng tiền)
function renderOrderCard($order, $status_map, $default_status, $relative_id_map) {
    $current_status = $status_map[(int)$order['status']] ?? $default_status;
    $display_id_to_show = isset($relative_id_map[$order['id']]) ? $relative_id_map[$order['id']] : $order['id'];

    $name = htmlspecialchars($order['name'] ?? '—');
    $phone = htmlspecialchars($order['phone'] ?? '—');
    $address = htmlspecialchars($order['address'] ?? '—');
    $total_price = isset($order['total_price']) ? (float)$order['total_price'] : 0.0;
    $order_real_id = htmlspecialchars($order['id']);

    // Rút gọn địa chỉ nếu quá dài
    $max_addr_len = 140;
    $display_address = mb_strlen($address) > $max_addr_len ? mb_substr($address, 0, $max_addr_len) . '...' : $address;
    ?>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body d-flex flex-column justify-content-between" style="min-height:260px;">
                <div>
                    <div class="order-header d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="card-title mb-0">Đơn hàng #<?php echo $display_id_to_show; ?></h5>
                            <!-- <small class="text-muted">ID thực: <?php echo $order_real_id; ?></small> -->
                        </div>
                        <div style="text-align:right;">
                            <span style="font-weight:700; color:<?php echo $current_status['color']; ?>;">
                                <?php echo htmlspecialchars($current_status['text']); ?>
                            </span>
                        </div>
                    </div>

                    <p class="card-text mb-1"><strong>Tên người nhận:</strong> <?php echo $name; ?></p>
                    <p class="card-text mb-1"><strong>Số điện thoại:</strong> <?php echo $phone; ?></p>
                    <!-- <p class="card-text mb-2"><strong>Địa chỉ:</strong> <?php echo $display_address; ?></p> -->

                    <p class="card-text">
                        <strong>Tổng tiền:</strong>
                        <span style="color:#d6333e; font-weight:700;">
                            <?php echo number_format($total_price, 0, ',', '.'); ?> đ
                        </span>
                    </p>
                </div>

                <div class="mt-3 d-flex flex-wrap">
                    <?php if ((int)$order['status'] === 0): ?>
                        <form method="POST" style="margin-right:8px;">
                            <input type="hidden" name="order_id" value="<?php echo $order_real_id; ?>">
                            <button type="submit" name="cancel_order" class="btn btn-danger">Hủy đơn</button>
                        </form>
                    <?php endif; ?>

                    <?php if ((int)$order['status'] === 2): ?>
                        <form method="POST" style="margin-right:8px;">
                            <input type="hidden" name="order_id" value="<?php echo $order_real_id; ?>">
                            <button type="submit" name="complete_order" class="btn btn-success">Đã nhận hàng</button>
                        </form>
                    <?php endif; ?>

                    <a href="order_detail.php?order_id=<?php echo urlencode($order_real_id); ?>" class="btn btn-primary">Xem chi tiết</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đơn hàng của tôi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card { min-height: 260px; display: flex; flex-direction: column; justify-content: space-between; }
        .order-header { margin-bottom: 10px; }
        .order-group-title { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-top: 25px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container my-5">
    <h1 class="text-center mb-4">Danh sách Đơn Hàng Của Bạn</h1>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): 
        $order_id_message = "";
        if (isset($_GET['order_id']) && isset($relative_id_map[$_GET['order_id']])) {
            $order_id_from_url = (int)$_GET['order_id'];
            $display_id_for_message = $relative_id_map[$order_id_from_url];
            $order_id_message = " Mã đơn hàng của bạn là: <strong>#" . $display_id_for_message . "</strong>";
        }
    ?>
        <div id="auto-dismiss-alert" class='alert alert-success text-center' role='alert'>
            <strong>Bạn đã đặt hàng thành công!</strong><?php echo $order_id_message; ?>
        </div>

        <script>
            setTimeout(function() {
                var alertBox = document.getElementById('auto-dismiss-alert');
                if (alertBox) alertBox.style.display = 'none';
            }, 10000); // 10s
        </script>
    <?php endif; ?>

    <div class="row">
        <?php if (empty($orders_all)): ?>
            <div class="col-12 text-center">
                <p class="lead">Bạn chưa có đơn hàng nào.</p>
            </div>
        <?php else: ?>

            <?php if (!empty($orders_pending)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[0]['color']; ?>;">Đơn chờ xác nhận</h3>
                </div>
                <?php foreach ($orders_pending as $order) renderOrderCard($order, $status_map, $default_status, $relative_id_map); ?>
            <?php endif; ?>

            <?php if (!empty($orders_confirmed)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[1]['color']; ?>;">Đơn đã xác nhận</h3>
                </div>
                <?php foreach ($orders_confirmed as $order) renderOrderCard($order, $status_map, $default_status, $relative_id_map); ?>
            <?php endif; ?>

            <?php if (!empty($orders_shipping)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[2]['color']; ?>;">Đang vận chuyển</h3>
                </div>
                <?php foreach ($orders_shipping as $order) renderOrderCard($order, $status_map, $default_status, $relative_id_map); ?>
            <?php endif; ?>

            <?php if (!empty($orders_completed)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[3]['color']; ?>;">Đơn hoàn thành</h3>
                </div>
                <?php foreach ($orders_completed as $order) renderOrderCard($order, $status_map, $default_status, $relative_id_map); ?>
            <?php endif; ?>

            <?php if (!empty($orders_cancelled)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[4]['color']; ?>;">Đơn đã hủy</h3>
                </div>
                <?php foreach ($orders_cancelled as $order) renderOrderCard($order, $status_map, $default_status, $relative_id_map); ?>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
