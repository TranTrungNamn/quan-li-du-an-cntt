<?php

function scrape_ytesonhuong_list($url)
{
    // --- dùng cURL để tránh bị chặn ---
    $html = curl_get($url);
    if (!$html) return [];

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);

    // === SELECTOR CHUẨN ===
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
            if ($imgNode->getAttribute("data-src"))
                $img = $imgNode->getAttribute("data-src");
            elseif ($imgNode->getAttribute("src"))
                $img = $imgNode->getAttribute("src");

            if ($img && strpos($img, "http") !== 0)
                $img = "https:" . $img;
        }

        // --- Price ---
        $priceNode = $xpath->query('.//div[contains(@class,"price-info")]//span', $item)->item(0);
        $price = $priceNode ? trim($priceNode->textContent) : "0";

        $price = (int)preg_replace('/[^0-9]/', '', $price);

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


// -----------------------
// CURL ANTI-BLOCK
// -----------------------
function curl_get($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_USERAGENT,
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36");

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}
