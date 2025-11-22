<?php
session_start();
require_once __DIR__ . '/../api/db.php';

// Lấy id sản phẩm
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

// Danh sách gợi ý sản phẩm để chọn làm SP2
$listSql = "SELECT id, title, image_url, price_current 
            FROM products 
            WHERE id <> {$id1} 
            ORDER BY id DESC 
            LIMIT 80";
$listResult = $conn->query($listSql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Compare Products - Tiki Style</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --tiki-blue: #1a94ff;
            --tiki-blue-soft: #e6f3ff;
            --bg: #f5f6fb;
            --text-main: #111827;
            --muted: #6b7280;
            --danger: #ef4444;
            --card-radius: 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--bg);
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
        }

        .container {
            width: min(1120px, 100%);
            margin: 32px auto 48px;
            padding: 0 16px 40px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 16px;
            font-weight: 600;
        }

        h2 {
            font-size: 20px;
            margin: 16px 0;
            font-weight: 600;
        }

        /* ---------- TOP COMPARE CARD (2 SP) ---------- */

        .compare-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 12px;
            margin-bottom: 24px;
        }

        .product-card {
            background: #fff;
            border-radius: var(--card-radius);
            padding: 20px 20px 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .product-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top, rgba(26, 148, 255, 0.18), transparent 55%);
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .product-card:hover::before {
            opacity: 1;
        }

        .product-image-wrap {
            width: 220px;
            height: 220px;
            border-radius: 16px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 12px;
            transition: transform 0.25s ease;
        }

        .product-card:hover .product-image-wrap {
            transform: translateY(-4px) scale(1.02);
        }

        .product-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .product-title {
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            line-height: 1.35;
            height: 40px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .price-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            margin-bottom: 4px;
        }

        .price-current {
            font-size: 20px;
            font-weight: 700;
            color: var(--danger);
        }

        .price-original {
            font-size: 13px;
            color: var(--muted);
            text-decoration: line-through;
        }

        .badge-discount {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(248, 113, 113, 0.08);
            color: var(--danger);
            font-size: 11px;
            font-weight: 600;
            margin-left: 4px;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0 10px;
            font-size: 13px;
            color: var(--muted);
        }

        .stars {
            color: #facc15;
            font-size: 14px;
        }

        .rating-count {
            font-size: 12px;
            color: var(--muted);
        }

        .sold {
            font-size: 13px;
        }

        .shop-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--tiki-blue);
        }

        .btn-row {
            display: flex;
            justify-content: center;
            margin-top: 8px;
            gap: 10px;
        }

        .btn-primary {
            border: none;
            outline: none;
            cursor: pointer;
            padding: 8px 18px;
            border-radius: 999px;
            background: var(--tiki-blue);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 10px 25px rgba(26, 148, 255, 0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            background: #1684e5;
            box-shadow: 0 12px 30px rgba(26, 148, 255, 0.45);
        }

        .btn-outline {
            border-radius: 999px;
            padding: 7px 16px;
            border: 1px solid rgba(148, 163, 184, 0.8);
            background: #fff;
            font-size: 13px;
            color: #111827;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        /* ---------- SELECT BOX CHO SP THỨ 2 ---------- */

        .select-placeholder {
            border: 2px dashed rgba(148, 163, 184, 0.9);
            border-radius: var(--card-radius);
            padding: 24px;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
        }

        .select-placeholder span {
            font-size: 14px;
            color: var(--muted);
        }

        .selector {
            width: 100%;
            max-width: 320px;
            padding: 9px 10px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            outline: none;
        }

        /* ---------- BẢNG SO SÁNH CHI TIẾT ---------- */

        .compare-table-card {
            margin-top: 10px;
            background: #fff;
            border-radius: var(--card-radius);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            padding: 18px 22px 20px;
        }

        .compare-table-card h3 {
            margin: 0 0 14px;
            font-size: 17px;
            font-weight: 600;
        }

        .compare-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .compare-table th,
        .compare-table td {
            padding: 10px 10px;
            text-align: left;
        }

        .compare-table th {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--muted);
            border-bottom: 1px solid #e5e7eb;
        }

        .compare-table tr:nth-child(even) td {
            background: #f9fafb;
        }

        .compare-label {
            width: 18%;
            font-weight: 500;
        }

        .link-view {
            color: var(--tiki-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .link-view:hover {
            text-decoration: underline;
        }

        /* ---------- RESPONSIVE ---------- */

        @media (max-width: 900px) {
            .compare-row {
                grid-template-columns: 1fr;
            }
            .product-card,
            .select-placeholder {
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            .product-image-wrap {
                width: 170px;
                height: 170px;
            }
        }

        /* ---------- LOADING SKELETON NHẸ ---------- */
        .skeleton {
            position: relative;
            overflow: hidden;
            background: #e5e7eb;
        }

        .skeleton::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(255,255,255,0), rgba(255,255,255,0.8), rgba(255,255,255,0));
            transform: translateX(-100%);
            animation: shimmer 1.2s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Compare Products</h1>

    <h2>So sánh sản phẩm</h2>

    <div class="compare-row">

        <!-- Sản phẩm 1 -->
        <?php if ($product1): ?>
            <?php
                $p1 = $product1;
                $p1PriceCur = (int)$p1['price_current'];
                $p1PriceOrig = (int)$p1['price_original'];
                $p1Discount = ($p1PriceOrig > 0 && $p1PriceCur > 0 && $p1PriceCur < $p1PriceOrig)
                    ? round(100 * ($p1PriceOrig - $p1PriceCur) / $p1PriceOrig)
                    : 0;
                $p1Sold = (int)$p1['sold_quantity'];
                $p1Rating = isset($p1['rating_avg']) ? (float)$p1['rating_avg'] : 0;
                $p1RatingCount = isset($p1['rating_count']) ? (int)$p1['rating_count'] : 0;
            ?>
            <div class="product-card">
                <div class="product-image-wrap">
                    <img src="<?= htmlspecialchars($p1['image_url']) ?>" alt="">
                </div>

                <div class="product-title"><?= htmlspecialchars($p1['title']) ?></div>

                <div class="price-row">
                    <div class="price-current">
                        <?= $p1PriceCur > 0 ? number_format($p1PriceCur) . " đ" : "—" ?>
                    </div>
                    <?php if ($p1PriceOrig > 0): ?>
                        <div>
                            <span class="price-original"><?= number_format($p1PriceOrig) ?> đ</span>
                            <?php if ($p1Discount > 0): ?>
                                <span class="badge-discount">-<?= $p1Discount ?>%</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="meta-row">
                    <div>
                        <?php if ($p1Rating > 0): ?>
                            <span class="stars">
                                <?php
                                $filled = floor($p1Rating);
                                for ($i = 0; $i < 5; $i++) {
                                    echo $i < $filled ? "★" : "☆";
                                }
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

                <div class="btn-row">
                    <?php if (!empty($p1['product_url'])): ?>
                        <a class="btn-primary" href="<?= htmlspecialchars($p1['product_url']) ?>" target="_blank">
                            Mua ngay
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="select-placeholder">
                <span>Chưa chọn sản phẩm 1</span>
            </div>
        <?php endif; ?>

        <!-- Sản phẩm 2 -->
        <div>
            <?php if ($product2): ?>
                <?php
                    $p2 = $product2;
                    $p2PriceCur = (int)$p2['price_current'];
                    $p2PriceOrig = (int)$p2['price_original'];
                    $p2Discount = ($p2PriceOrig > 0 && $p2PriceCur > 0 && $p2PriceCur < $p2PriceOrig)
                        ? round(100 * ($p2PriceOrig - $p2PriceCur) / $p2PriceOrig)
                        : 0;
                    $p2Sold = (int)$p2['sold_quantity'];
                    $p2Rating = isset($p2['rating_avg']) ? (float)$p2['rating_avg'] : 0;
                    $p2RatingCount = isset($p2['rating_count']) ? (int)$p2['rating_count'] : 0;
                ?>
                <div class="product-card">
                    <div class="product-image-wrap">
                        <img src="<?= htmlspecialchars($p2['image_url']) ?>" alt="">
                    </div>

                    <div class="product-title"><?= htmlspecialchars($p2['title']) ?></div>

                    <div class="price-row">
                        <div class="price-current">
                            <?= $p2PriceCur > 0 ? number_format($p2PriceCur) . " đ" : "—" ?>
                        </div>
                        <?php if ($p2PriceOrig > 0): ?>
                            <div>
                                <span class="price-original"><?= number_format($p2PriceOrig) ?> đ</span>
                                <?php if ($p2Discount > 0): ?>
                                    <span class="badge-discount">-<?= $p2Discount ?>%</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="meta-row">
                        <div>
                            <?php if ($p2Rating > 0): ?>
                                <span class="stars">
                                <?php
                                $filled = floor($p2Rating);
                                for ($i = 0; $i < 5; $i++) {
                                    echo $i < $filled ? "★" : "☆";
                                }
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

                    <div class="btn-row">
                        <?php if (!empty($p2['product_url'])): ?>
                            <a class="btn-primary" href="<?= htmlspecialchars($p2['product_url']) ?>" target="_blank">
                                Mua ngay
                            </a>
                        <?php endif; ?>
                        <a class="btn-outline" href="compare_view.php?id1=<?= $id1 ?>">
                            Đổi sản phẩm khác
                        </a>
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
            // Giá + discount đã tính ở trên (p1PriceCur, p1PriceOrig, p1Discount, ...)
            $category1 = !empty($product1['category_id']) ? ('Danh mục ID #' . $product1['category_id']) : 'Không rõ';
            $category2 = !empty($product2['category_id']) ? ('Danh mục ID #' . $product2['category_id']) : 'Không rõ';
            $brand1 = !empty($product1['shop_name']) ? $product1['shop_name'] : 'Không rõ';
            $brand2 = !empty($product2['shop_name']) ? $product2['shop_name'] : 'Không rõ';
        ?>

        <div class="compare-table-card">
            <h3>Bảng so sánh chi tiết</h3>

            <table class="compare-table">
                <tr>
                    <th></th>
                    <th><?= htmlspecialchars($product1['title']) ?></th>
                    <th><?= htmlspecialchars($product2['title']) ?></th>
                </tr>

                <tr>
                    <td class="compare-label">Giá</td>
                    <td><?= $p1PriceCur > 0 ? number_format($p1PriceCur) . " đ" : "—" ?></td>
                    <td><?= $p2PriceCur > 0 ? number_format($p2PriceCur) . " đ" : "—" ?></td>
                </tr>

                <tr>
                    <td class="compare-label">Giá gốc</td>
                    <td>
                        <?= $p1PriceOrig > 0 ? number_format($p1PriceOrig) . " đ" : "—" ?>
                        <?= $p1Discount > 0 ? " (-{$p1Discount}%)" : "" ?>
                    </td>
                    <td>
                        <?= $p2PriceOrig > 0 ? number_format($p2PriceOrig) . " đ" : "—" ?>
                        <?= $p2Discount > 0 ? " (-{$p2Discount}%)" : "" ?>
                    </td>
                </tr>

                <tr>
                    <td class="compare-label">Lượt bán</td>
                    <td><?= $p1Sold > 0 ? number_format($p1Sold) : "—" ?></td>
                    <td><?= $p2Sold > 0 ? number_format($p2Sold) : "—" ?></td>
                </tr>

                <tr>
                    <td class="compare-label">Đánh giá</td>
                    <td>
                        <?php if ($p1Rating > 0): ?>
                            <?= number_format($p1Rating, 1) ?>/5 (<?= $p1RatingCount ?> lượt)
                        <?php else: ?>
                            Chưa có
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p2Rating > 0): ?>
                            <?= number_format($p2Rating, 1) ?>/5 (<?= $p2RatingCount ?> lượt)
                        <?php else: ?>
                            Chưa có
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <td class="compare-label">Shop / Brand</td>
                    <td><?= htmlspecialchars($brand1) ?></td>
                    <td><?= htmlspecialchars($brand2) ?></td>
                </tr>

                <tr>
                    <td class="compare-label">Category</td>
                    <td><?= htmlspecialchars($category1) ?></td>
                    <td><?= htmlspecialchars($category2) ?></td>
                </tr>

                <tr>
                    <td class="compare-label">Link sản phẩm</td>
                    <td>
                        <?php if (!empty($product1['product_url'])): ?>
                            <a class="link-view" href="<?= htmlspecialchars($product1['product_url']) ?>" target="_blank">Xem</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($product2['product_url'])): ?>
                            <a class="link-view" href="<?= htmlspecialchars($product2['product_url']) ?>" target="_blank">Xem</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
