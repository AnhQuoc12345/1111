<?php
ob_start();
session_start();

// include header.php file
include ('header.php');
include './database/DBController.php';

$user_id = $_SESSION['user_id'] ?? 1;
$cate_id = isset($_GET['cate_id']) ? (int)$_GET['cate_id'] : 0;

$category_query = mysqli_query($conn, "SELECT * FROM `categories` WHERE id = $cate_id") or die('Query failed');
if (mysqli_num_rows($category_query) == 0) {
    die('Danh mục không tồn tại!');
}
$category = mysqli_fetch_assoc($category_query);

// Lấy sản phẩm theo danh mục
$product_query = mysqli_query($conn, "SELECT * FROM `products` WHERE item_category = $cate_id") or die('Query failed');
$products = mysqli_fetch_all($product_query, MYSQLI_ASSOC);


// request method post
if($_SERVER['REQUEST_METHOD'] == "POST"){
    if (isset($_POST['category_submit'])){
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
    <title>Danh sách sản phẩm</title>
    <!-- Bootstrap CSS -->
</head>
<body>
    <div class="container mt-5">
        <h3 class="font-size-20 text-center mb-4">Danh sách sản phẩm: <?php echo $category['name']; ?></h3>
        <div class="d-flex flex-wrap mb-4" style="gap: 20px;">
            <?php foreach ($products as $item) { ?>
            <div class="grid-item border <?php echo $item['item_brand'] ?? "Brand" ; ?>">
                <div class="item py-2" style="width: 200px;">
                    <div class="product font-rale">
                        <a href="<?php printf('%s?item_id=%s', 'product.php',  $item['item_id']); ?>"><img src="./assets/products/<?php echo $item['item_image'] ?? "./assets/products/13.png"; ?>" alt="product1" class="img-fluid"></a>
                        <div class="text-center">
                            <h6 style="height: 39px;"><?php echo $item['item_name'] ?? "Unknown"; ?></h6>
                            <!-- <div class="rating text-warning font-size-12">
                                <span><i class="fas fa-star"></i></span>
                                <span><i class="fas fa-star"></i></span>
                                <span><i class="fas fa-star"></i></span>
                                <span><i class="fas fa-star"></i></span>
                                <span><i class="far fa-star"></i></span>
                            </div> -->
                            <div class="price py-2">
                                <?php echo number_format($item['item_price'], 0, ',', '.'); ?> đ
                            </div>
                            <form method="post">
                            <input type="hidden" name="item_id" value="<?php echo $item['item_id'] ?? '1'; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <?php
                            if (in_array($item['item_id'], $Cart->getCartId($product->getData('cart')) ?? [])){
                                echo '<button type="submit" disabled class="btn btn-success font-size-12">Đã có trong giỏ</button>';
                            }else{
                                echo '<button type="submit" name="category_submit" class="btn btn-warning font-size-12">Thêm vào giỏ</button>';
                            }
                            ?>
                        </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>

<?php
// include footer.php file
include ('footer.php');
?>


