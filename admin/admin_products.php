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

// Thêm sản phẩm mới
if (isset($_POST['add_product'])) {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $item_category = $_POST['item_category'];
    $select_category = mysqli_query($conn, "SELECT name FROM `categories` WHERE id = '$item_category'") or die('Query failed');
    $category = mysqli_fetch_assoc($select_category);
    $item_brand = $category['name'];
    $item_desc = mysqli_real_escape_string($conn, $_POST['item_desc']);
    $item_quantity = mysqli_real_escape_string($conn, $_POST['item_quantity']);
    $item_price = mysqli_real_escape_string($conn, $_POST['item_price']);

    // Upload hình ảnh sản phẩm
    $item_image_name = $_FILES['item_image']['name'];
    $item_image_tmp_name = $_FILES['item_image']['tmp_name'];
    $item_image_folder = '../assets/products/' . $item_image_name;

    if (move_uploaded_file($item_image_tmp_name, $item_image_folder)) {
        $insert_product_query = mysqli_query($conn, "INSERT INTO `products` (item_brand, item_category, item_name, item_desc, item_quantity, item_price, item_image) 
        VALUES ('$item_brand', '$item_category', '$item_name', '$item_desc', '$item_quantity', '$item_price', '$item_image_name')") or die('Query failed');

        if ($insert_product_query) {
            $message[] = 'Thêm sản phẩm thành công!';
        } else {
            $message[] = 'Thêm sản phẩm thất bại!';
        }
    } else {
        $message[] = 'Lỗi khi tải ảnh!';
    }
}

// Xóa sản phẩm
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_image_query = mysqli_query($conn, "SELECT item_image FROM `products` WHERE item_id = '$delete_id'") or die('Query failed');
    $fetch_image = mysqli_fetch_assoc($delete_image_query);
    unlink('../assets/products/' . $fetch_image['item_image']);
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0") or die('Query failed to disable foreign key checks');

    $delete_query = mysqli_query($conn, "DELETE FROM `products` WHERE item_id = '$delete_id'") or die('Query failed');
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1") or die('Query failed to enable foreign key checks');

    if ($delete_query) {
        $message[] = 'Xóa sản phẩm thành công!';
    } else {
        $message[] = 'Xóa sản phẩm thất bại!';
    }
}

// Cập nhật sản phẩm
if (isset($_POST['update_product'])) {
    $update_id = $_POST['update_id'];
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $item_category = $_POST['item_category'];
    $select_category = mysqli_query($conn, "SELECT name FROM `categories` WHERE id = '$item_category'") or die('Query failed');
    $category = mysqli_fetch_assoc($select_category);
    $item_brand = $category['name'];
    $item_desc = mysqli_real_escape_string($conn, $_POST['item_desc']);
    $item_quantity = mysqli_real_escape_string($conn, $_POST['item_quantity']);
    $item_price = mysqli_real_escape_string($conn, $_POST['item_price']);

    $update_query = "UPDATE `products` SET item_name = '$item_name', item_category = '$item_category', item_brand = '$item_brand',
                    item_desc = '$item_desc', item_quantity = '$item_quantity', item_price = '$item_price'";

    if (!empty($_FILES['item_image']['name'])) {
        $item_image_name = $_FILES['item_image']['name'];
        $item_image_tmp_name = $_FILES['item_image']['tmp_name'];
        $item_image_folder = '../assets/products/' . $item_image_name;

        move_uploaded_file($item_image_tmp_name, $item_image_folder);
        $update_query .= ", item_image = '$item_image_name'";
    }
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0") or die('Query failed to disable foreign key checks');
    $update_query .= " WHERE item_id = '$update_id'";
    $update_result = mysqli_query($conn, $update_query) or die('Query failed');
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1") or die('Query failed to enable foreign key checks');


    if ($update_result) {
        $message[] = 'Cập nhật sản phẩm thành công!';
    } else {
        $message[] = 'Cập nhật sản phẩm thất bại!';
    }
}


// Phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit; 

// Dòng 102 (Quan trọng): Chúng ta cần biến $total_products này
$total_products_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `products`") or die('Query failed');
$total_products = mysqli_fetch_assoc($total_products_query)['total'];

