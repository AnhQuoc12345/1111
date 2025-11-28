<?php
// Đảm bảo session đã được khởi động
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tạo guest_id cho khách hàng chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    // Tạo guest_id dựa trên session_id (hash để tạo số nguyên)
    if (!isset($_SESSION['guest_id'])) {
        // Tạo guest_id từ hash của session_id, chuyển thành số nguyên âm
        $session_hash = crc32(session_id());
        $_SESSION['guest_id'] = -abs($session_hash); // Sử dụng số âm để tránh xung đột với user_id thật
    }
    $user_id = $_SESSION['guest_id'];
} else {
    $user_id = $_SESSION['user_id'];
    // Xóa guest_id nếu đã đăng nhập
    if (isset($_SESSION['guest_id'])) {
        unset($_SESSION['guest_id']);
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REAL TECH</title>

    <!-- Bootstrap CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <!-- Owl-carousel CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"
        integrity="sha256-UhQQ4fxEeABh4JrcmAJ1+16id/1dnlOEVCFOxDef9Lw=" crossorigin="anonymous" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css"
        integrity="sha256-kksNxjDRxd/5+jGurZUJd1sdR2v+ClrCl3svESBaJqw=" crossorigin="anonymous" />

    <!-- font awesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css"
        integrity="sha256-h20CPZ0QyXlBuAw7A+KluUYx/3pK+c7lYEpqLTlxjYQ=" crossorigin="anonymous" />

    <!-- Custom CSS file -->
    <link rel="stylesheet" href="style.css">

    <?php
    // require functions.php file
    require('functions.php');
    ?>

    <style>
        /* === BẮT ĐẦU CSS CHATBOT === */
.chat-bubble {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    border: none;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    z-index: 1000;
    transition: transform 0.2s ease;
}
.chat-bubble:hover {
    transform: scale(1.1);
}
.chat-window {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 350px;
    height: 450px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    display: none; /* Ẩn ban đầu */
    flex-direction: column;
    z-index: 1001;
    overflow: hidden;
}
.chat-header {
    background: #007bff;
    color: white;
    padding: 15px;
    font-weight: bold;
    font-size: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chat-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
}
.chat-body {
    flex-grow: 1;
    padding: 15px;
    overflow-y: auto;
    background: #f4f4f4;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.chat-message {
    padding: 10px 15px;
    border-radius: 18px;
    max-width: 80%;
    line-height: 1.4;
}
.chat-message.ai {
    background: #e9e9eb;
    color: #333;
    align-self: flex-start;
}
.chat-message.user {
    background: #007bff;
    color: white;
    align-self: flex-end;
}
.chat-footer {
    display: flex;
    padding: 10px;
    border-top: 1px solid #ddd;
}
#chat-input {
    flex-grow: 1;
    border: 1px solid #ccc;
    border-radius: 20px;
    padding: 10px 15px;
    margin-right: 10px;
}
#chat-send-btn {
    background: #007bff;
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 16px;
    cursor: pointer;
}
/* === KẾT THÚC CSS CHATBOT === */
        .nav-link {
            color: white !important;
        }

        .search-product {
            width: 300px;
            margin-right: 50px;
        }

        .user-dropdown {
            cursor: pointer;
        }

        #userDropdown {
            padding-bottom: 5px;
            ;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        #userDropdown p {
            margin: 0;
            font-size: 14px;
            color: #333;
        }

        #userDropdown button {
            margin-top: 10px;
        }
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .dropdown-item:focus, .dropdown-item:hover {
            background-color: #007bff;
            color: white;
        }
        .search-btn {
            position: absolute;
            right: 49px;
            border-radius: 50;
            height: -webkit-fill-available;
        }
    </style>

</head>

