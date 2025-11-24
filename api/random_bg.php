<?php
// api/random_bg.php

// Tắt báo lỗi để không làm hỏng dữ liệu ảnh
error_reporting(0);
ini_set('display_errors', 0);

// 1. Đường dẫn từ thư mục api đi ngược ra ngoài -> vào assets -> images -> backgrounds
// Cấu trúc: root/api/random_bg.php  ---> root/assets/images/backgrounds/
$folder = __DIR__ . '/../assets/images/backgrounds/';

// 2. Kiểm tra thư mục có tồn tại không
if (!is_dir($folder)) {
    header("HTTP/1.0 404 Not Found");
    die("Folder not found");
}

// 3. Lấy danh sách file ảnh (jpg, jpeg, png, webp)
$files = glob($folder . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);

// 4. Nếu có ảnh, chọn ngẫu nhiên
if ($files && count($files) > 0) {
    $randomFile = $files[array_rand($files)];
    
    // Lấy đuôi file để set header đúng
    $ext = strtolower(pathinfo($randomFile, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg': 
        case 'jpeg': header('Content-Type: image/jpeg'); break;
        case 'png':  header('Content-Type: image/png'); break;
        case 'webp': header('Content-Type: image/webp'); break;
        default:     header('Content-Type: image/jpeg');
    }
    
    // Xuất file ảnh
    readfile($randomFile);
} else {
    // Không có ảnh thì trả về lỗi 404 (sẽ hiện màu nền mặc định)
    header("HTTP/1.0 404 Not Found");
    echo "No images found in " . $folder;
}
?>