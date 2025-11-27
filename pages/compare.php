<?php
require_once __DIR__ . "/../api/db.php";

// --- Logic lọc giống hệt Products.php ---
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
    <title>Select to Compare</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body class="iframe-page-body">

<main class="main-content">

    <h1 class="hero-title">Compare Products</h1>
    <p class="hero-description">
        Select exactly <strong>2 items</strong> to compare.
    </p>

    <form method="GET" class="search-bar-container">
        <input type="text" name="q" placeholder="Search..." value="<?= htmlspecialchars($keyword) ?>" class="search-input">

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

        <button type="submit" class="btn btn-primary search-btn">Filter</button>
        <?php if ($keyword || $platform || $category): ?>
            <a href="compare.php" class="btn" style="background:#f3f4f6; color:#374151; margin-left:5px;">Reset</a>
        <?php endif; ?>
    </form>

    <div class="product-grid">
        <?php if (!empty($product_list)): ?>
            <?php foreach ($product_list as $row): ?>
                <?php 
                    // Tính discount
                    $discount = 0;
                    if ($row['price_original'] > $row['price_current'] && $row['price_current'] > 0) {
                        $discount = round((($row['price_original'] - $row['price_current']) / $row['price_original']) * 100);
                    }
                    $pfCode = isset($row['platform_code']) ? $row['platform_code'] : '';
                ?>
                
                <div class="product-card">
                    
                    <div class="select-overlay">
                        <input type="checkbox" class="cmp-checkbox" value="<?= $row['id'] ?>">
                    </div>

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
                                <span class="price-current text-contact">Contact</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column:1/-1; text-align:center; color:#666;">No products found.</p>
        <?php endif; ?>
    </div>

</main>

<div id="compareBar">
    <span>Selected: <strong id="countSelected">0</strong>/2</span>
    <a href="#" id="btnGoCompare" class="btn-compare-go">Compare Now →</a>
    <button onclick="clearSelection()" style="background:none; border:none; color:#ccc; cursor:pointer; font-size:0.8rem; text-decoration:underline;">Clear</button>
</div>

<script>
    const checkboxes = document.querySelectorAll('.cmp-checkbox');
    const bar = document.getElementById('compareBar');
    const countSpan = document.getElementById('countSelected');
    const btnGo = document.getElementById('btnGoCompare');
    
    let selectedIds = [];

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const id = this.value;

            if (this.checked) {
                if (selectedIds.length >= 2) {
                    alert("You can only compare 2 items at a time.");
                    this.checked = false;
                    return;
                }
                selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(item => item !== id);
            }
            updateUI();
        });
    });

    function updateUI() {
        countSpan.innerText = selectedIds.length;
        
        if (selectedIds.length > 0) {
            bar.style.display = "flex";
        } else {
            bar.style.display = "none";
        }

        if (selectedIds.length === 2) {
            btnGo.href = `compare_view.php?id1=${selectedIds[0]}&id2=${selectedIds[1]}`;
            btnGo.style.pointerEvents = "auto";
            btnGo.style.opacity = "1";
            btnGo.innerHTML = "Compare Now →";
        } else {
            btnGo.removeAttribute("href");
            btnGo.style.pointerEvents = "none";
            btnGo.style.opacity = "0.6";
            btnGo.innerHTML = "Pick 2 items";
        }
    }

    function clearSelection() {
        selectedIds = [];
        checkboxes.forEach(cb => cb.checked = false);
        updateUI();
    }
</script>

</body>
</html>