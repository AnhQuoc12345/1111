<?php
session_start();
include './database/DBController.php'; // $conn của bạn được lấy từ đây

// Kiểm tra nếu người dùng chưa đăng nhập
$user_id = $_SESSION['user_id'] ?? null;
if (!isset($user_id)) {
    header('location: ./login.php');
    exit;
}

// ==========================================================
// SỬA ĐỔI QUAN TRỌNG: Dùng Prepared Statements để chống SQL Injection
// ==========================================================

// Lấy thông tin người dùng hiện tại (An toàn)
$stmt = mysqli_prepare($conn, "SELECT * FROM `users` WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Xử lý cập nhật thông tin
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']); // <-- MỚI THÊM
    $current_password_input = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // ==========================================================
    // SỬA ĐỔI QUAN TRỌNG: Nâng cấp MD5 lên password_verify()
    // ==========================================================

    // 1. Kiểm tra số điện thoại (Server-side validation)
    if (!preg_match('/^0\d{9,10}$/', $phone)) {
        $message = "<div class='alert alert-danger'>Số điện thoại không hợp lệ. Phải bắt đầu bằng 0 và có 10 hoặc 11 số.</div>";
    }
    // 2. Kiểm tra mật khẩu hiện tại (An toàn)
    else if (!password_verify($current_password_input, $user['password'])) {
        $message = "<div class='alert alert-danger'>Mật khẩu hiện tại không đúng!</div>";
    }
    // 3. Nếu mật khẩu đúng và SĐT hợp lệ, tiến hành cập nhật
    else {
        // Chuẩn bị câu lệnh SQL và tham số
        $sql = "UPDATE `users` SET username = ?, email = ?, phone = ? WHERE user_id = ?";
        $params_types = "sssi"; // s = string, i = integer
        $params_values = [$username, $email, $phone, $user_id];

        // 4. Kiểm tra nếu người dùng muốn đổi mật khẩu
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $message = "<div class='alert alert-danger'>Mật khẩu mới và xác nhận không khớp!</div>";
            } else {
                // ==========================================================
                // SỬA ĐỔI QUAN TRỌNG: Nâng cấp MD5 lên password_hash()
                // ==========================================================
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Thay đổi câu lệnh SQL để cập nhật mật khẩu
                $sql = "UPDATE `users` SET username = ?, email = ?, phone = ?, password = ? WHERE user_id = ?";
                $params_types = "ssssi";
                $params_values = [$username, $email, $phone, $hashed_password, $user_id];
            }
        }

        // 5. Thực thi truy vấn (chỉ khi không có lỗi)
        if (empty($message)) {
            $stmt_update = mysqli_prepare($conn, $sql);
            // Dùng ... (splat operator) để truyền mảng tham số
            mysqli_stmt_bind_param($stmt_update, $params_types, ...$params_values);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $message = "<div class='alert alert-success'>Cập nhật thông tin thành công!</div>";
                
                // Cập nhật lại thông tin $user để hiển thị trên form
                $stmt_refresh = mysqli_prepare($conn, "SELECT * FROM `users` WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt_refresh, "i", $user_id);
                mysqli_stmt_execute($stmt_refresh);
                $user = mysqli_stmt_get_result($stmt_refresh)->fetch_assoc();
            } else {
                $message = "<div class='alert alert-danger'>Lỗi cập nhật thông tin: " . mysqli_error($conn) . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include('header.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="text-center">Thông tin cá nhân</h2>
    <p class="text-center text-muted">Cập nhật thông tin tài khoản của bạn</p>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <?= $message ?>
            
            <form action="" method="post" class="border p-4 shadow-sm rounded">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên người dùng</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Số điện thoại</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                           required
                           pattern="^0\d{9,10}$" 
                           title="Phải là 10 hoặc 11 số, bắt đầu bằng 0."
                           minlength="10" 
                           maxlength="11">
                </div>
                <div class="mb-3">
                    <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">Mật khẩu mới (bỏ trống nếu không đổi)</label>
                    <input type="password" class="form-control" id="new_password" name="new_password">
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                </div>

                <button type="submit" class="btn btn-primary w-100">Cập nhật</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include('footer.php'); ?>