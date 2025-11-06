<?php
ob_start();

session_start();
$user_id = @$_SESSION['user_id'];
// include header.php file
include './database/DBController.php';

include('header.php');
?>
<?php

/*  include banner area  */
include('Template/_banner-area.php');
/*  include banner area  */

/*  include top sale section */
include('Template/_top-sale.php');
/*  include top sale section */
// Hiển thị khu vực sản phẩm nổi bật
include('Template/_hot_products.php');

/*  include special price section  */
include('Template/_special-price.php');
/*  include special price section  */

/*  include banner ads  */
include('Template/_banner-ads.php');
/*  include banner ads  */

/*  include new phones section  */
include('Template/_new-phones.php');
/*  include new phones section  */



?>
<style>
    #chat-icon:hover {
        background: #0056b3;
    }

    #chat-form label {
        font-weight: bold;
    }
</style>



<script src="./index.js"></script>

<?php
// include footer.php file
include('footer.php');
?>