<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.php";

// Load scrapers
require_once __DIR__ . '/../scraper/sieuthiyte_scraper.php';
require_once __DIR__ . '/../scraper/phana_scraper.php';
require_once __DIR__ . '/../scraper/ytesonhuong_scraper.php';

// ====================================================================
// 1. GET URL
// ====================================================================
if (!isset($_GET['url']) || empty($_GET['url'])) {
    echo json_encode(["error" => "Missing URL"]);
    exit;
}

$url = trim($_GET['url']);
$domain = parse_url($url, PHP_URL_HOST);

// ====================================================================
// 2. DETECT SCRAPER BY DOMAIN
// ====================================================================
$data = null;
$platform_code = null;

try {

    // SieuThiYTe
    if (strpos($domain, "sieuthiyte") !== false) {
        $data = scrape_sieuthiyte_list($url);
        $platform_code = "sieuthiyte";
    }

    // Phana
    elseif (strpos($domain, "phana.com.vn") !== false) {
        $data = scrape_phana_list($url);
        $platform_code = "phana";
    }

    // Y Te Son Huong
    elseif (strpos($domain, "ytesonhuong.com") !== false) {
        $data = scrape_ytesonhuong_list($url);
        $platform_code = "ytesonhuong";
    }

    // Unsupported
    else {
        echo json_encode([
            "error" => "Website not supported",
            "supported" => [
                "sieuthiyte.com.vn",
                "phana.com.vn",
                "ytesonhuong.com"
            ]
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}


// ====================================================================
// 3. GET OR CREATE PLATFORM ID
// ====================================================================
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


// ====================================================================
// 4. NORMALIZE KEY
// ====================================================================
function normalize_key($str) {
    return preg_replace('/[^a-z0-9]+/', '-', strtolower($str));
}


// ====================================================================
// 5. SQL INSERT / UPDATE
// ====================================================================
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


// ====================================================================
// 6. INSERT DATA TO DATABASE
// ====================================================================
foreach ($data as $item) {

    $title  = $item['title'] ?? '';
    $norm   = normalize_key($title);
    $url2   = $item['link'] ?? '';
    $img    = $item['image'] ?? '';
    $shop   = $item['shop'] ?? null;

    // Mapping đồng bộ tất cả scraper
    $p_new  = $item['price_current']  ?? $item['price_new']  ?? 0;
    $p_old  = $item['price_original'] ?? $item['price_old'] ?? 0;
    $sold   = $item['sold_quantity']  ?? $item['sold']      ?? 0;
    $rate   = $item['rating_avg']     ?? $item['rating']    ?? 0;
    $count  = $item['rating_count']   ?? 0;

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


// ====================================================================
// 7. RETURN JSON RESPONSE
// ====================================================================
echo json_encode($data, JSON_UNESCAPED_UNICODE);

?>
