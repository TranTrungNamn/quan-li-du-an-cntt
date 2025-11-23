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
$platforms   = $conn->query("SELECT id, name FROM platforms ORDER BY id ASC");
$categories  = $conn->query("SELECT DISTINCT normalized_key FROM products WHERE normalized_key IS NOT NULL AND normalized_key != '' ORDER BY normalized_key ASC");

/* ========================
   BASE SQL
   ======================== */
$sql = "SELECT p.*, pf.name AS platform_name
        FROM products p
        LEFT JOIN platforms pf ON p.platform_id = pf.id
        WHERE p.is_active = 1";

/* ========================
   BỘ LỌC
   ======================== */
if ($keyword !== "") {
    $safe = $conn->real_escape_string($keyword);
    $sql .= " AND p.title LIKE '%$safe%'";
}

if ($platform !== "") {
    $safe = intval($platform);   // FIX: platform_id là số — không dùng pf.code nữa
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
</head>

<body>
<main class="main-content">

    <h1 class="hero-title" style="font-size: 1.5rem;">Saved Products & Search</h1>
    <p class="hero-description">
        <span class="badge"><?= count($product_list) ?></span> products found.
    </p>

    <!-- FORM SEARCH -->
    <form method="GET" class="search-form-group">

        <input type="text" name="q" placeholder="Search product title..."
               value="<?= htmlspecialchars($keyword) ?>" class="form-input">

        <select name="platform">
            <option value="">All Platforms</option>
            <?php 
            // Reset pointer về đầu danh sách để dùng lại nếu cần (tùy code gốc)
            $platforms->data_seek(0); 
            while ($p = $platforms->fetch_assoc()): 
            ?>
                <option value="<?= $p['id'] ?>"
                    <?= $platform == $p['id'] ? "selected" : "" ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="category">
            <option value="">All Categories</option>
            <?php 
            $categories->data_seek(0);
            while ($c = $categories->fetch_assoc()): 
            ?>
                <option value="<?= $c['normalized_key'] ?>"
                    <?= $category == $c['normalized_key'] ? "selected" : "" ?>>
                    <?= ucwords(str_replace('-', ' ', $c['normalized_key'])) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="sort">
            <option value="">⇅ Sort By...</option>
            <option value="price_asc"  <?= $sort == "price_asc"  ? "selected" : "" ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sort == "price_desc" ? "selected" : "" ?>>Price: High to Low</option>
            <option value="name_asc"   <?= $sort == "name_asc"   ? "selected" : "" ?>>Name: A → Z</option>
            <option value="name_desc"  <?= $sort == "name_desc"  ? "selected" : "" ?>>Name: Z → A</option>
        </select>

        <button type="submit" class="btn btn-primary">
            Filter
        </button>

        <?php if ($keyword || $platform || $category || $sort): ?>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='products.php'">
                ✕ Reset
            </button>
        <?php endif; ?>
    </form>

    <!-- PRODUCT GRID -->
    <div class="product-grid">
        <?php if (!empty($product_list)): ?>
            <?php foreach ($product_list as $row): ?>
                <div class="product-card">

                    <div class="product-image-container">
                        <img src="<?= htmlspecialchars($row['image_url']) ?>"
                             onerror="this.src='https://placehold.co/300x300?text=No+Image';"
                             class="product-image">
                    </div>

                    <div class="product-info">
                        <h3 class="product-title"><?= htmlspecialchars($row['title']) ?></h3>

                        <div class="price-box">
                            <span class="current-price"><?= number_format($row['price_current']) ?>₫</span>

                            <?php if ($row['price_original']): ?>
                                <span class="old-price"><?= number_format($row['price_original']) ?>₫</span>
                            <?php endif; ?>
                        </div>

                        <p class="small-text">
                            Platform: <?= htmlspecialchars($row['platform_name'] ?? "Unknown") ?>
                        </p>
                    </div>

                    <a href="<?= htmlspecialchars($row['product_url']) ?>" 
                       target="_blank" class="view-btn">
                        View Product
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="product-grid-empty">No products found matching your criteria.</p>
        <?php endif; ?>
    </div>

</main>
</body>
</html>
