<?php
session_start();
require_once "../api/db.php";

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../style.css?v=<?= time() ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
    <div class="header-container">
        <div class="logo-area">
            <img class="logo-image" src="../assets/logo/logo.png" alt="Logo">
            <span style="font-weight: 600; font-size: 1.1rem; margin-left: 5px;">Admin Panel</span>
        </div>

        <nav class="main-nav">
            <ul class="nav-links">
                <li class="nav-item active">Dashboard</li>
                <li class="nav-item" onclick="window.location='../index.php'">View Website</li>
                <li class="nav-item" onclick="window.location='logout.php'" style="color: var(--error);">Logout</li>
            </ul>
        </nav>
    </div>
</header>

<div class="container mt-20">

    <div class="card mb-20" style="border: 1px solid #2563eb;">
        <div class="card-header" style="background: #eff6ff; display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="color: #1e40af;">Scrape Products</h3>
            <span style="font-size: 0.85rem; color: #64748b;">Supported: SieuThiYTe, Phana, YTeSonHuong</span>
        </div>
        <div class="card-content">
            <form id="scrapeForm">
                <div class="input-group" style="display: flex; gap: 10px;">
                    <input type="url" id="urlInput" placeholder="Paste category URL here (e.g. https://sieuthiyte.com.vn/may-do-huyet-ap)..." required style="flex: 1;">
                    <button type="submit" id="scrapeBtn" class="btn btn-primary">
                        <span class="btn-text">Start Scraping</span>
                        <span class="spinner hidden"></span>
                    </button>
                </div>

                <a id="siteInfo" href="#" target="_blank" class="site-info hidden" style="margin-top: 15px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #333; background: #f1f5f9; padding: 5px 10px; border-radius: 4px;">
                    <img id="siteFavicon" src="" alt="" width="16" height="16">
                    <span id="siteDomain" style="font-weight: 500;"></span>
                </a>

                <p id="errorMessage" class="text-danger mt-20 hidden"></p>
            </form>

            <div id="resultsSection" class="hidden" style="margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                    <h4 style="margin: 0;">Scraped Result Preview</h4>
                    <span style="background: #2563eb; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">
                        Found <span id="countValue">0</span> items
                    </span>
                </div>
                <div id="resultsList" class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));"></div>
                
                <div class="text-center mt-20">
                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 10px;">Data has been saved to database.</p>
                    <button onclick="window.location.reload()" class="btn btn-primary" style="background: #10b981;">
                        Refresh Page to Manage
                    </button>
                </div>
            </div>
        </div>
    </div>
    <h3 style="margin-bottom: 20px; font-size: 1.5rem;">Product Manager</h3>

    <div style="background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden;">
        <table class="product-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <th style="padding: 12px 16px; text-align: left; width: 60px;">ID</th>
                    <th style="padding: 12px 16px; text-align: left; width: 80px;">Image</th>
                    <th style="padding: 12px 16px; text-align: left;">Title</th>
                    <th style="padding: 12px 16px; text-align: left; width: 120px;">Price</th>
                    <th style="padding: 12px 16px; text-align: left; width: 150px;">Shop</th>
                    <th style="padding: 12px 16px; text-align: right; width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $products->fetch_assoc()) { ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 12px 16px; color: #6b7280;">#<?= $p['id'] ?></td>
                    <td style="padding: 12px 16px;">
                        <img src="<?= htmlspecialchars($p['image_url']) ?>" 
                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; display: block;"
                             onerror="this.src='https://placehold.co/50x50?text=No+Img'">
                    </td>
                    <td style="padding: 12px 16px;">
                        <a href="<?= htmlspecialchars($p['product_url']) ?>" target="_blank" style="color: #111827; text-decoration: none; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= htmlspecialchars($p['title']) ?>
                        </a>
                    </td>
                    <td style="padding: 12px 16px; font-weight: 600; color: #d32f2f;">
                        <?= ($p['price_current'] > 0) ? number_format($p['price_current']) . ' đ' : 'Liên hệ' ?>
                    </td>
                    <td style="padding: 12px 16px; color: #4b5563;">
                        <?= htmlspecialchars($p['shop_name'] ?? 'N/A') ?>
                    </td>
                    <td style="padding: 12px 16px; text-align: right;">
                        <a href="edit.php?id=<?= $p['id'] ?>" class="btn-edit" style="font-size: 0.85rem;">Edit</a>
                        <a href="delete.php?id=<?= $p['id'] ?>" class="btn-delete" style="font-size: 0.85rem;" onclick="return confirm('Delete product?');">Delete</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    // Vì file script.js nằm ở thư mục gốc, mà file này ở trong /admin/
    // nên đường dẫn gọi API phải lùi ra 1 cấp: ../api/api.php
    window.API_BASE_URL = '../api/api.php';
</script>

<script src="../script.js"></script>

</body>
</html>