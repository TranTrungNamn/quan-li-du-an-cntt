<?php
require_once "../api/db.php";

$keyword = isset($_GET['q']) ? trim($_GET['q']) : "";
$sort = $_GET['sort'] ?? "";
$platform = $_GET['platform'] ?? "";
$category = $_GET['category'] ?? "";

// Lấy danh sách nền tảng
$platforms = $conn->query("SELECT DISTINCT code FROM platforms");
// Lấy danh sách normalized_key (dùng làm category tạm)
$categories = $conn->query("SELECT DISTINCT normalized_key FROM products");

// Base SQL
$sql = "SELECT * FROM products WHERE 1";

if ($keyword !== "") {
    $keyword_safe = $conn->real_escape_string($keyword);
    $sql .= " AND title LIKE '%$keyword_safe%'";
}
if ($platform !== "") {
    $platform_safe = $conn->real_escape_string($platform);
    $sql .= " AND platform_id = (SELECT id FROM platforms WHERE code='$platform_safe')";
}
if ($category !== "") {
    $category_safe = $conn->real_escape_string($category);
    $sql .= " AND normalized_key='$category_safe'";
}

// Sort
if ($sort === "price_asc") $sql .= " ORDER BY price_current ASC";
elseif ($sort === "price_desc") $sql .= " ORDER BY price_current DESC";
elseif ($sort === "name_asc") $sql .= " ORDER BY title ASC";
elseif ($sort === "name_desc") $sql .= " ORDER BY title DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<div class="container mt-20">
    <h1 class="hero-title" style="font-size: 2rem; margin-bottom:20px;">Search & Filter Products</h1>

    <form method="GET" class="search-bar-container">
        <input type="text" name="q" placeholder="Search..." 
               value="<?= htmlspecialchars($keyword) ?>" class="search-input">

        <select name="platform" class="filter-select">
            <option value="">All Platforms</option>
            <?php while ($p = $platforms->fetch_assoc()): ?>
                <option value="<?= $p['code'] ?>" <?= $platform == $p['code'] ? "selected" : "" ?>>
                    <?= strtoupper($p['code']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="category" class="filter-select">
            <option value="">All Categories</option>
            <?php while ($c = $categories->fetch_assoc()): ?>
                <option value="<?= $c['normalized_key'] ?>" <?= $category == $c['normalized_key'] ? "selected" : "" ?>>
                    <?= $c['normalized_key'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="sort" class="filter-select">
            <option value="">No Sort</option>
            <option value="price_asc" <?= $sort=="price_asc" ? "selected" : "" ?>>Price ↑</option>
            <option value="price_desc" <?= $sort=="price_desc" ? "selected" : "" ?>>Price ↓</option>
            <option value="name_asc" <?= $sort=="name_asc" ? "selected" : "" ?>>Name A → Z</option>
            <option value="name_desc" <?= $sort=="name_desc" ? "selected" : "" ?>>Name Z → A</option>
        </select>

        <button type="submit" class="btn btn-primary search-btn">Filter</button>
    </form>

    <div class="product-list">
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="product-list-row">
                
                <img src="<?= $row['image_url'] ?: 'https://via.placeholder.com/120?text=No+Image' ?>"
                     class="product-thumb">

                <div class="product-details">
                    <strong><?= $row['title'] ?></strong>
                    <span class="product-price">
                        <?= ($row['price_current'] > 0) ? number_format($row['price_current']) . 'đ' : 'Liên hệ' ?>
                    </span>
                    <small class="product-meta">Platform ID: <?= $row['platform_id'] ?></small><br>
                    <a href="<?= $row['product_url'] ?>" target="_blank" style="color:var(--link-color);">View on Site</a>
                </div>

                <div>
                    <label style="display:flex; align-items:center; cursor:pointer;">
                        <input type="checkbox" class="cmp-box" value="<?= $row['id'] ?>" style="width:auto; margin-right:8px;">
                        <span>Compare</span>
                    </label>
                </div>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-center text-muted">No products found.</p>
    <?php endif; ?>
    </div>
</div>

<div id="compareBar">
    <span id="cmpCount">0</span> selected —
    <a id="cmpBtn" href="#">Compare now</a>
</div>

<script>
    let selected = [];
    document.querySelectorAll(".cmp-box").forEach(box => {
        box.addEventListener("change", function() {
            let id = this.value;
            if (this.checked) {
                if (!selected.includes(id)) selected.push(id);
            } else {
                selected = selected.filter(x => x != id);
            }
            updateCompareBar();
        });
    });

    function updateCompareBar() {
        const bar = document.getElementById("compareBar");
        const count = document.getElementById("cmpCount");
        const btn = document.getElementById("cmpBtn");

        if (selected.length === 0) {
            bar.style.display = "none";
            return;
        }
        if (selected.length > 4) {
            alert("Maximum 4 products allowed!");
            selected.pop();
        }
        bar.style.display = "block";
        count.textContent = selected.length;
        btn.href = "../pages/compare.php?ids=" + selected.join(",");
    }
</script>
</body>
</html>