// Dòng 104: Đảm bảo là "DESC"
$select_products = mysqli_query($conn, "SELECT p.*, c.name AS category_name FROM `products` p 
LEFT JOIN `categories` c ON p.item_category = c.id 
ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset") or die('Query failed'); // <-- PHẢI LÀ DESC

$total_pages = ceil($total_products / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/admin_style.css">
</head>

<body>
    <div class="d-flex">
        <?php include 'admin_navbar.php'; ?>
        <div class="manage-container">
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
            <div class="bg-primary text-white text-center py-2 mb-4 shadow">
                <h1 class="mb-0">Quản Lý Sản Phẩm</h1>
            </div>
            <section class="add-products mb-4">
                <!-- Thêm button hiển thị form -->
                <button type="button" id="showAddForm" style="width: fit-content;" class="btn btn-primary mb-3">Thêm sản phẩm mới</button>

                <!-- Form thêm sản phẩm (mặc định ẩn) -->
                <form action="" method="post" enctype="multipart/form-data" id="addProductForm" style="display: none;">
                    <h3>Thêm sản phẩm mới</h3>
                    <div class="mb-3">
                        <input type="text" name="item_name" class="form-control" placeholder="Tên sản phẩm" required>
                    </div>
                    <div class="mb-3">
                        <select name="item_category" class="form-control" required>
                            <?php
                            $categories = mysqli_query($conn, "SELECT * FROM `categories`") or die('Query failed');
                            while ($category = mysqli_fetch_assoc($categories)) {
                                echo "<option value='{$category['id']}'>{$category['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea name="item_desc" class="form-control" placeholder="Mô tả sản phẩm" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <input type="number" name="item_quantity" class="form-control" placeholder="Số lượng" required>
                    </div>
                    <div class="mb-3">
                        <input type="number" name="item_price" class="form-control" placeholder="Giá sản phẩm" required>
                    </div>
                    <div class="mb-3">
                        <input type="file" name="item_image" class="form-control" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <button type="submit" style="width: fit-content;" name="add_product" class="btn btn-primary">Thêm sản phẩm</button>
                        <button type="button" style="width: fit-content;" class="btn btn-secondary" id="cancelAdd">Hủy</button>
                    </div>
                </form>
            </section>

            <section class="show-products">
                <div class="container">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Số lượng</th>
                                <th>Giá</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($select_products) > 0) {
                                
                                // STT = Tổng số sản phẩm - (số sản phẩm đã bỏ qua)
                                $stt = $total_products - $offset; 
                                
                                while ($product = mysqli_fetch_assoc($select_products)) {
                            ?>
                                    <tr>
                                        <td><?php echo $stt; ?></td>
                                        <td><img src="../assets/products/<?php echo $product['item_image']; ?>" alt="" width="50"></td>
                                        <td><?php echo $product['item_name']; ?></td>
                                        <td><?php echo $product['item_brand']; ?></td>
                                        <td><?php echo $product['item_quantity']; ?></td>
                                        <td><?php echo number_format($product['item_price'], 0, ',', '.'); ?> đ</td>
                                        
                                        <td>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $product['item_id']; ?>">Sửa</button>
                                            
                                            <a href="admin_products.php?delete=<?php echo $product['item_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">Xóa</a>

                                            <div class="modal fade" id="editModal<?php echo $product['item_id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel">Sửa sản phẩm</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="post" enctype="multipart/form-data">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="update_id" value="<?php echo $product['item_id']; ?>">
                                                                <input type="text" name="item_name" class="form-control mb-3" value="<?php echo $product['item_name']; ?>" required>
                                                                <select name="item_category" class="form-control mb-3" required>
                                                                    <?php
                                                                    $categories = mysqli_query($conn, "SELECT * FROM `categories`") or die('Query failed');
                                                                    while ($category = mysqli_fetch_assoc($categories)) {
                                                                        echo "<option value='{$category['id']}'" . ($category['id'] == $product['item_category'] ? ' selected' : '') . ">{$category['name']}</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                                <textarea name="item_desc" class="form-control mb-3" rows="5" required><?php echo $product['item_desc']; ?></textarea>
                                                                <input type="number" name="item_quantity" class="form-control mb-3" value="<?php echo $product['item_quantity']; ?>" required>
                                                                <input type="number" name="item_price" class="form-control mb-3" value="<?php echo $product['item_price']; ?>" required>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                                <button type="submit" name="update_product" class="btn btn-primary">Cập nhật</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            </td>
                                        </tr>
                            <?php
                                    $stt--; // Giảm STT đi 1
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center">Chưa có sản phẩm nào.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="admin_products.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="admin_products.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php } ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="admin_products.php?page=<?php echo $page + 1; ?>" aria-label="Next">
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const showAddForm = document.getElementById('showAddForm');
        const addProductForm = document.getElementById('addProductForm');
        const cancelAdd = document.getElementById('cancelAdd');

        // Xử lý hiện form khi click nút thêm mới
        showAddForm.addEventListener('click', function() {
            addProductForm.style.display = 'block';
            showAddForm.style.display = 'none';
        });

        // Xử lý ẩn form khi click nút hủy
        cancelAdd.addEventListener('click', function() {
            addProductForm.style.display = 'none';
            showAddForm.style.display = 'block';
            // Reset form
            addProductForm.reset();
        });
    });
    </script>
</body>

</html>