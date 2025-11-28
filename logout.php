<?php

    session_start();
    
    // LƯU GIỎ HÀNG GUEST VÀO BACKUP TRƯỚC KHI XÓA SESSION
    $guest_cart_backup = isset($_SESSION['guest_cart_backup']) ? $_SESSION['guest_cart_backup'] : null;
    $guest_id_backup = isset($_SESSION['guest_id_backup']) ? $_SESSION['guest_id_backup'] : null;
    
    // Xóa toàn bộ session
    session_unset();
    session_destroy();
    
    // Khởi động lại session mới
    session_start();
    
    // KHÔI PHỤC GIỎ HÀNG GUEST TỪ BACKUP
    if ($guest_cart_backup !== null) {
        $_SESSION['guest_cart'] = $guest_cart_backup;
    }
    
    if ($guest_id_backup !== null) {
        $_SESSION['guest_id'] = $guest_id_backup;
    } else {
        // Nếu không có backup, tạo guest_id mới
        $session_hash = crc32(session_id());
        $_SESSION['guest_id'] = -abs($session_hash);
    }

    header('location:index.php');

?>