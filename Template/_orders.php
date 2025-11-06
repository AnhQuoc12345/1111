<?php
// Mặc định user_id là 0 nếu chưa đăng nhập
$user_id = $_SESSION['user_id'] ?? 0;

// Mảng định nghĩa các trạng thái
$status_map = [
    0 => ['text' => 'Chờ xác nhận', 'color' => '#ffc107'],
    1 => ['text' => 'Đã xác nhận', 'color' => 'blue'],
    2 => ['text' => 'Đang vận chuyển', 'color' => 'orange'],
    3 => ['text' => 'Hoàn thành', 'color' => 'green'],
    4 => ['text' => 'Đã hủy', 'color' => 'red']
];
$default_status = ['text' => 'Không rõ', 'color' => 'black'];

// Xử lý POST (Giữ nguyên)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $order_id = $_POST['order_id'];
    $status = 3;
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $status, $order_id, $user_id);
    $stmt->execute();
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    $status = 4;
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $status, $order_id, $user_id);
    $stmt->execute();
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// ===================================================================
// PHẦN LOGIC MỚI: LẤY, TẠO ID ĐẾM NGƯỢC, VÀ PHÂN LOẠI
// ===================================================================

// 1. Lấy TẤT CẢ đơn hàng (vẫn sắp xếp theo ID DESC)
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders_all = []; // Mảng chứa tất cả đơn hàng
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders_all[] = $row;
    }
}

// 2. TẠO MAP ID ĐẾM NGƯỢC (ĐỂ SỬA LỖI)
// Map này sẽ lưu [ID thật] => [ID đếm ngược]
// Ví dụ: [25 => 3, 24 => 2, 23 => 1]
$relative_id_map = []; 
$display_id_counter = count($orders_all); // Bắt đầu đếm ngược từ tổng số đơn
foreach ($orders_all as $order) {
    $relative_id_map[$order['id']] = $display_id_counter;
    $display_id_counter--;
}

// 3. Phân loại đơn hàng vào 5 mảng (như cũ)
$orders_pending = [];   // 0: Chờ xác nhận
$orders_confirmed = []; // 1: Đã xác nhận
$orders_shipping = [];  // 2: Đang vận chuyển
$orders_completed = []; // 3: Hoàn thành
$orders_cancelled = []; // 4: Đã hủy

foreach ($orders_all as $row) {
    switch ($row['status']) {
        case 0: $orders_pending[] = $row; break;
        case 1: $orders_confirmed[] = $row; break;
        case 2: $orders_shipping[] = $row; break;
        case 3: $orders_completed[] = $row; break;
        case 4: $orders_cancelled[] = $row; break;
    }
}

/*
Hàm trợ giúp: renderOrderCard
(Đã thêm $relative_id_map)
*/
function renderOrderCard($order, $status_map, $default_status, $relative_id_map) {
    $current_status = $status_map[$order['status']] ?? $default_status;
    
    // LẤY ID ĐẾM NGƯỢC TỪ MAP
    $display_id_to_show = $relative_id_map[$order['id']]; 
    ?>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <div>
                    <div class="order-header">
                        <h5 class="card-title">Đơn hàng #<?php echo $display_id_to_show; ?></h5>
                    </div>
                    <p class="card-text">Tên người nhận: <?php echo htmlspecialchars($order['name']); ?></p>
                    <p class="card-text">Tổng tiền: <?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</p>
                    <p class="card-text">
                        <strong style="color: <?php echo $current_status['color']; ?>">
                            <?php echo $current_status['text']; ?>
                        </strong>
                    </p>
                    </div>

                <div>
                    <?php if ($order['status'] == 0): // Nút Hủy ?>
                        <form method="POST" style="margin-bottom: 15px;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="cancel_order" class="btn btn-danger">Hủy đơn hàng</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($order['status'] == 2): // Nút Đã nhận hàng ?>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="complete_order" class="btn btn-success">Đã nhận hàng</button>
                        </form>
                    <?php endif; ?>

                    <a href="order_detail.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary mt-2">Xem Chi tiết</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<style>
    /* ... (CSS của bạn giữ nguyên) ... */
    .card { min-height: 399px !important; display: flex; flex-direction: column; justify-content: space-between; }
    .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .card-title { margin-bottom: 0 !important; }
    .order-group-title { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-top: 25px; margin-bottom: 20px; }
</style>

<div class="container my-5">
    <h1 class="text-center mb-4">Danh sách Đơn Hàng Của Bạn</h1>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <?php
    $order_id_message = "";
    // Dùng $relative_id_map để lấy ID đếm ngược
    if (isset($_GET['order_id']) && isset($relative_id_map[$_GET['order_id']])) {
        $order_id_from_url = $_GET['order_id'];
        $display_id_for_message = $relative_id_map[$order_id_from_url];
        $order_id_message = " Mã đơn hàng của bạn là: <strong>#" . $display_id_for_message . "</strong>";
    }
    ?>
    
    <div id="auto-dismiss-alert" class='alert alert-success' role='alert' style='text-align: center;'>
        <strong>Bạn đã đặt hàng thành công!</strong><?php echo $order_id_message; ?>
    </div>

    <script>
        // Chờ 15 giây (15000 mili giây)
        setTimeout(function() {
            // Tìm đến cái hộp thông báo bằng ID
            var alertBox = document.getElementById('auto-dismiss-alert');
            
            // Ẩn nó đi
            if (alertBox) {
                alertBox.style.display = 'none';
            }
        }, 10000); // 10 giây
    </script>

<?php endif; ?>

    <div class="row">
        <?php if (count($orders_all) == 0): ?>
            <div class="col-12 text-center">
                <p class="lead">Bạn chưa có đơn hàng nào.</p>
            </div>
        <?php else: ?>

            <?php if (!empty($orders_pending)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[0]['color']; ?>;">
                        Đơn chờ xác nhận
                    </h3>
                </div>
                <?php foreach ($orders_pending as $order) {
                    // Truyền $relative_id_map vào hàm
                    renderOrderCard($order, $status_map, $default_status, $relative_id_map);
                } ?>
            <?php endif; ?>

            <?php if (!empty($orders_confirmed)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[1]['color']; ?>;">
                        Đơn đã xác nhận
                    </h3>
                </div>
                <?php foreach ($orders_confirmed as $order) {
                    renderOrderCard($order, $status_map, $default_status, $relative_id_map);
                } ?>
            <?php endif; ?>

            <?php if (!empty($orders_shipping)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[2]['color']; ?>;">
                        Đang vận chuyển
                    </h3>
                </div>
                <?php foreach ($orders_shipping as $order) {
                    renderOrderCard($order, $status_map, $default_status, $relative_id_map);
                } ?>
            <?php endif; ?>

            <?php if (!empty($orders_completed)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[3]['color']; ?>;">
                        Đơn hoàn thành
                    </h3>
                </div>
                <?php foreach ($orders_completed as $order) {
                    renderOrderCard($order, $status_map, $default_status, $relative_id_map);
                } ?>
            <?php endif; ?>

            <?php if (!empty($orders_cancelled)): ?>
                <div class="col-12">
                    <h3 class="order-group-title" style="color: <?php echo $status_map[4]['color']; ?>;">
                        Đơn đã hủy
                    </h3>
                </div>
                <?php foreach ($orders_cancelled as $order) {
                    renderOrderCard($order, $status_map, $default_status, $relative_id_map);
                } ?>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>