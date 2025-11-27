<?php
require_once __DIR__ . "/../api/db.php";

/* ========================
   1. LOGIC PHP (GIỮ NGUYÊN)
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
    <style>
        /* ========================
           NEW CARD DESIGN (FIXED LAYOUT)
           ======================== */
        
        body {
            background-color: #f3f4f6;
            font-family: 'Inter', sans-serif;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            padding-bottom: 40px;
        }

        /* 1. THẺ CHÍNH: CẤU TRÚC FLEX DỌC */
        .product-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column; /* Xếp dọc các phần */
            height: 100%; /* Chiếm hết chiều cao ô grid */
            transition: box-shadow 0.3s ease, transform 0.2s;
            position: relative;
        }

        .product-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
            border-color: #d1d5db;
        }

        /* 2. PHẦN ẢNH (HEADER) */
        .card-image-wrap {
            height: 180px; /* Chiều cao cố định */
            width: 100%;
            padding: 15px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #f3f4f6;
            position: relative;
        }

        .card-image-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain; /* Ảnh tự co giãn không méo */
        }

        .tag-discount {
            position: absolute;
            top: 10px; right: 10px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 4px;
        }

        /* 3. PHẦN NỘI DUNG (BODY): Tự giãn nở (flex-grow: 1) */
        .card-body {
            padding: 15px;
            flex-grow: 1; /* Đẩy phần Footer xuống đáy */
            display: flex;
            flex-direction: column;
        }

        .tag-platform {
            display: inline-block;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            color: #6b7280;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            margin-bottom: 8px;
            width: fit-content;
        }

        .product-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 10px 0;
            line-height: 1.4;
            /* Giới hạn cứng 2 dòng */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px; /* 14px * 1.4 * 2 dòng ~ 40px */
        }

        .price-row {
            margin-top: auto; /* Đẩy giá xuống sát đáy của Body */
        }

        .price-current {
            font-size: 16px;
            font-weight: 700;
            color: #db2777;
        }
        .price-old {
            font-size: 12px;
            color: #9ca3af;
            text-decoration: line-through;
            margin-left: 6px;
        }

        /* 4. PHẦN CHÂN (FOOTER): NÚT BẤM RIÊNG BIỆT */
        .card-footer {
            padding: 12px 15px;
            background: #f9fafb;
            border-top: 1px solid #f3f4f6;
        }

        .btn-view {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 10px 0;
            background: #1f2937; /* Màu đen/xám đậm */
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .btn-view:hover {
            background: #374151;
        }

        /* Badge màu sắc riêng */
        .badge-sieuthiyte { color: #0ea5e9; background: #e0f2fe; }
        .badge-phana { color: #10b981; background: #d1fae5; }
        .badge-ytesonhuong { color: #f59e0b; background: #fef3c7; }

    </style>
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