<?php
session_start();
require_once __DIR__ . '/../api/db.php';
// ... (Giữ nguyên phần logic PHP) ...
// ... (Hàm getProductById, $listSql, v.v...) ...
$id1 = isset($_GET['id1']) ? (int)$_GET['id1'] : 0;
$id2 = isset($_GET['id2']) ? (int)$_GET['id2'] : 0;

function getProductById(mysqli $conn, int $id): ?array {
    if ($id <= 0) return null;
    $sql = "SELECT * FROM products WHERE id = " . $id . " LIMIT 1";
    $res = $conn->query($sql);
    return $res && $res->num_rows ? $res->fetch_assoc() : null;
}

$product1 = getProductById($conn, $id1);
$product2 = getProductById($conn, $id2);

$listSql = "SELECT id, title, image_url, price_current FROM products WHERE id <> {$id1} ORDER BY id DESC LIMIT 80";
$listResult = $conn->query($listSql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Compare Products - Tiki Style</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<div class="compare-view-container">
    <h1>Compare Products</h1>
    <h2>So sánh sản phẩm</h2>

    <div class="compare-row">
        <?php if ($product1): ?>
            <?php
                $p1 = $product1;
                $p1PriceCur = (int)$p1['price_current'];
                $p1PriceOrig = (int)$p1['price_original'];
                $p1Discount = ($p1PriceOrig > 0 && $p1PriceCur > 0 && $p1PriceCur < $p1PriceOrig)
                    ? round(100 * ($p1PriceOrig - $p1PriceCur) / $p1PriceOrig) : 0;
                $p1Sold = (int)$p1['sold_quantity'];
                $p1Rating = isset($p1['rating_avg']) ? (float)$p1['rating_avg'] : 0;
                $p1RatingCount = isset($p1['rating_count']) ? (int)$p1['rating_count'] : 0;
            ?>
            <div class="compare-card">
                <div class="compare-image-wrap">
                    <img src="<?= htmlspecialchars($p1['image_url']) ?>" alt="">
                </div>
                <div class="compare-title"><?= htmlspecialchars($p1['title']) ?></div>
                <div class="price-row">
                    <div class="compare-price-current">
                        <?= $p1PriceCur > 0 ? number_format($p1PriceCur) . " đ" : "—" ?>
                    </div>
                    <?php if ($p1PriceOrig > 0): ?>
                        <div>
                            <span class="compare-price-original"><?= number_format($p1PriceOrig) ?> đ</span>
                            <?php if ($p1Discount > 0): ?>
                                <span class="badge-discount">-<?= $p1Discount ?>%</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="compare-meta-row">
                    <div>
                        <?php if ($p1Rating > 0): ?>
                            <span class="stars">
                                <?php
                                $filled = floor($p1Rating);
                                for ($i = 0; $i < 5; $i++) echo $i < $filled ? "★" : "☆";
                                ?>
                            </span>
                            <span class="rating-count">(<?= $p1RatingCount ?>)</span>
                        <?php else: ?>
                            <span class="rating-count">Chưa có đánh giá</span>
                        <?php endif; ?>
                    </div>
                    <div class="sold">
                        <?= $p1Sold > 0 ? "Đã bán " . number_format($p1Sold) : "Chưa có lượt bán" ?>
                    </div>
                </div>
                <div class="shop-name">
                    <?= !empty($p1['shop_name']) ? htmlspecialchars($p1['shop_name']) : "Không rõ shop" ?>
                </div>
                <div class="compare-btn-row">
                    <?php if (!empty($p1['product_url'])): ?>
                        <a class="btn-tiki-primary" href="<?= htmlspecialchars($p1['product_url']) ?>" target="_blank">Mua ngay</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="select-placeholder"><span>Chưa chọn sản phẩm 1</span></div>
        <?php endif; ?>

        <div>
            <?php if ($product2): ?>
                <?php
                    $p2 = $product2;
                    $p2PriceCur = (int)$p2['price_current'];
                    $p2PriceOrig = (int)$p2['price_original'];
                    $p2Discount = ($p2PriceOrig > 0 && $p2PriceCur > 0 && $p2PriceCur < $p2PriceOrig)
                        ? round(100 * ($p2PriceOrig - $p2PriceCur) / $p2PriceOrig) : 0;
                    $p2Sold = (int)$p2['sold_quantity'];
                    $p2Rating = isset($p2['rating_avg']) ? (float)$p2['rating_avg'] : 0;
                    $p2RatingCount = isset($p2['rating_count']) ? (int)$p2['rating_count'] : 0;
                ?>
                <div class="compare-card">
                    <div class="compare-image-wrap">
                        <img src="<?= htmlspecialchars($p2['image_url']) ?>" alt="">
                    </div>
                    <div class="compare-title"><?= htmlspecialchars($p2['title']) ?></div>
                    <div class="price-row">
                        <div class="compare-price-current">
                            <?= $p2PriceCur > 0 ? number_format($p2PriceCur) . " đ" : "—" ?>
                        </div>
                        <?php if ($p2PriceOrig > 0): ?>
                            <div>
                                <span class="compare-price-original"><?= number_format($p2PriceOrig) ?> đ</span>
                                <?php if ($p2Discount > 0): ?>
                                    <span class="badge-discount">-<?= $p2Discount ?>%</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="compare-meta-row">
                        <div>
                            <?php if ($p2Rating > 0): ?>
                                <span class="stars">
                                    <?php
                                    $filled = floor($p2Rating);
                                    for ($i = 0; $i < 5; $i++) echo $i < $filled ? "★" : "☆";
                                    ?>
                                </span>
                                <span class="rating-count">(<?= $p2RatingCount ?>)</span>
                            <?php else: ?>
                                <span class="rating-count">Chưa có đánh giá</span>
                            <?php endif; ?>
                        </div>
                        <div class="sold">
                            <?= $p2Sold > 0 ? "Đã bán " . number_format($p2Sold) : "Chưa có lượt bán" ?>
                        </div>
                    </div>
                    <div class="shop-name">
                        <?= !empty($p2['shop_name']) ? htmlspecialchars($p2['shop_name']) : "Không rõ shop" ?>
                    </div>
                    <div class="compare-btn-row">
                        <?php if (!empty($p2['product_url'])): ?>
                            <a class="btn-tiki-primary" href="<?= htmlspecialchars($p2['product_url']) ?>" target="_blank">Mua ngay</a>
                        <?php endif; ?>
                        <a class="btn-tiki-outline" href="compare_view.php?id1=<?= $id1 ?>">Đổi sản phẩm khác</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="select-placeholder">
                    <span>Chọn sản phẩm thứ 2 để so sánh</span>
                    <form>
                        <input type="hidden" name="id1" value="<?= $id1 ?>">
                        <select name="id2" class="selector" onchange="this.form.submit()">
                            <option value="">Chọn sản phẩm…</option>
                            <?php if ($listResult): ?>
                                <?php while ($r = $listResult->fetch_assoc()): ?>
                                    <option value="<?= $r['id'] ?>">
                                        <?= htmlspecialchars($r['title']) ?> - <?= number_format($r['price_current']) ?> đ
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($product1 && $product2): ?>
        <?php
            $category1 = !empty($product1['category_id']) ? ('Danh mục ID #' . $product1['category_id']) : 'Không rõ';
            $category2 = !empty($product2['category_id']) ? ('Danh mục ID #' . $product2['category_id']) : 'Không rõ';
            $brand1 = !empty($product1['shop_name']) ? $product1['shop_name'] : 'Không rõ';
            $brand2 = !empty($product2['shop_name']) ? $product2['shop_name'] : 'Không rõ';
        ?>
        <div class="compare-table-card">
            <h3>Bảng so sánh chi tiết</h3>
            <table class="compare-table">
                <tr><th></th><th><?= htmlspecialchars($product1['title']) ?></th><th><?= htmlspecialchars($product2['title']) ?></th></tr>
                <tr><td class="compare-label">Giá</td><td><?= $p1PriceCur > 0 ? number_format($p1PriceCur) . " đ" : "—" ?></td><td><?= $p2PriceCur > 0 ? number_format($p2PriceCur) . " đ" : "—" ?></td></tr>
                <tr><td class="compare-label">Lượt bán</td><td><?= $p1Sold > 0 ? number_format($p1Sold) : "—" ?></td><td><?= $p2Sold > 0 ? number_format($p2Sold) : "—" ?></td></tr>
                <tr><td class="compare-label">Shop / Brand</td><td><?= htmlspecialchars($brand1) ?></td><td><?= htmlspecialchars($brand2) ?></td></tr>
                <tr><td class="compare-label">Category</td><td><?= htmlspecialchars($category1) ?></td><td><?= htmlspecialchars($category2) ?></td></tr>
                <tr><td class="compare-label">Link</td>
                    <td><?php if (!empty($product1['product_url'])): ?><a class="link-view" href="<?= htmlspecialchars($product1['product_url']) ?>" target="_blank">Xem</a><?php else: ?>—<?php endif; ?></td>
                    <td><?php if (!empty($product2['product_url'])): ?><a class="link-view" href="<?= htmlspecialchars($product2['product_url']) ?>" target="_blank">Xem</a><?php else: ?>—<?php endif; ?></td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>