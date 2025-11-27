<?php
session_start();
require_once __DIR__ . '/../api/db.php';

$sql = "SELECT id, title, image_url, price_current FROM products ORDER BY id DESC LIMIT 50";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Compare Products</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background:#f5f6fb; font-family:Inter,sans-serif; }

        .container { width:90%; margin:40px auto; }
        h2 { font-size:26px; margin-bottom:20px; }

        .grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(240px,1fr));
            gap:16px;
        }

        .card {
            background:white;
            border-radius:12px;
            overflow:hidden;
            box-shadow:0 2px 6px rgba(0,0,0,0.08);
            height:300px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
        }

        .card img {
            width:100%; height:150px; object-fit:contain; background:#fff;
        }

        .card .info {
            padding:10px;
        }

        .card h3 {
            font-size:14px;
            height:40px;
            overflow:hidden;
            margin-bottom:6px;
        }

        .price {
            font-weight:600;
            color:#d32f2f;
            margin-bottom:10px;
        }

        .compare-btn {
            padding:10px; text-align:center; background:#111; color:white;
            text-decoration:none; display:block; border-radius:0 0 12px 12px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Chọn sản phẩm để so sánh</h2>

    <div class="grid">

        <?php while($row = $result->fetch_assoc()): ?>
        <div class="card">
            <img src="<?= $row['image_url'] ?>">
            <div class="info">
                <h3><?= $row['title'] ?></h3>
                <div class="price"><?= number_format($row['price_current']) ?> đ</div>
            </div>

            <a class="compare-btn" href="compare_view.php?id1=<?= $row['id'] ?>">
                So sánh sản phẩm này
            </a>`
        </div>
        <?php endwhile; ?>

    </div>
</div>

</body>
</html>
