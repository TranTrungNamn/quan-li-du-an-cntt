<?php

require_once __DIR__ . "/common.php";

function scrape_ytesonhuong_list($url)
{
    // 1. Dùng fetch_html từ common.php (giống sieuthiyte) để xử lý header/gzip tốt hơn
    try {
        $html = fetch_html($url);
    } catch (Exception $e) {
        return [];
    }

    if (!$html) return [];

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    
    // [FIX QUAN TRỌNG] Thay thế mb_convert_encoding bị lỗi
    // Thêm thẻ meta charset utf-8 vào đầu chuỗi HTML để DOMDocument hiểu đúng encoding
    @$doc->loadHTML('<meta charset="utf-8">' . $html);
    
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);

    // === SELECTOR CHUẨN ===
    // (Giữ nguyên logic selector của bạn vì nó đã lấy được dữ liệu trong log)
    $items = $xpath->query('//div[contains(@class, "product-wrapper")]');

    $data = [];

    foreach ($items as $item) {

        // --- Title + Link ---
        $a = $xpath->query('.//a', $item)->item(0);

        $title = $a ? trim($a->textContent) : "";
        $link  = $a ? $a->getAttribute("href") : "";

        if ($link && strpos($link, "http") !== 0) {
            $link = "https://www.ytesonhuong.com" . $link;
        }

        // --- Image ---
        $imgNode = $xpath->query('.//img', $item)->item(0);

        $img = "";
        if ($imgNode) {
            // Ưu tiên data-src cho lazyload
            if ($imgNode->getAttribute("data-src"))
                $img = $imgNode->getAttribute("data-src");
            elseif ($imgNode->getAttribute("src"))
                $img = $imgNode->getAttribute("src");

            if ($img && strpos($img, "http") !== 0)
                $img = "https:" . $img;
        }

        // --- Price ---
        $priceNode = $xpath->query('.//div[contains(@class,"price-info")]//span', $item)->item(0);
        // Dùng to_int_price từ common.php cho đồng bộ
        $price = to_int_price($priceNode ? $priceNode->textContent : "");

        $data[] = [
            "title" => $title,
            "link" => $link,
            "image" => $img,
            "price_new" => $price,
            "price_old" => 0,
            "rating" => 0,
            "rating_count" => 0,
            "sold" => 0
        ];
    }

    return $data;
}

?>