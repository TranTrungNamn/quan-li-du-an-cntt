<?php

require_once __DIR__ . "/common.php";

/**
 * Extract product list from ONE page
 */
function scrape_phana_page(string $url): array
{
    $html = fetch_html($url);

    $dom = new DOMDocument();
    @$dom->loadHTML('<meta charset="utf-8">' . $html);

    $xpath = new DOMXPath($dom);

    // PRODUCT ITEM
    $items = $xpath->query('//form[contains(@class, "item_product_main")]');

    $products = [];

    foreach ($items as $item) {

        // -----------------------------
        // 1. TITLE + LINK
        // -----------------------------
        $titleNode = $xpath->query('.//h3[contains(@class,"product-name")]/a', $item)->item(0);
        if (!$titleNode) continue;

        $title = trim($titleNode->textContent);
        $link  = absolute_url("<https:clear></https:clear>//phana.com.vn/", $titleNode->getAttribute("href"));

        // -----------------------------
// 2. IMAGE — FULL LAZYLOAD FIX
// -----------------------------
$imgNode = $xpath->query(
    './/div[contains(@class,"image_thumb")]//img | .//picture//img',
    $item
)->item(0);

$image = "";

if ($imgNode) {

    // Ưu tiên data-src → data-srcset → srcset → src
    $attrs = [
        "data-src",
        "data-srcset",
        "srcset",
        "src"
    ];

    foreach ($attrs as $attr) {
        $val = $imgNode->getAttribute($attr);
        if ($val) {

            // Nếu dạng "url 500w"
            if (strpos($val, " ") !== false) {
                $val = trim(explode(" ", $val)[0]);
            }

            // Nếu URL bắt đầu bằng "//" → thêm https:
            if (strpos($val, "//") === 0) {
                $val = "https:" . $val;
            }

            // Nếu URL tương đối → ghép với domain
            elseif (!str_starts_with($val, "http")) {
                $val = "https://phana.com.vn/" . ltrim($val, "/");
            }

            $image = $val;
            break;
        }
    }
}

        // -----------------------------
        // 3. PRICE NEW
        // -----------------------------
        $priceNewNode = $xpath->query(
            './/div[contains(@class,"price-box")]//span[contains(@class,"price")]',
            $item
        )->item(0);

        $price_new = to_int_price($priceNewNode ? $priceNewNode->textContent : "");

        // -----------------------------
        // 4. PRICE OLD
        // -----------------------------
        $priceOldNode = $xpath->query(
            './/div[contains(@class,"price-box")]//span[contains(@class,"old-price")]',
            $item
        )->item(0);

        $price_old = to_int_price($priceOldNode ? $priceOldNode->textContent : "");

        // -----------------------------
        // 5. DISCOUNT (Sale %)
        // -----------------------------
        $discountNode = $xpath->query(
            './/span[contains(@class,"sale") or contains(@class,"discount")]',
            $item
        )->item(0);

        $discount = $discountNode ? trim($discountNode->textContent) : "";

        // -----------------------------
        // PUSH RESULT
        // -----------------------------
        $products[] = [
            "title"     => $title,
            "image"     => $image,
            "link"      => $link,
            "price_new" => $price_new,
            "price_old" => $price_old,
            "discount"  => $discount
        ];
    }

    // -----------------------------
    // NEXT PAGE
    // -----------------------------
    $next = null;

    $nextNode = $xpath->query(
        '//a[@rel="next" or contains(text(),"Sau") or contains(text(),"Next")]'
    )->item(0);

    if ($nextNode) {
        $href = $nextNode->getAttribute("href");
        if ($href)
            $next = absolute_url($url, $href);
    }

    return [$products, $next];
}


/**
 * Crawl multiple pages
 */
function scrape_phana_list(string $url, int $maxPages = 1): array
{
    $all = [];
    $visited = [];
    $current = $url;
    $page = 1;

    while ($current && $page <= $maxPages) {

        if (in_array($current, $visited)) break;
        $visited[] = $current;

        try {
            list($items, $next) = scrape_phana_page($current);
            $all = array_merge($all, $items);
            $current = $next;
        } catch (Exception $e) {
            break;
        }

        $page++;
    }

    return $all;
}
