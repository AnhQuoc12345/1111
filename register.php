<?php
include 'database/DBController.php'; // $conn của bạn được lấy từ đây

if (isset($_POST['submit'])) {

    // Lấy dữ liệu an toàn (không cần escape vì dùng Prepared Statements)
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ==========================================================
    // SỬA ĐỔI QUAN TRỌNG: Dùng Prepared Statements chống SQL Injection
    // ==========================================================
    
    // 1. Kiểm tra email đã tồn tại chưa (An toàn)
    $stmt = mysqli_prepare($conn, "SELECT * FROM `users` WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $select_user = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($select_user) > 0) {
        $message[] = 'Email đã tồn tại!';
    } else {
        // 2. So sánh mật khẩu gốc (chưa mã hóa)
        if ($password != $confirm_password) {
            $message[] = 'Mật khẩu không khớp!';
        } else {
            // ==========================================================
            // SỬA ĐỔI QUAN TRỌNG: Nâng cấp MD5 lên PASSWORD_HASH()
            // ==========================================================
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 3. Thêm tài khoản vào bảng `users` (An toàn)
            $stmt_insert = mysqli_prepare($conn, "INSERT INTO `users` (username, email, password) VALUES(?, ?, ?)");
            // "sss" nghĩa là 3 biến đều là kiểu string (chuỗi)
            mysqli_stmt_bind_param($stmt_insert, "sss", $name, $email, $hashed_password);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $message[] = 'Đăng ký thành công!';
                // Dùng exit; sau khi header để đảm bảo chuyển trang
                header('location:login.php');
                exit;
            } else {
                $message[] = 'Đăng ký thất bại! Vui lòng thử lại.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng ký</title>



    <!-- Bootstrap CSS -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="./admin/admin_style.css">

</head>



<body class="background">

    <?php

    //nhúng vào các trang bán hàng

    if (isset($message)) { // hiển thị thông báo sau khi thao tác với biến message được gán giá trị

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

                <h4>Đăng ký</h4>

            </div>

            <div class="card-body">

                <!-- Hiển thị thông báo -->

                <?php

                if (isset($message)) {

                    foreach ($message as $msg) {

                        echo '

                        <div class="alert alert-info alert-dismissible fade show" role="alert">

                            <span>' . $msg . '</span>

                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

                        </div>';

                    }

                }

                ?>



                <!-- Form đăng ký -->

                <form action="" method="post">

                    <div class="mb-3">

                        <label for="name" class="form-label">Họ tên</label>

                        <input type="text" id="name" name="name" class="form-control" placeholder="Nhập họ tên" required>

                    </div>

                    <div class="mb-3">

                        <label for="email" class="form-label">E-mail</label>

                        <input type="email" id="email" name="email" class="form-control" placeholder="Nhập email" required>

                    </div>

                    <div class="mb-3">

                        <label for="password" class="form-label">Mật khẩu</label>

                        <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>

                    </div>

                    <div class="mb-3">

                        <label for="confirm_password" class="form-label">Nhập lại mật khẩu</label>

                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required>

                    </div>

                    <button type="submit" name="submit" class="btn btn-primary w-100">Đăng ký ngay</button>

                </form>

                <p class="text-center mt-3">

                    Bạn đã có tài khoản?

                    <a href="login.php" class="text-primary text-decoration-none">Đăng nhập</a>

                </p>

            </div>

        </div>

    </div>



    <!-- Bootstrap JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>



</html>