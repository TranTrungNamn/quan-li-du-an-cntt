<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Scraping</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<?php include 'includes/navbar.php'; ?>

<body class="<?= !isset($_SESSION['user_logged_in']) ? 'random-background' : '' ?>">

<script>
// Nhận tín hiệu từ tab Scraper → bật tab Products + reload iframe
window.addEventListener("message", function(e) {
    if (e.data.action === "refreshProducts") {
        // Đổi tab
        document.querySelectorAll(".nav-item").forEach(n => n.classList.remove("active"));
        document.querySelector(".nav-item[data-tab='tab-products']").classList.add("active");

        document.querySelectorAll(".tab-page").forEach(page => page.classList.add("hidden"));
        document.getElementById("tab-products").classList.remove("hidden");

        // Reload iframe
        const iframe = document.querySelector("#tab-products iframe");
        iframe.src = iframe.src;
    }

    if (e.data.action === "openCompare") {
        document.querySelectorAll(".nav-item").forEach(n => n.classList.remove("active"));
        document.querySelector(".nav-item[data-tab='tab-compare']").classList.add("active");

        document.querySelectorAll(".tab-page").forEach(page => page.classList.add("hidden"));
        document.getElementById("tab-compare").classList.remove("hidden");

        document.querySelector("#tab-compare iframe").src = "pages/compare.php?ids=" + e.data.ids.join(",");
    }
});
</script>

<main class="main-content">

<?php if (isset($_SESSION['user_logged_in'])): ?>

    <section id="tab-scraper" class="tab-page">
        <div class="hero-section">
            <h1 class="hero-title">E-commerce Data Scraper</h1>
            <p class="hero-description">
                Extract product data automatically from supported platforms.
            </p>
        </div>

        <div class="input-section">
            <div class="card">
                <div class="card-content">
                    
                    <div class="scraper-ui-layout">
                        
                        <div class="scraper-suggestions">
                            <div class="suggestion-header">
                                <span style="font-size: 1.2rem;">💡</span>
                                <h3>Supported Sites</h3>
                            </div>
                            <p class="suggestion-desc">
                                Paste a category URL from one of these medical e-commerce platforms to extract data instantly:
                            </p>
                            <div class="platform-list">
                                <span class="tag-platform badge-sieuthiyte">SieuThiYTe</span>
                                <span class="tag-platform badge-phana">Phana</span>
                                <span class="tag-platform badge-ytesonhuong">YTeSonHuong</span>
                            </div>
                        </div>

                        <div class="scraper-input-area">
                            <form id="scrapeForm" class="scrape-form">
                                <div class="input-group">
                                    <input type="url" id="urlInput" placeholder="Paste target URL here..." required>
                                    <button type="submit" id="scrapeBtn" class="btn btn-primary">
                                        <span class="btn-text">Start Scraping</span>
                                        <span class="spinner hidden"></span>
                                    </button>
                                </div>

                                <a id="siteInfo" href="#" target="_blank" class="site-info hidden">
                                    <img id="siteFavicon" src="" alt="Logo" class="site-favicon">
                                    <span id="siteDomain" class="site-domain"></span>
                                </a>

                                <p id="errorMessage" class="error-text hidden"></p>
                            </form>
                        </div>

                    </div> </div>
            </div>
        </div>

        <div id="resultsSection" class="results-section hidden">
            <div class="results-header">
                <h2 class="results-title">Scraped Results</h2>
                <p class="results-count">Found <span id="countValue">0</span> items</p>
            </div>
            <div id="resultsList" class="product-grid"></div>
        </div>
    </section>

    <section id="tab-products" class="tab-page hidden">
        <iframe src="pages/products.php" class="iframe-page"></iframe>
    </section>

    <section id="tab-compare" class="tab-page hidden">
        <iframe src="pages/compare.php" class="iframe-page"></iframe>
    </section>

<?php else: ?>

    <section id="tab-landing" class="tab-page">
        <div class="hero-section" style="margin-top: 100px;">
            <h1 class="hero-title">Welcome to E-commerce Data Tool</h1>
            <p class="hero-description" style="font-size: 1.25rem; margin-bottom: 30px;">
                Please Login or Sign Up to access the tools.
            </p>
        </div>
    </section>

<?php endif; ?>

</main>

<script src="script.js"></script>

<script>
document.querySelectorAll(".nav-item[data-tab]").forEach(btn => {
    btn.addEventListener("click", () => {
        document.querySelectorAll(".nav-item").forEach(i => i.classList.remove("active"));
        btn.classList.add("active");

        const tab = btn.getAttribute("data-tab");

        document.querySelectorAll(".tab-page").forEach(page => {
            page.classList.add("hidden");
        });

        document.getElementById(tab).classList.remove("hidden");
    });
});
</script>

</body>
</html>