<?php
require_once __DIR__ . "/../api/db.php";

/* ========================
   LẤY INPUT TÌM KIẾM / LỌC
   ======================== */
$keyword  = trim($_GET['q'] ?? "");
$platform = trim($_GET['platform'] ?? "");
$category = trim($_GET['category'] ?? "");
$sort     = trim($_GET['sort'] ?? "");

/* ========================
   LẤY LIST PLATFORM + CATEGORY
   ======================== */
// Fix: Sử dụng DISTINCT để tránh lặp nếu bảng platforms chưa chuẩn hoá
$platforms   = $conn->query("SELECT * FROM platforms ORDER BY id ASC");
$categories  = $conn->query("SELECT DISTINCT normalized_key FROM products WHERE normalized_key IS NOT NULL AND normalized_key != '' ORDER BY normalized_key ASC");

/* ========================
   BASE SQL
   ======================== */
$sql = "SELECT p.*, pf.name AS platform_name, pf.code AS platform_code
        FROM products p
        LEFT JOIN platforms pf ON p.platform_id = pf.id
        WHERE 1=1"; // Sử dụng 1=1 để dễ nối chuỗi AND

/* ========================
   BỘ LỌC
   ======================== */
if ($keyword !== "") {
    $safe = $conn->real_escape_string($keyword);
    $sql .= " AND p.title LIKE '%$safe%'";
}

if ($platform !== "") {
    $safe = intval($platform);
    $sql .= " AND p.platform_id = $safe";
}

if ($category !== "") {
    $safe = $conn->real_escape_string($category);
    $sql .= " AND p.normalized_key = '$safe'";
}

/* ========================
   SẮP XẾP
   ======================== */
switch ($sort) {
    case "price_asc":
        $sql .= " ORDER BY p.price_current ASC";
        break;
    case "price_desc":
        $sql .= " ORDER BY p.price_current DESC";
        break;
    case "name_asc":
        $sql .= " ORDER BY p.title ASC";
        break;
    case "name_desc":
        $sql .= " ORDER BY p.title DESC";
        break;
    default:
        $sql .= " ORDER BY p.id DESC";
}

/* ========================
   LẤY DỮ LIỆU
   ======================== */
$result = $conn->query($sql);
$product_list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $product_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products List</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Bổ sung một chút CSS nội bộ để xử lý badge nền tảng nếu cần */
        .platform-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            color: #fff;
            background: #333;
            margin-top: 5px;
        }
        .badge-sieuthiyte { background-color: #0056b3; }
        .badge-phana { background-color: #28a745; }
        .badge-ytesonhuong { background-color: #17a2b8; }
    </style>
</head>

<body>
<main class="main-content">

    <h1 class="hero-title" style="font-size: 24px; margin-bottom: 10px;">Saved Products & Search</h1>
    <p class="hero-description" style="margin-bottom: 20px;">
        Found <span class="badge" style="font-weight:bold; color:var(--primary);"><?= count($product_list) ?></span> products.
    </p>

    <form method="GET" class="search-bar-container">

        <input type="text" name="q" placeholder="Search product title..."
               value="<?= htmlspecialchars($keyword) ?>" class="search-input">

        <select name="platform" class="filter-select">
            <option value="">All Platforms</option>
            <?php 
            // Reset pointer dữ liệu platforms
            if($platforms) $platforms->data_seek(0); 
            while ($p = $platforms->fetch_assoc()): 
            ?>
                <option value="<?= $p['id'] ?>" <?= $platform == $p['id'] ? "selected" : "" ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="category" class="filter-select">
            <option value="">All Categories</option>
            <?php 
            if($categories) $categories->data_seek(0);
            while ($c = $categories->fetch_assoc()): 
            ?>
                <option value="<?= $c['normalized_key'] ?>" <?= $category == $c['normalized_key'] ? "selected" : "" ?>>
                    <?= ucwords(str_replace('-', ' ', $c['normalized_key'])) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="sort" class="filter-select">
            <option value="">Sort By...</option>
            <option value="price_asc"  <?= $sort == "price_asc"  ? "selected" : "" ?>>Price ↑</option>
            <option value="price_desc" <?= $sort == "price_desc" ? "selected" : "" ?>>Price ↓</option>
            <option value="name_asc"   <?= $sort == "name_asc"   ? "selected" : "" ?>>Name A → Z</option>
            <option value="name_desc"  <?= $sort == "name_desc"  ? "selected" : "" ?>>Name Z → A</option>
        </select>

        <button type="submit" class="btn btn-primary search-btn">Filter</button>

        <?php if ($keyword || $platform || $category || $sort): ?>
            <a href="products.php" class="btn" style="background: #e5e7eb; color: #333;">Reset</a>
        <?php endif; ?>
    </form>

    <div class="product-grid">
        <?php if (!empty($product_list)): ?>
            <?php foreach ($product_list as $row): ?>
                <?php 
                    // Tính toán % giảm giá nếu có
                    $discount = 0;
                    if ($row['price_original'] > $row['price_current']) {
                        $discount = round((($row['price_original'] - $row['price_current']) / $row['price_original']) * 100);
                    }
                    // Lấy code platform để tô màu badge
                    $pfCode = isset($row['platform_code']) ? $row['platform_code'] : 'unknown';
                ?>
                <div class="product-card">

                    <div class="product-image-container">
                        <img src="<?= htmlspecialchars($row['image_url']) ?>"
                             onerror="this.src='https://placehold.co/300x300?text=No+Image';"
                             class="product-image" alt="<?= htmlspecialchars($row['title']) ?>">
                        
                        <?php if ($discount > 0): ?>
                            <span class="discount-tag">-<?= $discount ?>%</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-info">
                        <h3 class="product-title" title="<?= htmlspecialchars($row['title']) ?>">
                            <?= htmlspecialchars($row['title']) ?>
                        </h3>

                        <div class="price-box">
                            <span class="current-price">
                                <?= ($row['price_current'] > 0) ? number_format($row['price_current']) . '₫' : 'Liên hệ' ?>
                            </span>
                            
                            <?php 
                            // Chỉ hiện giá cũ nếu giá hiện tại > 0 và có giá cũ
                            if ($row['price_current'] > 0 && $row['price_original'] > $row['price_current']): 
                            ?>
                                <span class="old-price"><?= number_format($row['price_original']) ?>₫</span>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 5px;">
                            <span class="platform-badge badge-<?= $pfCode ?>">
                                <?= htmlspecialchars($row['platform_name'] ?? "Unknown") ?>
                            </span>
                        </div>
                    </div>

                    <a href="<?= htmlspecialchars($row['product_url']) ?>" 
                       target="_blank" class="view-btn">
                        View on Website
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <p class="text-muted">No products found matching your criteria.</p>
                <a href="products.php" class="btn btn-primary" style="margin-top:10px;">View All Products</a>
            </div>
        <?php endif; ?>
    </div>

</main>
</body>
</html>