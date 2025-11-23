<?php
require_once "../api/db.php";
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) die("Missing product ID");
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) die("Product not found");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST["title"];
    $price = intval($_POST["price"]);
    $shop = $_POST["shop_name"];
    $url  = $_POST["product_url"];

    $update = $conn->prepare("UPDATE products SET title=?, price_current=?, shop_name=?, product_url=?, updated_at=NOW() WHERE id=?");
    $update->bind_param("sissi", $title, $price, $shop, $url, $id);
    $update->execute();
    header("Location: index.php?msg=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<div class="container mt-20">
    <h2 class="text-center">Edit Product</h2>

    <div class="form-box">
        <form method="POST">
            <label>Product Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($product['title']) ?>">

            <label>Price (current)</label>
            <input type="number" name="price" value="<?= $product['price_current'] ?>">

            <label>Shop Name</label>
            <input type="text" name="shop_name" value="<?= htmlspecialchars($product['shop_name']) ?>">

            <label>Product URL</label>
            <input type="text" name="product_url" value="<?= htmlspecialchars($product['product_url']) ?>">

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="admin_dashboard.php" class="btn form-cancel-btn">Cancel</a>
        </form>
    </div>
</div>

</body>
</html>