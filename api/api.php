<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.php";

// ===============================
// 1. Lấy URL
// ===============================
if (!isset($_GET['url']) || empty($_GET['url'])) {
    echo json_encode(["error" => "Missing URL"]);
    exit;
}

$url = trim($_GET['url']);
$escapedUrl = escapeshellarg($url);
$domain = parse_url($url, PHP_URL_HOST);

// ===============================
// 2. Xác định đường dẫn scraper + python
// ===============================
$scraperDir = realpath(__DIR__ . '/../scraper');
if (!$scraperDir) {
    echo json_encode(["error" => "Scraper folder not found"]);
    exit;
}

// 🔥 Python.exe của bạn (Python Launcher)
$python = "python";

$cmd = null;

if (strpos($domain, "tiki.vn") !== false) {
    $cmd = "\"$python\" \"$scraperDir/scraper_tiki.py\" $escapedUrl";
}
elseif (strpos($domain, "shopee") !== false) {
    $cmd = "\"$python\" \"$scraperDir/scraper_shopee.py\" $escapedUrl";
}
elseif (strpos($domain, "sieuthiyte") !== false) {
    // Dùng scraper.py (Selenium) thay vì scraper_beauti.py (Requests)
    $cmd = "\"$python\" \"$scraperDir/scraper.py\" $escapedUrl";
}
else {
    // fallback beauti
    $cmd = "\"$python\" \"$scraperDir/scraper_beauti.py\" $escapedUrl";
}


// ===============================
// 3. Gọi Python
// ===============================
$output = shell_exec($cmd . " 2>&1");   // lấy cả stderr

if (!$output) {
    echo json_encode([
        "error" => "Python execution failed",
        "cmd"   => $cmd,
        "note"  => "Check Python path or scraper script"
    ]);
    exit;
}

// Giải mã JSON
$data = json_decode($output, true);

if (!is_array($data)) {
    echo json_encode([
        "error" => "Invalid JSON returned from Python",
        "raw"   => $output,
        "cmd"   => $cmd
    ]);
    exit;
}

// ===============================
// 4. Mapping platform
// ===============================
$platform_code = "other";
if (strpos($domain, "tiki") !== false)          $platform_code = "tiki";
elseif (strpos($domain, "shopee") !== false)     $platform_code = "shopee";
elseif (strpos($domain, "sieuthiyte") !== false) $platform_code = "sieuthiyte";

// Kiểm tra hoặc thêm platform
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
}
$stmt->close();

// ===============================
// 5. Normalize key
// ===============================
function normalize_key($str) {
    return preg_replace('/[^a-z0-9]+/', '-', strtolower($str));
}

// ===============================
// 6. SQL insert/update
// ===============================
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

// ===============================
// 7. Lưu từng item
// ===============================
foreach ($data as $item) {

    $title  = $item['title'] ?? '';
    $norm   = normalize_key($title);
    $url2   = $item['link'] ?? '';
    $img    = $item['image'] ?? '';
    $shop   = $item['shop'] ?? null;

    $p_new  = $item['price_new'] ?? null;
    $p_old  = $item['price_old'] ?? null;
    $sold   = $item['sold'] ?? null;
    $rate   = $item['rating'] ?? null;
    $count  = $item['rating_count'] ?? null;

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

    // lấy ID
    $product_id = $conn->insert_id;
    if (!$product_id) {
        $q = $conn->query("SELECT id FROM products WHERE product_url='$url2' AND platform_id=$platform_id");
        $product_id = $q->fetch_assoc()['id'] ?? null;
    }

    if ($product_id) {
        $conn->query("
            INSERT INTO product_snapshots
            (product_id, price_current, price_original, sold_quantity, rating_avg, rating_count)
            VALUES (
                $product_id,
                " . ($p_new  === null ? "NULL" : intval($p_new)) . ",
                " . ($p_old  === null ? "NULL" : intval($p_old)) . ",
                " . ($sold   === null ? "NULL" : intval($sold)) . ",
                " . ($rate   === null ? "NULL" : floatval($rate)) . ",
                " . ($count  === null ? "NULL" : intval($count)) . "
            )
        ");
    }
}

// ===============================
// 8. Trả JSON cho FE
// ===============================
echo json_encode($data, JSON_UNESCAPED_UNICODE);

?>
