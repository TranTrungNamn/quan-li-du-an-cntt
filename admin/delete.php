<?php
require_once "../api/db.php";
session_start();

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// 2. Kiểm tra ID hợp lệ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php?error=missing_id");
    exit;
}

$id = intval($_GET['id']);

// 3. Thực hiện xóa (Sử dụng Prepared Statement để bảo mật)
// Bước 3.1: Xóa dữ liệu liên quan trước (nếu có bảng phụ thuộc như snapshots)
$conn->query("DELETE FROM product_snapshots WHERE product_id = $id");

// Bước 3.2: Xóa sản phẩm chính
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // [FIX] Chuyển hướng đúng về admin_dashboard.php thay vì index.php
    header("Location: admin_dashboard.php?msg=deleted");
} else {
    // Trường hợp lỗi SQL
    echo "Lỗi khi xóa: " . $conn->error;
}

$stmt->close();
exit;
?>