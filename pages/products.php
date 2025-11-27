<?php
require_once __DIR__ . "/../api/db.php";

/* ========================
   1. LOGIC PHP
   ======================== */
$keyword  = trim($_GET['q'] ?? "");
$platform = trim($_GET['platform'] ?? "");
$category = trim($_GET['category'] ?? "");
$sort     = trim($_GET['sort'] ?? "");

$platforms   = $conn->query("SELECT * FROM platforms ORDER BY id ASC");
$categories  = $conn->query("SELECT DISTINCT normalized_key FROM products WHERE normalized_key IS NOT NULL AND normalized_key != '' ORDER BY normalized_key ASC");

$sql = "SELECT p.*, pf.name AS platform_name, pf.code AS platform_code
        FROM products p
        LEFT JOIN platforms pf ON p.platform_id = pf.id
        WHERE 1=1";

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

switch ($sort) {
    case "price_asc":  $sql .= " ORDER BY p.price_current ASC"; break;
    case "price_desc": $sql .= " ORDER BY p.price_current DESC"; break;
    case "name_asc":   $sql .= " ORDER BY p.title ASC"; break;
    case "name_desc":  $sql .= " ORDER BY p.title DESC"; break;
    default:           $sql .= " ORDER BY p.id DESC";
}

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
</head>

<body>
<main class="main-content">

    <h1 class="hero-title" style="font-size: 24px; margin-bottom: 10px;">Products Library</h1>
    <p class="hero-description" style="margin-bottom: 20px; color:#666;">
        Found <strong style="color:#111;"><?= count($product_list) ?></strong> items.
    </p>

    <form method="GET" class="search-bar-container" style="background:white; padding:15px; border-radius:8px; border:1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <input type="text" name="q" placeholder="Search products..." value="<?= htmlspecialchars($keyword) ?>" class="search-input">

        <select name="platform" class="filter-select">
            <option value="">All Platforms</option>
            <?php if($platforms) $platforms->data_seek(0); while ($p = $platforms->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>" <?= $platform == $p['id'] ? "selected" : "" ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="category" class="filter-select">
            <option value="">All Categories</option>
            <?php if($categories) $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                <option value="<?= $c['normalized_key'] ?>" <?= $category == $c['normalized_key'] ? "selected" : "" ?>>
                    <?= ucwords(str_replace('-', ' ', $c['normalized_key'])) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="sort" class="filter-select">
            <option value="">Default Sort</option>
            <option value="price_asc"  <?= $sort == "price_asc"  ? "selected" : "" ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sort == "price_desc" ? "selected" : "" ?>>Price: High to Low</option>
            <option value="name_asc"   <?= $sort == "name_asc"   ? "selected" : "" ?>>Name: A-Z</option>
        </select>

        <button type="submit" class="btn btn-primary search-btn">Apply</button>
        <?php if ($keyword || $platform || $category || $sort): ?>
            <a href="products.php" class="btn" style="background:#f3f4f6; color:#374151; margin-left:5px;">Reset</a>
        <?php endif; ?>
    </form>

    <div class="product-grid">
        <?php if (!empty($product_list)): ?>
            <?php foreach ($product_list as $row): ?>
                <?php 
                    $discount = 0;
                    if ($row['price_original'] > $row['price_current'] && $row['price_current'] > 0) {
                        $discount = round((($row['price_original'] - $row['price_current']) / $row['price_original']) * 100);
                    }
                    $pfCode = isset($row['platform_code']) ? $row['platform_code'] : '';
                ?>
                
                <div class="product-card">
                    
                    <div class="card-image-wrap">
                        <img src="<?= htmlspecialchars($row['image_url']) ?>"
                             onerror="this.src='https://placehold.co/300x300?text=No+Image';" 
                             alt="Product Image">
                        <?php if ($discount > 0): ?>
                            <span class="tag-discount">-<?= $discount ?>%</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <span class="tag-platform badge-<?= $pfCode ?>">
                            <?= htmlspecialchars($row['platform_name'] ?? "Unknown") ?>
                        </span>

                        <h3 class="product-title" title="<?= htmlspecialchars($row['title']) ?>">
                            <?= htmlspecialchars($row['title']) ?>
                        </h3>

                        <div class="price-row">
                            <?php if ($row['price_current'] > 0): ?>
                                <span class="price-current"><?= number_format($row['price_current']) ?>₫</span>
                                <?php if ($row['price_original'] > $row['price_current']): ?>
                                    <span class="price-old"><?= number_format($row['price_original']) ?>₫</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="price-current" style="color:#6b7280">Liên hệ</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="<?= htmlspecialchars($row['product_url']) ?>" target="_blank" class="btn-view">
                            View Detail
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding:50px; background:white; border-radius:8px;">
                <p style="color:#6b7280;">No products found. Try changing filters.</p>
                <a href="products.php" class="btn btn-primary" style="margin-top:10px;">Clear All Filters</a>
            </div>
        <?php endif; ?>
    </div>

</main>
</body>
</html>