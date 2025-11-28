<?php

// php cart class
class Cart
{
    public $db = null;

    public function __construct(DBController $db)
    {
        if (!isset($db->con)) return null;
        $this->db = $db;
    }

    // insert into cart table
    public  function insertIntoCart($params = null, $table = "cart"){
        if ($this->db->con != null){
            if ($params != null){
                // get table columns
                $columns = implode(',', array_keys($params));

                $values = implode(',' , array_values($params));

                // create sql query
                $query_string = sprintf("INSERT INTO %s(%s) VALUES(%s)", $table, $columns, $values);

                // execute query
                $result = $this->db->con->query($query_string);
                return $result;
            }
        }
    }

    public function deleteAllCart($user_id = null){
        // Nếu là guest, xóa từ session
        if ($user_id != null && $user_id < 0) {
            $_SESSION['guest_cart'] = array();
            return true;
        }
        
        // Nếu đã đăng nhập, xóa từ database
        if ($this->db->con != null && $user_id != null){
            $result = $this->db->con->query("DELETE FROM cart WHERE user_id={$user_id}");
            return $result;
        }
    }

    // to get user_id and item_id and insert into cart table
    public function addToCart($userid, $itemid){
        if (isset($userid) && isset($itemid)){
            // Kiểm tra nếu là guest (user_id < 0), lưu vào session
            if ($userid < 0) {
                // Khởi tạo session cart nếu chưa có
                if (!isset($_SESSION['guest_cart'])) {
                    $_SESSION['guest_cart'] = array();
                }
                
                // Kiểm tra xem sản phẩm đã có trong giỏ session chưa
                $found = false;
                foreach ($_SESSION['guest_cart'] as &$item) {
                    if ($item['item_id'] == $itemid) {
                        $item['quantity'] += 1;
                        $found = true;
                        break;
                    }
                }
                
                // Nếu chưa có, thêm mới
                if (!$found) {
                    $_SESSION['guest_cart'][] = array(
                        'item_id' => $itemid,
                        'quantity' => 1
                    );
                }
                
                // Reload Page
                header("Location: " . $_SERVER['PHP_SELF']);
                return;
            }
            
            // Nếu đã đăng nhập, lưu vào database
            // Kiểm tra xem sản phẩm đã có trong giỏ hay chưa
            $checkCart = mysqli_query($this->db->con, "SELECT * FROM cart WHERE user_id = {$userid} AND item_id = {$itemid}");
            
            // Nếu sản phẩm đã có trong giỏ, tăng số lượng lên 1
            if (mysqli_num_rows($checkCart) > 0) {
                // Cập nhật số lượng sản phẩm trong giỏ
                $updateQuery = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = {$userid} AND item_id = {$itemid}";
                $result = mysqli_query($this->db->con, $updateQuery);
                if ($result) {
                    // Reload Page
                    header("Location: " . $_SERVER['PHP_SELF']);
                }
            } else {
                // Nếu sản phẩm chưa có trong giỏ, thêm mới vào giỏ
                $params = array(
                    "user_id" => $userid,
                    "item_id" => $itemid,
                    "quantity" => 1 // Số lượng mặc định khi thêm mới
                );
                $result = $this->insertIntoCart($params);
                if ($result) {
                    // Reload Page
                    header("Location: " . $_SERVER['PHP_SELF']);
                }
            }
        }
    }
    
    // delete cart item using cart item id
    public function deleteCart($item_id = null, $user_id = null, $table = 'cart'){
        if($item_id != null && $user_id != null){
            // Nếu là guest, xóa từ session
            if ($user_id < 0) {
                if (isset($_SESSION['guest_cart'])) {
                    $_SESSION['guest_cart'] = array_filter($_SESSION['guest_cart'], function($item) use ($item_id) {
                        return $item['item_id'] != $item_id;
                    });
                    // Reset array keys
                    $_SESSION['guest_cart'] = array_values($_SESSION['guest_cart']);
                }
                header("Location:" . $_SERVER['PHP_SELF']);
                return true;
            }
            
            // Nếu đã đăng nhập, xóa từ database
            $result = $this->db->con->query("DELETE FROM {$table} WHERE item_id={$item_id} AND user_id={$user_id}");
            if($result){
                header("Location:" . $_SERVER['PHP_SELF']);
            }
            return $result;
        }
    }

    public function getSum($cartItems){
        $sum = 0;
    
        if(isset($cartItems)){
            foreach ($cartItems as $cartItem){
                // Lấy item_id và quantity từ cart
                $itemId = $cartItem['item_id'];
                $quantity = intval($cartItem['quantity']);
    
                // Lấy giá sản phẩm từ bảng products
                $result = mysqli_query($this->db->con, "SELECT item_price FROM products WHERE item_id = {$itemId}");
                $item = mysqli_fetch_assoc($result);
    
                // Kiểm tra xem có giá trị item_price không
                if ($item) {
                    $itemPrice = floatval($item['item_price']); // Giá của sản phẩm
                    $sum += $itemPrice * $quantity; // Tính tổng tiền của sản phẩm
                }
            }
        }
    
        // Trả về tổng tiền đã định dạng
        return number_format($sum, 0, ',', '.');
    }
    

    // get item_it of shopping cart list
    public function getCartId($cartArray = null, $key = "item_id"){
        if ($cartArray != null){
            $cart_id = array_map(function ($value) use($key){
                return $value[$key];
            }, $cartArray);
            return $cart_id;
        }
    }

    // Save for later
    public function saveForLater($item_id = null, $saveTable = "wishlist", $fromTable = "cart"){
        if ($item_id != null){
            $query = "INSERT INTO {$saveTable} SELECT * FROM {$fromTable} WHERE item_id={$item_id};";
            $query .= "DELETE FROM {$fromTable} WHERE item_id={$item_id};";

            // execute multiple query
            $result = $this->db->con->multi_query($query);

            if($result){
                header("Location :" . $_SERVER['PHP_SELF']);
            }
            return $result;
        }
    }

    public function updateQuantity($item_id, $quantity, $user_id = null) {
        // Update quantity for the given item ID and user ID
        if ($user_id != null) {
            // Nếu là guest, cập nhật trong session
            if ($user_id < 0) {
                if (isset($_SESSION['guest_cart'])) {
                    foreach ($_SESSION['guest_cart'] as &$item) {
                        if ($item['item_id'] == $item_id) {
                            $item['quantity'] = $quantity;
                            break;
                        }
                    }
                }
                return true;
            }
            
            // Nếu đã đăng nhập, cập nhật trong database
            $query = "UPDATE cart SET quantity = ? WHERE item_id = ? AND user_id = ?";
            $stmt = $this->db->con->prepare($query);
            $stmt->bind_param("iii", $quantity, $item_id, $user_id);
            $stmt->execute();
        }
    }


}