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
// (Vì file login đã gán $conn rồi, các file khác cũng nên làm vậy)
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

// Lấy trang hiện tại
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$message = []; // Mảng chứa thông báo

// ==========================================================
// BẮT ĐẦU: LOGIC THÊM TÀI KHOẢN (MỚI)
// ==========================================================
if (isset($_POST['add_staff'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $role = $_POST['role']; // 'staff' hoặc 'admin'

    // Băm mật khẩu (BẮT BUỘC)
    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    // 1. Kiểm tra tài khoản đã tồn tại chưa (Bảo mật với Prepared Statement)
    $stmt_check = mysqli_prepare($conn, "SELECT user_id FROM `users` WHERE username = ? OR email = ?");
    mysqli_stmt_bind_param($stmt_check, "ss", $username, $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $message[] = 'Tên đăng nhập hoặc email đã tồn tại!';
    } else {
        // 2. Thêm tài khoản mới (Bảo mật với Prepared Statement)
        // Mặc định status = 1 (hoạt động)
        $stmt_insert = mysqli_prepare($conn, "INSERT INTO `users` (username, email, password, role, status) VALUES (?, ?, ?, ?, '1')");
        mysqli_stmt_bind_param($stmt_insert, "ssss", $username, $email, $hashed_pass, $role);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            $message[] = 'Thêm tài khoản ' . htmlspecialchars($role) . ' thành công!';
        } else {
            $message[] = 'Thêm tài khoản thất bại!';
        }
        mysqli_stmt_close($stmt_insert);
    }
    mysqli_stmt_close($stmt_check);
}

// ==========================================================
// BẮT ĐẦU: LOGIC XÓA (NÂNG CẤP BẢO MẬT)
// ==========================================================
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    if ($delete_id == $admin_id) {
        $message[] = 'Bạn không thể tự xóa chính mình!';
    } else {
        // Nâng cấp: Dùng Prepared Statement
        $stmt = mysqli_prepare($conn, "DELETE FROM `users` WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message[] = 'Xóa tài khoản thành công!';
        } else {
            $message[] = 'Xóa tài khoản thất bại!';
        }
        mysqli_stmt_close($stmt);
    }
    
    // Redirect để xóa tham số 'delete' khỏi URL, tránh lỗi
    header("location:admin_accounts.php?page=$page");
    exit();
}

// ==========================================================
// BẮT ĐẦU: LOGIC KHÓA (NÂNG CẤP BẢO MẬT)
// ==========================================================
if (isset($_GET['block'])) {
    $block_id = $_GET['block'];

    if ($block_id == $admin_id) {
        $message[] = 'Bạn không thể tự khóa chính mình!';
    } else {
        // Nâng cấp: Dùng Prepared Statement
        $stmt = mysqli_prepare($conn, "UPDATE `users` SET status = '0' WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $block_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message[] = 'Khóa tài khoản thành công!';
        } else {
            $message[] = 'Khóa tài khoản thất bại!';
        }
        mysqli_stmt_close($stmt);
    }
    // Tải lại trang để cập nhật nút
    header("location:admin_accounts.php?page=$page");
    exit();
}

// ==========================================================
// BẮT ĐẦU: LOGIC MỞ KHÓA (NÂNG CẤP BẢO MẬT)
// ==========================================================
if (isset($_GET['un_block'])) {
    $un_block_id = $_GET['un_block'];

    // Nâng cấp: Dùng Prepared Statement
    $stmt = mysqli_prepare($conn, "UPDATE `users` SET status = '1' WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $un_block_id);

    if (mysqli_stmt_execute($stmt)) {
        $message[] = 'Mở khóa tài khoản thành công!';
    } else {
        $message[] = 'Mở khóa tài khoản thất bại!';
    }
    mysqli_stmt_close($stmt);

    // Tải lại trang để cập nhật nút
    header("location:admin_accounts.php?page=$page");
    exit();
}


// ==========================================================
// BẮT ĐẦU: LOGIC PHÂN TRANG (NÂNG CẤP BẢO MẬT)
// ==========================================================
$limit = 10; // Giới hạn 10 tài khoản mỗi trang
$offset = ($page - 1) * $limit; 

// Nâng cấp: Dùng Prepared Statement
$stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM `users` WHERE role = 'user'");
mysqli_stmt_execute($stmt_count);
$total_result = mysqli_stmt_get_result($stmt_count);
$total_users = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_users / $limit);
mysqli_stmt_close($stmt_count);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_style.css">
</head>

