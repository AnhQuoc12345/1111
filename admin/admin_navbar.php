<?php
// Đảm bảo session được khởi động an toàn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// *** LẤY VAI TRÒ ***
$admin_role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : '';

// *** INCLUDE DB (ĐÚNG ĐƯỜNG DẪN) ***
include_once '../database/DBController.php'; 

// BƯỚC 1: ĐẾM SỐ ĐƠN HÀNG CẦN XỬ LÝ
$pending_count = 0; 

if (isset($conn)) {
    $pending_orders_query = mysqli_query($conn, "SELECT COUNT(*) AS pending_count FROM `orders` WHERE status < 3") or die('Query failed');
    $pending_count_data = mysqli_fetch_assoc($pending_orders_query);
    $pending_count = $pending_count_data['pending_count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Navbar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./admin_style.css">
</head>

<body>

    <div class="sidebar" style="background-color: #667791;">
        <div class="logo">
            <img width="100" src="../assets/logo.png" alt="Logo" width="80">
        </div>

        <?php if ($admin_role == 'admin') : ?>
            <a style="margin-bottom: 5px;" href="admin_statistical.php">
                <i class="menu-icon fas fa-chart-bar"></i> Thống kê
            </a>
            <a style="margin-bottom: 5px;" href="admin_products.php">
                <i class="menu-icon fas fa-box"></i> Sản phẩm
            </a>
            <a style="margin-bottom: 5px;" href="admin_categories.php">
                <i class="menu-icon fas fa-list"></i> Danh mục
            </a>
        <?php endif; ?>

        <a style="margin-bottom: 5px;" class="d-flex justify-content-between align-items-center" href="admin_orders.php">
            <span> <i class="menu-icon fas fa-shopping-cart"></i> Đơn hàng
            </span>
            
            <?php
            // Hiển thị badge (thông báo số) nếu có đơn hàng cần xử lý
            if ($pending_count > 0) {
                echo '<span class="badge bg-danger rounded-pill">' . $pending_count . '</span>';
            }
            ?>
        </a>
        
        <?php if ($admin_role == 'admin') : ?>
            <a style="margin-bottom: 5px;" href="admin_accounts.php">
                <i class="menu-icon fas fa-user"></i> Tài khoản
            </a>
        <?php endif; ?>
        
        <a style="margin-bottom: 5px;" href="../logout.php" class="btn btn-danger logout-btn">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Lấy vai trò từ PHP (Đã được truyền xuống)
        const userRole = "<?php echo $admin_role; ?>";
        
        // Lấy tất cả các link trong sidebar
        const sidebarLinks = document.querySelectorAll('.sidebar a');

        // *** ĐÃ SỬA: XÁC ĐỊNH TRANG MẶC ĐỊNH DỰA TRÊN VAI TRÒ ***
        let defaultPage = (userRole === 'admin') ? 'admin_statistical.php' : 'admin_orders.php'; 
        
        const activePage = localStorage.getItem('activePage') || defaultPage;

        // Gán class 'active' cho link tương ứng với trang hiện tại
        sidebarLinks.forEach(link => {
            if (link.getAttribute('href') === activePage) {
                link.classList.add('active');
            }
        });

        // Thêm sự kiện click cho từng link
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                
                // Xử lý nút Đăng xuất
                if (this.classList.contains('logout-btn')) {
                    localStorage.removeItem('activePage');
                    return; // Dừng lại, không lưu gì cả
                }

                // Xóa class 'active' khỏi tất cả các link
                sidebarLinks.forEach(item => item.classList.remove('active'));

                // Thêm class 'active' vào link được click
                this.classList.add('active');

                // Lưu trang hiện tại vào localStorage
                localStorage.setItem('activePage', this.getAttribute('href'));
            });
        });
    </script>
    </body>

</html>