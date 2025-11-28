<?php
// database/connection.php
// Kết nối MySQL bằng mysqli, an toàn hơn so với die() hiển thị lỗi trực tiếp

// Bật báo lỗi theo exception (tiện debug trong dev)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'webbanlinhkien';

$conn = null;

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Log lỗi (file log, syslog, v.v.) — đừng hiển thị chi tiết cho user
    error_log("DB connection error: " . $e->getMessage());
    // Thông báo thân thiện cho client
    exit('Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.');
}

// Bạn có thể thêm helper nhỏ ở đây nếu muốn, ví dụ:
function db_get_user_by_id($id) {
    global $conn;
    $sql = "SELECT id, email, name, phone, address, city, province, postal_code FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
