<?php
session_start();
require_once __DIR__ . '/../api/db.php';

$id1 = isset($_GET['id1']) ? (int)$_GET['id1'] : 0;
$id2 = isset($_GET['id2']) ? (int)$_GET['id2'] : 0;

function getProduct(mysqli $conn, int $id): ?array {
    if ($id <= 0) return null;
    $sql = "SELECT p.*, pf.name as platform_name, pf.code as platform_code 
            FROM products p 
            LEFT JOIN platforms pf ON p.platform_id = pf.id 
            WHERE p.id = $id";
    $res = $conn->query($sql);
    return $res && $res->num_rows ? $res->fetch_assoc() : null;
}

$p1 = getProduct($conn, $id1);
$p2 = getProduct($conn, $id2);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Compare Result</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="iframe-page-body">

<main class="main-content compare-container">
    
    <div class="compare-header">
        <h1 class="hero-title">Comparison</h1>
        <a href="compare.php" class="btn btn-primary" style="background:#f3f4f6; color:#333;">← Select other products</a>
    </div>

    <div class="compare-layout">
        
        <?php if ($p1): ?>
            <div class="compare-item">
                <div class="compare-img-box">
                    <img src="<?= htmlspecialchars($p1['image_url']) ?>" onerror="this.src='https://placehold.co/300x300?text=No+Img'">
                </div>
                
                <span class="tag-platform badge-<?= $p1['platform_code'] ?>">
                    <?= htmlspecialchars($p1['platform_name']) ?>
                </span>

                <h3 class="compare-title-large">
                    <a href="<?= $p1['product_url'] ?>" target="_blank" style="color:inherit; text-decoration:none;">
                        <?= htmlspecialchars($p1['title']) ?>
                    </a>
                </h3>

                <div class="compare-price-large">
                    <?= ($p1['price_current'] > 0) ? number_format($p1['price_current']) . ' ₫' : 'Liên hệ' ?>
                </div>

                <a href="<?= htmlspecialchars($p1['product_url']) ?>" target="_blank" class="btn btn-primary" style="width:100%">Mua ngay</a>

                <div class="compare-specs">
                    <div class="compare-row">
                        <span class="spec-label">Giá gốc</span>
                        <span class="spec-value" style="text-decoration:line-through; color:#999">
                            <?= ($p1['price_original'] > 0) ? number_format($p1['price_original']) . ' ₫' : '—' ?>
                        </span>
                    </div>
                    <div class="compare-row">
                        <span class="spec-label">Shop / Brand</span>
                        <span class="spec-value"><?= htmlspecialchars($p1['shop_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="compare-row">
                        <span class="spec-label">Đã bán</span>
                        <span class="spec-value"><?= number_format($p1['sold_quantity']) ?></span>
                    </div>
                    <div class="compare-row">
                        <span class="spec-label">Đánh giá</span>
                        <span class="spec-value"><?= $p1['rating_avg'] > 0 ? $p1['rating_avg'] . ' ⭐' : 'Chưa có' ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-slot">
                <h3>Chưa chọn sản phẩm 1</h3>
                <a href="compare.php">Quay lại chọn</a>
            </div>
        <?php endif; ?>

        <?php if ($p2): ?>
            <div class="compare-item">
                <div class="compare-img-box">
                    <img src="<?= htmlspecialchars($p2['image_url']) ?>" onerror="this.src='https://placehold.co/300x300?text=No+Img'">
                </div>
                
                <span class="tag-platform badge-<?= $p2['platform_code'] ?>">
                    <?= htmlspecialchars($p2['platform_name']) ?>
                </span>

                <h3 class="compare-title-large">
                    <a href="<?= $p2['product_url'] ?>" target="_blank" style="color:inherit; text-decoration:none;">
                        <?= htmlspecialchars($p2['title']) ?>
                    </a>
                </h3>

                <div class="compare-price-large">
                    <?= ($p2['price_current'] > 0) ? number_format($p2['price_current']) . ' ₫' : 'Liên hệ' ?>
                </div>

                <a href="<?= htmlspecialchars($p2['product_url']) ?>" target="_blank" class="btn btn-primary" style="width:100%">Mua ngay</a>

                <div class="compare-specs">
                    <div class="compare-row">
                        <span class="spec-label">Giá gốc</span>
                        <span class="spec-value" style="text-decoration:line-through; color:#999">
                            <?= ($p2['price_original'] > 0) ? number_format($p2['price_original']) . ' ₫' : '—' ?>
                        </span>
                    </div>
                    <div class="compare-row">
                        <span class="spec-label">Shop / Brand</span>
                        <span class="spec-value"><?= htmlspecialchars($p2['shop_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="compare-row">
                        <span class="spec-label">Đã bán</span>
                        <span class="spec-value"><?= number_format($p2['sold_quantity']) ?></span>
                    </div>
                    <div class="compare-row">
                        <span class="spec-label">Đánh giá</span>
                        <span class="spec-value"><?= $p2['rating_avg'] > 0 ? $p2['rating_avg'] . ' ⭐' : 'Chưa có' ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-slot">
                <h3>Chưa chọn sản phẩm 2</h3>
                <a href="compare.php">Quay lại chọn</a>
            </div>
        <?php endif; ?>

    </div>

</main>

</body>
</html>