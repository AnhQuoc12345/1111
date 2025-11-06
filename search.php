<?php
ob_start();
session_start();

// include header.php file
include ('header.php');
include './database/DBController.php';

// Kết nối cơ sở dữ liệu
include './database/DBController.php';

$user_id = $_SESSION['user_id'] ?? 1;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Tách từ khóa thành mảng để tìm kiếm theo từng từ
$keywords = explode(' ', $keyword);
$searchQuery = implode("%' OR `item_name` LIKE '%", $keywords);

// Truy vấn sản phẩm
$product_query = mysqli_query($conn, "SELECT * FROM `products` WHERE `item_name` LIKE '%$searchQuery%'") or die('Query failed');
$products = mysqli_fetch_all($product_query, MYSQLI_ASSOC);

// request method post
if($_SERVER['REQUEST_METHOD'] == "POST"){
    if (isset($_POST['search_submit'])){
        // call method addToCart
        $Cart->addToCart($_POST['user_id'], $_POST['item_id']);
        header('Location: ' . $_SERVER['REQUEST_URI']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả tìm kiếm</title>
</head>
<body>
    <div class="container mt-5">
        <h3 class="font-size-20 text-center mb-4">Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($keyword); ?>"</h3>
        <div class="d-flex flex-wrap mb-4" style="gap: 20px;">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $item): ?>
                <div class="grid-item border <?php echo $item['item_brand'] ?? "Brand" ; ?>">
                    <div class="item py-2" style="width: 200px;">
                        <div class="product font-rale">
                            <a href="<?php printf('%s?item_id=%s', 'product.php', $item['item_id']); ?>">
                                <img src="./assets/products/<?php echo $item['item_image'] ?? "./assets/products/13.png"; ?>" alt="product1" class="img-fluid">
                            </a>
                            <div class="text-center">
                                <h6><?php echo $item['item_name']; ?></h6>
                                <div class="price py-2">
                                    <?php echo number_format($item['item_price'], 0, ',', '.'); ?> đ
                                </div>
                                <form method="post">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                    <?php
                                        if (in_array($item['item_id'], $Cart->getCartId($product->getData('cart')) ?? [])){
                                            echo '<button type="submit" disabled class="btn btn-success font-size-12">Đã có trong giỏ</button>';
                                        }else{
                                            echo '<button type="submit" name="search_submit" class="btn btn-warning font-size-12">Thêm vào giỏ</button>';
                                        }
                                    ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Không tìm thấy sản phẩm nào phù hợp với từ khóa "<?php echo htmlspecialchars($keyword); ?>"</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


<?php
// include footer.php file
include ('footer.php');
?>