<body>
    <div class="d-flex">
        <?php include 'admin_navbar.php'; ?>
        <div class="manage-container">
            <?php
            // Hiển thị thông báo
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
                <h1 class="mb-0">Quản lý Tài khoản</h1>
            </div>

            <section class="add-staff-form mb-4">
                <div class="container">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h3 class="mb-0">Thêm tài khoản Quản trị / Nhân viên</h3>
                        </div>
                        <div class="card-body">
                            <form action="admin_accounts.php?page=<?php echo $page; ?>" method="POST" autocomplete="off">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="username" class="form-label">Tên đăng nhập</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="password" class="form-label">Mật khẩu</label>
                                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="role" class="form-label">Vai trò</label>
                                        <select id="role" name="role" class="form-select">
                                            <option value="staff" selected>Nhân viên (Staff)</option>
                                            <option value="admin">Quản trị (Admin)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8 d-flex align-items-end">
                                        <button type="submit" name="add_staff" class="btn btn-primary">Thêm tài khoản</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
            
            <hr class="my-4">

            <section class="show-users">
                <div class="container">
                    <h1 class="text-center">Danh sách tài khoản người dùng</h1>
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php
                            // Nâng cấp: Dùng Prepared Statement
                            $stmt_users = mysqli_prepare($conn, "SELECT * FROM `users` where role ='user' ORDER BY user_id DESC LIMIT ? OFFSET ?");
                            mysqli_stmt_bind_param($stmt_users, "ii", $limit, $offset);
                            mysqli_stmt_execute($stmt_users);
                            $select_users = mysqli_stmt_get_result($stmt_users);

                            if (mysqli_num_rows($select_users) > 0) {
                                $stt = $total_users - $offset;
                                
                                while ($user = mysqli_fetch_assoc($select_users)) {
                            ?>
                                    <tr>
                                        <td><?php echo $stt; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                                        <td>
                                            <?php if ($user['status'] == 1) { ?>
                                                <a href="admin_accounts.php?page=<?php echo $page; ?>&block=<?php echo $user['user_id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản này?');">Khóa</a>
                                            <?php } else { ?>
                                                <a href="admin_accounts.php?page=<?php echo $page; ?>&un_block=<?php echo $user['user_id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Bạn có chắc chắn muốn mở khóa tài khoản này?');">Mở Khóa</a>
                                            <?php } ?>
                                            <a href="admin_accounts.php?page=<?php echo $page; ?>&delete=<?php echo $user['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này?');">Xóa</a>
                                        </td>
                                    </tr>
                            <?php
                                    $stt--;
                                }
                                mysqli_stmt_close($stmt_users); // Đóng statement
                            } else {
                                echo '<tr><td colspan="5" class="text-center">Chưa có tài khoản nào.</td></tr>';
                            }
                            ?>
                        </tbody>
                        </table>

                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="admin_accounts.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="admin_accounts.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php } ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="admin_accounts.php?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    </div>
            </section>

            <hr class="my-5">

            <section class="show-admins mt-5">
                <div class="container">
                    <h1 class="text-center">Danh sách tài khoản Quản trị & Nhân viên</h1>
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Nâng cấp: Dùng Prepared Statement
                            $stmt_staff = mysqli_prepare($conn, "SELECT * FROM `users` WHERE role = 'admin' OR role = 'staff' ORDER BY role DESC, user_id ASC");
                            mysqli_stmt_execute($stmt_staff);
                            $select_staff = mysqli_stmt_get_result($stmt_staff);
                            
                            if (mysqli_num_rows($select_staff) > 0) {
                                while ($staff = mysqli_fetch_assoc($select_staff)) {
                            ?>
                                    <tr>
                                        <td><?php echo $staff['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                        <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                        <td>
                                            <?php 
                                            // Làm nổi bật vai trò
                                            if($staff['role'] == 'admin'){
                                                echo '<span class="text-danger fw-bold">Admin</span>';
                                            } else {
                                                echo '<span class="text-primary">Nhân viên</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Hiển thị trạng thái
                                            if($staff['status'] == 1){
                                                echo '<span class="badge bg-success">Đang hoạt động</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Đã khóa</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            // ---- BẢO VỆ: Ngăn admin tự thao tác chính mình ----
                                            if ($staff['user_id'] == $admin_id) {
                                                echo '<span class="text-muted fst-italic">Tài khoản hiện tại</span>';
                                            } else {
                                            ?>
                                                <?php if ($staff['status'] == 1) { ?>
                                                    <a href="admin_accounts.php?page=<?php echo $page; ?>&block=<?php echo $staff['user_id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Bạn có chắc muốn KHÓA tài khoản này?');">Khóa</a>
                                                <?php } else { ?>
                                                    <a href="admin_accounts.php?page=<?php echo $page; ?>&un_block=<?php echo $staff['user_id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Bạn có chắc muốn MỞ KHÓA tài khoản này?');">Mở Khóa</a>
                                                <?php } ?>
                                                
                                                <a href="admin_accounts.php?page=<?php echo $page; ?>&delete=<?php echo $staff['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('CẢNH BÁO: Bạn có chắc muốn XÓA VĨNH VIỄN tài khoản này?');">Xóa</a>
                                            <?php
                                            } // Đóng thẻ if($staff['user_id'] == $admin_id)
                                            ?>
                                        </td>
                                    </tr>
                            <?php
                                } // Đóng vòng lặp while
                                mysqli_stmt_close($stmt_staff); // Đóng statement
                            } else {
                                echo '<tr><td colspan="6" class="text-center">Không có tài khoản quản trị hoặc nhân viên nào.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>