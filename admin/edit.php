<?php
require_once "../api/db.php";
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) die("Missing product ID");
$id = intval($_GET['id']);

// Lấy dữ liệu sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) die("Product not found");

// Xử lý cập nhật
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $price = intval($_POST["price"]);
    $shop  = $_POST["shop_name"];
    $url   = $_POST["product_url"];

    $update = $conn->prepare("UPDATE products SET title=?, price_current=?, shop_name=?, product_url=?, updated_at=NOW() WHERE id=?");
    $update->bind_param("sissi", $title, $price, $shop, $url, $id);
    $update->execute();
    header("Location: admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<nav class="admin-navbar">
    <a href="admin_dashboard.php" class="brand-logo">Admin Panel</a>
    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
</nav>

<div class="auth-wrapper" style="align-items: flex-start; padding-top: 40px;">
    <div class="auth-card" style="max-width: 600px;">
        <h2 class="auth-title" style="text-align: left;">Edit Product</h2>
        <p class="auth-subtitle" style="text-align: left; margin-bottom: 24px;">ID: #<?= $id ?></p>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Product Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($product['title']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Current Price (VND)</label>
                <input type="number" name="price" value="<?= $product['price_current'] ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Shop / Brand Name</label>
                <input type="text" name="shop_name" value="<?= htmlspecialchars($product['shop_name']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Product URL</label>
                <input type="text" name="product_url" value="<?= htmlspecialchars($product['product_url']) ?>">
            </div>

            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>