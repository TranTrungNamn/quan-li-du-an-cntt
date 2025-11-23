<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Scraping Tool</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

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

        document.querySelector("#tab-compare iframe").src =
            "pages/compare.php?ids=" + e.data.ids.join(",");
    }
});
</script>

<header class="site-header">
    <div class="header-container">
        <div class="logo-area">
            <img class='logo-image' src="assets/images/logo.png" alt="Logo Website">
        </div>

        <nav class="main-nav">
            <ul class="nav-links">
                <?php if (isset($_SESSION['user_logged_in'])): ?>
                    <li class="nav-item nav-user-greeting cursor-default">
                        Hi, <?= htmlspecialchars($_SESSION['username']) ?>!
                    </li>
                    <li class="nav-item active" data-tab="tab-scraper">Scraper</li>
                    <li class="nav-item" data-tab="tab-products">Products</li>
                    <li class="nav-item" data-tab="tab-compare">Compare</li>
                    <li class="nav-item cursor-pointer" onclick="window.location='auth/logout.php'">Logout</li>
                <?php else: ?>
                    <li class="nav-item active" data-tab="tab-landing">Home</li>
                    <li class="nav-item cursor-pointer" onclick="window.location='auth/login.php'">Login</li>
                    <li class="nav-item cursor-pointer" onclick="window.location='auth/signup.php'">Sign Up</li>
                <?php endif; ?>
            </ul>
        </nav>

    </div>
</header>

<main class="main-content">

<?php if (isset($_SESSION['user_logged_in'])): ?>

    <section id="tab-scraper" class="tab-page">

        <div class="hero-section">
            <h1 class="hero-title">E-commerce Data Scraper</h1>
            <p class="hero-description">
                Enter a product category URL below to automatically extract product details.
            </p>
        </div>

        <div class="input-section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Target URL</h2>
                </div>
                <div class="card-content">
                    <form id="scrapeForm" class="scrape-form">
                        <div class="input-group">
                            <input type="url" id="urlInput" placeholder="https://example.com/products..." required class="form-input">
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
            </div>
        </div>

        <div id="resultsSection" class="results-section hidden">
            <div class="results-header">
                <h2 class="results-title">Scraped Products</h2>
                <p class="results-count">Found <span id="countValue">0</span> items</p>
            </div>
            <div id="resultsList" class="product-grid"></div>
        </div>

    </section>

    <section id="tab-products" class="tab-page hidden">
        <h2>Saved Products</h2>
        <iframe src="pages/products.php" class="iframe-page"></iframe>
    </section>

    <section id="tab-compare" class="tab-page hidden">
        <h2>Compare Products</h2>
        <iframe src="pages/compare.php" class="iframe-page"></iframe>
    </section>

<?php else: ?>

    <section id="tab-landing" class="tab-page">
        <div class="hero-section" style="margin-top: 100px;">
            <h1 class="hero-title" style="color: var(--primary);">Welcome to E-commerce Data Tool</h1>
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
