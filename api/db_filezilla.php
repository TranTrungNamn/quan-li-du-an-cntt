<?php
// Tắt báo lỗi hiển thị ra màn hình (để tránh làm hỏng JSON)
error_reporting(0); 
ini_set('display_errors', 0);

// Thay thế bằng thông tin thật từ Hosting của bạn
$host   = "localhost"; // Thường vẫn là localhost, nhưng có host dùng IP riêng
$user   = "sql_nhom30_itimi"; // Ví dụ: id12345_admin
$pass   = "71bbaeb8a35948"; 
$dbname = "sql_nhom30_itimi"; // Ví dụ: id12345_sql_nhom30

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    // Trả về JSON lỗi thay vì die text thuần
    header('Content-Type: application/json'); 
    echo json_encode([
        "status" => "error", 
        "message" => "Lỗi kết nối DB: " . $conn->connect_error
    ]);
    exit();
}

mysqli_set_charset($conn, "utf8");