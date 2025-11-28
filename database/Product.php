<?php

// Use to fetch product data
class Product
{
    public $db = null;

    public function __construct(DBController $db)
    {
        if (!isset($db->con)) return null;
        $this->db = $db;
    }

    // fetch product data using getData Method
    public function getData($table = 'products', $user_id = null){
        $result = $this->db->con->query("SELECT * FROM {$table} ");

        $resultArray = array();

        // fetch product data one by one
        while ($item = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $resultArray[] = $item;
        }

        return $resultArray;
    }

     // fetch product data using getData Method
     public function getCartData($user_id = null){
        // Nếu là guest (user_id < 0), lấy từ session
        if ($user_id != null && $user_id < 0) {
            if (isset($_SESSION['guest_cart'])) {
                // Chuyển đổi format từ session sang format giống database
                $resultArray = array();
                foreach ($_SESSION['guest_cart'] as $item) {
                    $resultArray[] = array(
                        'user_id' => $user_id,
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity']
                    );
                }
                return $resultArray;
            }
            return array();
        }
        
        // Nếu đã đăng nhập, lấy từ database
        if ($user_id != null) {
            $result = $this->db->con->query("SELECT * FROM cart WHERE user_id = {$user_id}");

            $resultArray = array();

            // fetch product data one by one
            while ($item = mysqli_fetch_array($result, MYSQLI_ASSOC)){
                $resultArray[] = $item;
            }

            return $resultArray;
        }
        
        return array();
    }

    // get product using item id
    public function getProduct($item_id = null, $table= 'products'){
        if (isset($item_id)){
            $result = $this->db->con->query("SELECT * FROM {$table} WHERE item_id={$item_id}");

            $resultArray = array();

            // fetch product data one by one
            while ($item = mysqli_fetch_array($result, MYSQLI_ASSOC)){
                $resultArray[] = $item;
            }

            return $resultArray;
        }
    }
    // TẠI FILE database/Product.php

public function checkProductExistence($item_name = null)
{
    // Kiểm tra kết nối
    if (!isset($this->db->con)) {
        // Nếu không có kết nối, coi như không tìm thấy
        return false; 
    }
    
    // Nếu không có tên sản phẩm
    if (empty($item_name)) {
        return false;
    }
    
    // Bảo vệ khỏi SQL Injection
    $safe_name = $this->db->con->real_escape_string($item_name); 
    
    // Truy vấn SQL: Tìm kiếm sản phẩm có tên tương tự
    $sql = "SELECT item_id FROM products WHERE item_name LIKE '%{$safe_name}%' LIMIT 1";
    $result = $this->db->con->query($sql);
    
    if ($result && $result->num_rows > 0) {
        // Tìm thấy sản phẩm
        return true; 
    } else {
        // Không tìm thấy
        return false; 
    }
}

}
?>