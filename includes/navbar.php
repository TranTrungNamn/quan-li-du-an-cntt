<?php
// Xác định đường dẫn cơ sở
$current_script_path = $_SERVER['SCRIPT_NAME'];
$base_path = "./";
if (strpos($current_script_path, '/admin/') !== false || strpos($current_script_path, '/auth/') !== false || strpos($current_script_path, '/pages/') !== false) {
    $base_path = "../";
}

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['user_logged_in']) || isset($_SESSION['admin_logged_in']);
?>

<header class="site-header <?= $is_logged_in ? 'dynamic-navbar' : '' ?>">
    
    <?php if ($is_logged_in): ?>
        <div class="navbar-bg-layer" id="navbarBg"></div>
        <div class="navbar-overlay"></div>
    <?php endif; ?>

    <div class="header-container">
        <div class="logo-area">
            <img class="logo-image" src="<?= $base_path ?>assets/logo/logo.png" alt="Logo Website">
            <?php if (isset($_SESSION['admin_logged_in'])): ?>
                <span style="font-weight: 700; font-size: 1.1rem; margin-left: 8px; color: #fff; text-transform: uppercase; letter-spacing: 1px;">Admin Panel</span>
            <?php endif; ?>
        </div>

        <nav class="main-nav">
            <ul class="nav-links">
                <?php if (isset($_SESSION['admin_logged_in'])): ?>
                    <li class="nav-item" onclick="window.location='<?= $base_path ?>admin/admin_dashboard.php'">Dashboard</li>
                    <li class="nav-item" onclick="window.location='<?= $base_path ?>index.php'">View Website</li>
                    <li class="nav-item btn-logout" onclick="window.location='<?= $base_path ?>admin/logout.php'">Logout</li>

                <?php elseif (isset($_SESSION['user_logged_in'])): ?>
                    <li class="nav-item nav-user-greeting cursor-default">
                        Hi, <?= htmlspecialchars($_SESSION['username']) ?>!
                    </li>
                    <li class="nav-item active" data-tab="tab-scraper" onclick="switchTab('tab-scraper')">Scraper</li>
                    <li class="nav-item" data-tab="tab-products" onclick="switchTab('tab-products')">Products</li>
                    <li class="nav-item" data-tab="tab-compare" onclick="switchTab('tab-compare')">Compare</li>
                    <li class="nav-item btn-logout" onclick="window.location='<?= $base_path ?>auth/logout.php'">Logout</li>

                <?php else: ?>
                    <li class="nav-item active" data-tab="tab-landing">Home</li>
                    <li class="nav-item" onclick="window.location='<?= $base_path ?>auth/login.php'">Login</li>
                    <li class="nav-item btn-signup" onclick="window.location='<?= $base_path ?>auth/signup.php'">Sign Up</li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<script>
function switchTab(tabId) {
    if(document.getElementById(tabId)) {
        document.querySelectorAll(".nav-item").forEach(i => i.classList.remove("active"));
        document.querySelector(`.nav-item[data-tab='${tabId}']`).classList.add("active");
        document.querySelectorAll(".tab-page").forEach(page => page.classList.add("hidden"));
        document.getElementById(tabId).classList.remove("hidden");
    } else {
        window.location.href = '<?= $base_path ?>index.php';
    }
}
</script>