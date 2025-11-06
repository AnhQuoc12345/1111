<?php
include 'database/DBController.php'; 

// Khởi tạo phiên an toàn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$message = [];

if (isset($_POST['submit'])) {
    
    $email = trim($_POST['email']);
    $password_input = trim($_POST['password']); 

    $stmt = mysqli_prepare($conn, "SELECT * FROM `users` WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        //login 21 22

        if (password_verify($password_input, $user['password'])) {
            
            // 🛑 BƯỚC QUAN TRỌNG: HỦY VÀ KHỞI ĐỘNG LẠI SESSION CŨ
            session_unset();
            session_destroy();
            // Khởi động lại session để lưu thông tin người dùng mới
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            if ($user['status'] == 0) {
                $message[] = 'Tài khoản của bạn đã bị khóa!';
            } else {
                
                // KIỂM TRA VÀ PHÂN QUYỀN TRUY CẬP
                if ($user['role'] == 'admin' || $user['role'] == 'staff') {
                    
                    // GÁN SESSION MỚI
                    $_SESSION['admin_id'] = $user['user_id'];
                    $_SESSION['admin_name'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role']; 
                    
                    
                    // CHUYỂN HƯỚNG
                    if ($user['role'] == 'admin') {
                        header('Location: admin/admin_statistical.php'); 
                        exit();
                    } elseif ($user['role'] == 'staff') { 
                        header('Location: admin/admin_orders.php'); 
                        exit();
                    }

                } elseif ($user['role'] == 'user') {
                    
                    // KHÁCH HÀNG THÔNG THƯỜNG
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['username'];
                    
                    header('Location: index.php'); 
                    exit();
                    
                } else {
                    $message[] = 'Tài khoản của bạn không có quyền truy cập!';
                }
            }
        } else {
            $message[] = 'Email hoặc mật khẩu không chính xác!';
        }
    } else {
        $message[] = 'Email hoặc mật khẩu không chính xác!';
    }
}
?>

<!DOCTYPE html>
<html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="./admin/admin_style.css">
</head>

<body class="background">
    <?php
        if (!empty($message)) { // Sửa lại điều kiện kiểm tra
            foreach ($message as $msg) {
                echo '
                    <div class=" alert alert-info alert-dismissible fade show" role="alert">
                        <span style="font-size: 16px;">' . $msg . '</span>
                        <i style="font-size: 20px; cursor: pointer" class="fas fa-times" onclick="this.parentElement.remove();"></i>
                    </div>';
            }
        }
    ?>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow" style="width: 400px; border-radius: 15px;">
            <div class="card-header text-center bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                <h4>Đăng nhập</h4>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Nhập E-mail" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                    </div>
                    <input type="submit" name="submit" class="btn btn-primary w-100" value="Đăng nhập">
                </form>
                <p class="text-center mt-3">
                    Bạn chưa có tài khoản?
                    <a href="./register.php" class="text-primary text-decoration-none">Đăng ký ngay</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>