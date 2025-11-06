<?php

// ĐẶT Ở DÒNG ĐẦU TIÊN CỦA FILE
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Kiểm tra 1: Đã đăng nhập chưa?
// Kiểm tra 2: Vai trò có phải là 'admin' không?
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'admin') {
    // Nếu không phải admin -> đá về trang đơn hàng
    header('Location: admin_orders.php');
    exit;
}

// Phải include DBController SAU khi kiểm tra
// // (Vì file login đã gán $conn rồi, các file khác cũng nên làm vậy)
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

// Thống kê dữ liệu
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM `orders`"))['count'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM `products`"))['count'];
$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM `categories`"))['count'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM `users` WHERE role='user'"))['count'];

// Doanh thu trong tháng hiện tại
$current_month = date('Y-m');
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_price) AS revenue FROM `orders` WHERE status = 3 AND DATE_FORMAT(created_at, '%Y-%m') = '$current_month'"))['revenue'];

// Thống kê số lượng đơn hàng theo trạng thái
$order_status_data = mysqli_query($conn, "SELECT status, COUNT(*) AS count FROM `orders` GROUP BY status");
$order_status_chart = [];
while ($row = mysqli_fetch_assoc($order_status_data)) {
    $order_status_chart[] = ['status' => $row['status'], 'count' => $row['count']];
}

// Thống kê số lượng sản phẩm theo danh mục
$product_category_data = mysqli_query($conn, "SELECT c.name AS category, COUNT(p.item_id) AS count 
    FROM `products` p JOIN `categories` c ON p.item_category = c.id GROUP BY c.id");
$product_category_chart = [];
while ($row = mysqli_fetch_assoc($product_category_data)) {
    $product_category_chart[] = ['category' => $row['category'], 'count' => $row['count']];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/admin_style.css">
</head>

<body>
    <div class="d-flex">
        <?php include 'admin_navbar.php'; ?>
        <div class="manage-container">
            <div class="bg-primary text-white text-center py-2 mb-4 shadow">
                <h1 class="mb-0">Trang quản lý thống kê</h1>
            </div>

            <div class="row mb-4  p-4">
                <div class="col-md-3">
                    <div class="card text-center bg-primary text-white">
                        <div class="card-body">
                            <h3><?php echo $total_orders; ?></h3>
                            <p>Đơn hàng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-success text-white">
                        <div class="card-body">
                            <h3><?php echo $total_products; ?></h3>
                            <p>Sản phẩm</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-warning text-white">
                        <div class="card-body">
                            <h3><?php echo $total_categories; ?></h3>
                            <p>Danh mục</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center bg-danger text-white">
                        <div class="card-body">
                            <h3><?php echo $total_users; ?></h3>
                            <p>Người dùng</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-center">Thống kê trạng thái đơn hàng</h5>
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-center">Số lượng sản phẩm theo danh mục</h5>
                            <canvas id="productCategoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Doanh thu tháng này</h5>
                    <h3><?php echo number_format($total_revenue ?? 0, 0, ',', '.'); ?> VND</h3>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Biểu đồ trạng thái đơn hàng
        const orderStatusData = <?php echo json_encode($order_status_chart); ?>;
        const orderStatusLabels = orderStatusData.map(item => {
            const status = parseInt(item.status); // Chuyển đổi sang số nguyên
            switch (status) {
                case 0:
                    return 'Chờ xác nhận';
                case 1:
                    return 'Đã xác nhận';
                case 2:
                    return 'Đang vận chuyển';
                case 3:
                    return 'Hoàn thành';
                case 4:
                    return 'Đã hủy';
                default:
                    return 'Không xác định';
            }
        });
        const orderStatusCounts = orderStatusData.map(item => item.count);

        new Chart(document.getElementById('orderStatusChart'), {
            type: 'pie',
            data: {
                labels: orderStatusLabels,
                datasets: [{
                    data: orderStatusCounts,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
                }]
            }
        });

        // Biểu đồ số lượng sản phẩm theo danh mục
        const productCategoryData = <?php echo json_encode($product_category_chart); ?>;
        const productCategoryLabels = productCategoryData.map(item => item.category);
        const productCategoryCounts = productCategoryData.map(item => item.count);

        new Chart(document.getElementById('productCategoryChart'), {
            type: 'bar',
            data: {
                labels: productCategoryLabels,
                datasets: [{
                    label: 'Số lượng sản phẩm',
                    data: productCategoryCounts,
                    backgroundColor: '#17a2b8'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>

</html>