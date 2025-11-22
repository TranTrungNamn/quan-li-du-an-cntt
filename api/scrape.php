<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.php";

// 1. Get URL
if (!isset($_GET['url']) || empty($_GET['url'])) {
    echo json_encode(["error" => "Missing URL"]);
    exit;
}

$url = trim($_GET['url']);
$domain = parse_url($url, PHP_URL_HOST);

// 1.1 Check supported domains
if (
    !str_contains($url, "sieuthiyte.com.vn") &&
    !str_contains($url, "phana.com.vn") &&
    !str_contains($url, "ytesonhuong.com")
) {
    echo json_encode(["error" => "Only sieuthiyte, phana, ytesonhuong are supported"]);
    exit;
}

// 2. Platform detect
if (str_contains($url, "sieuthiyte.com.vn")) {
    $platform_code = "sieuthiyte";
    require_once __DIR__ . '/../scraper/sieuthiyte_scraper.php';
    $data = scrape_sieuthiyte_list($url);
}
elseif (str_contains($url, "phana.com.vn")) {
    $platform_code = "phana";
    require_once __DIR__ . '/../scraper/phana_scraper.php';
    $data = scrape_phana_page($url);
}
elseif (str_contains($url, "ytesonhuong.com")) {
    $platform_code = "ytesonhuong";
    require_once __DIR__ . '/../scraper/ytesonhuong_scraper.php';
    $data = scrape_ytesonhuong_list($url);
}

// 3. Get platform_id
$stmt = $conn->prepare("SELECT id FROM platforms WHERE code = ?");
$stmt->bind_param("s", $platform_code);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($platform_id);
    $stmt->fetch();
} else {
    $ins = $conn->prepare("INSERT INTO platforms (code, name) VALUES (?, ?)");
    $ins->bind_param("ss", $platform_code, $domain);
    $ins->execute();
    $platform_id = $ins->insert_id;
    $ins->close();
}
$stmt->close();

// 4. Normalize key
function normalize_key($str) {
    return preg_replace('/[^a-z0-9]+/', '-', strtolower($str));
}

// 5. Save products
$sql = "
INSERT INTO products
(platform_id, title, normalized_key, product_url, image_url, shop_name,
 price_current, price_original, sold_quantity, rating_avg, rating_count,
 last_scraped_at)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
ON DUPLICATE KEY UPDATE
 price_current = VALUES(price_current),
 price_original = VALUES(price_original),
 sold_quantity = VALUES(sold_quantity),
 rating_avg = VALUES(rating_avg),
 rating_count = VALUES(rating_count),
 updated_at = NOW(),
 last_scraped_at = NOW()
";

$stmt_product = $conn->prepare($sql);

foreach ($data as $item) {
    $title  = $item['title'] ?? '';
    $norm   = normalize_key($title);

    $url2   = $item['link'] ?? '';
    $img    = $item['image'] ?? '';
    $shop   = $item['shop'] ?? null;

    $p_new  = $item['price_current'] ?? 0;
    $p_old  = $item['price_original'] ?? 0;
    $sold   = $item['sold_quantity'] ?? 0;
    $rate   = $item['rating_avg'] ?? 0;
    $count  = $item['rating_count'] ?? 0;

    $stmt_product->bind_param(
        "isssssiiiii",
        $platform_id,
        $title,
        $norm,
        $url2,
        $img,
        $shop,
        $p_new,
        $p_old,
        $sold,
        $rate,
        $count
    );

    $stmt_product->execute();
}

$stmt_product->close();

echo json_encode($data, JSON_UNESCAPED_UNICODE);