<body>

    <!-- start #header -->
    <header id="header">
        <?php

        global $message;

        if (isset($message) && is_array($message)) { // hiển thị thông báo sau khi thao tác với biến message được gán giá trị
            foreach ($message as $msg) {
                echo '
       <div class=" alert alert-info alert-dismissible fade show" role="alert">
          <span style="font-size: 16px;">' . $msg . '</span>
          <i style="font-size: 20px; cursor: pointer" class="fas fa-times" onclick="this.parentElement.remove();"></i>
       </div>';
            }
        }
        ?>
        <div class="strip d-flex justify-content-between px-4 py-1 bg-light">
            <p class="font-rale font-size-12 text-black-50 m-0"></p>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) { ?>
                <div class="user-dropdown" style="position: relative; display: inline-block;">
                    <i class="fas fa-user-circle" style="font-size: 30px; cursor: pointer;" id="userIcon"></i>
                    <!-- Dropdown menu -->
                    <div id="userDropdown"
                        style="display: none; position: absolute; top: 30px; right: 0; background: white; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); z-index: 1000; min-width: 150px;">
                        <p class="font-rale font-size-12 text-black-50 m-0 p-3">Xin chào,
                            <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Người dùng'; ?></p>
                        <a href="./logout.php" class="btn btn-danger btn-sm w-fit">Đăng xuất</a>
                    </div>
                </div>
            <?php } else { ?>
                <div class="font-rale font-size-14">
                    <a href="./register.php" class="px-3 text-dark">Đăng ký</a>
                    <a href="./login.php" class="px-3 border-right border-left text-dark">Đăng nhập</a>
                </div>
            <?php } ?>
        </div>

        <!-- Primary Navigation -->
        <nav style=" background: #d83131;" class="navbar navbar-expand-lg navbar-dark color-header-bg">
            <a class="navbar-brand" href="./index.php">
                <img width="100" src="./assets/logo.png" alt="logo" class="logo">
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav m-auto font-size-20">
                    <li class="nav-item">
                        <a class="nav-link" href="./index.php">Trang chủ</a>
                    </li>
                   <?php 
                        $categories = $product->getData('categories');
                        ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Danh mục
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <?php foreach ($categories as $category): ?>
                                    <a class="dropdown-item" href="./category.php?cate_id=<?php echo $category['id'] ?>"><?= $category['name']; ?></a>
                                <?php endforeach; ?>
                            </div>
                        </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./order.php">Đơn hàng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="./personal.php">Hồ sơ cá nhân</a>
                    </li>
                </ul>
                <form method="get" action="./search.php" class="">
                    <div class="input-group">
                        <?php $keyword = $_GET['keyword'] ?? ''; ?>
                        <input type="text" name="keyword" class="form-control search-product" placeholder="Search" value="<?php echo $keyword; ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary search-btn" type="submit">Tìm kiếm</button>
                        </div>
                    </div>
                </form>
                <form action="#" class="font-size-14 font-rale">
                    <a href="cart.php" class="py-2 rounded-pill color-primary-bg">
                        <span class="font-size-16 px-2 text-white"><i class="fas fa-shopping-cart"></i></span>
                        <span
                            class="px-3 py-2 rounded-pill text-dark bg-light"><?php echo count($product->getCartData($user_id ?? 0)); ?></span>
                    </a>
                </form>
            </div>
        </nav>
        <!-- !Primary Navigation -->

    </header>
    <!-- !start #header -->

    <!-- start #main-site -->
    <main id="main-site">

        <script>
            document.getElementById('userIcon').addEventListener('click', function() {
                const dropdown = document.getElementById('userDropdown');
                dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
            });

            // Đóng dropdown nếu click bên ngoài
            window.addEventListener('click', function(e) {
                const dropdown = document.getElementById('userDropdown');
                const userIcon = document.getElementById('userIcon');
                if (e.target !== dropdown && e.target !== userIcon) {
                    dropdown.style.display = 'none';
                }
            });
        </script>
        <button id="chat-bubble" class="chat-bubble">
    <i class="fas fa-comment"></i>
</button>

<div id="chat-window" class="chat-window">
    <div class="chat-header">
        Hỗ trợ AI
        <button id="chat-close-btn" class="chat-close">&times;</button>
    </div>
    <div id="chat-body" class="chat-body">
        <div class="chat-message ai">
            Chào bạn! Tôi có thể giúp gì cho bạn về máy tính và linh kiện?
        </div>
    </div>
    <div class="chat-footer">
        <input type="text" id="chat-input" placeholder="Nhập tin nhắn...">
        <button id="chat-send-btn"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>
<script>
// Đợi cho toàn bộ trang tải xong mới chạy script
document.addEventListener('DOMContentLoaded', function() {

    const chatBubble = document.getElementById('chat-bubble');
    const chatWindow = document.getElementById('chat-window');
    const chatCloseBtn = document.getElementById('chat-close-btn');
    const chatBody = document.getElementById('chat-body');
    const chatInput = document.getElementById('chat-input');
    const chatSendBtn = document.getElementById('chat-send-btn');

    // Mở cửa sổ chat
    chatBubble.addEventListener('click', () => {
        chatWindow.style.display = 'flex';
        chatBubble.style.display = 'none';
    });

    // Đóng cửa sổ chat
    chatCloseBtn.addEventListener('click', () => {
        chatWindow.style.display = 'none';
        chatBubble.style.display = 'block';
    });

    // Gửi tin nhắn khi nhấn nút
    chatSendBtn.addEventListener('click', sendMessage);

    // Gửi tin nhắn khi nhấn Enter
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function sendMessage() {
        const messageText = chatInput.value.trim();
        if (messageText === '') return;

        // 1. Hiển thị tin nhắn của người dùng
        appendMessage(messageText, 'user');
        chatInput.value = '';

        // 2. Gửi tin nhắn đến backend (chatbot_api.php)
       fetch('chatbot_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message: messageText })
})
.then(response => response.text()) // dùng text thay vì json
.then(text => {
    console.log('Backend trả:', text);
    const data = JSON.parse(text); // parse sau khi chắc chắn text là JSON
    appendMessage(data.reply.replace(/\n/g,'<br>'),'ai',true);
})
.catch(err => {
    console.error('Lỗi fetch:', err);
});

    }

    // Hàm để thêm tin nhắn vào cửa sổ chat
    function appendMessage(text, type, isHtml = false) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('chat-message', type);

        if (isHtml) {
            messageDiv.innerHTML = text; // Dùng cho AI (để hiển thị <br>)
        } else {
            messageDiv.textContent = text; // Dùng cho User (an toàn hơn)
        }

        chatBody.appendChild(messageDiv);

        // Tự động cuộn xuống tin nhắn mới nhất
        chatBody.scrollTop = chatBody.scrollHeight;
    }

});
</script>