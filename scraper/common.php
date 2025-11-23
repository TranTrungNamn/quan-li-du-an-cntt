<?php
/**
 * common.php
 * Common helpers for all scrapers
 */

if (!function_exists('fetch_html')) {
    function fetch_html(string $url, int $timeout = 30): string
    {
        $ch = curl_init($url);

        $headers = [
            'Upgrade-Insecure-Requests: 1',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_ENCODING       => '', // Cho phép cURL tự xử lý gzip/deflate/br
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false, // Quan trọng khi chạy localhost (XAMPP/Laragon)
            CURLOPT_SSL_VERIFYHOST => false,
            // Giả lập cookie để giữ session nếu cần
            CURLOPT_COOKIEJAR      => sys_get_temp_dir() . '/cookie.txt',
            CURLOPT_COOKIEFILE     => sys_get_temp_dir() . '/cookie.txt',
        ]);

        $html = curl_exec($ch);
        
        // Kiểm tra lỗi cURL
        if ($html === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: $err");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("HTTP Error $httpCode fetching $url");
        }

        return $html;
    }
}

// ... (Giữ nguyên các hàm to_int_price và absolute_url bên dưới)
if (!function_exists('to_int_price')) {
    function to_int_price(?string $text): ?int
    {
        if ($text === null) return 0;
        $num = preg_replace('/[^0-9]/', '', $text);
        if ($num === '' || $num === null) return 0;
        return (int)$num;
    }
}

if (!function_exists('absolute_url')) {
    function absolute_url(string $base, string $relative): string
    {
        if (preg_match('!^https?://!i', $relative)) return $relative;
        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host   = $parts['host']   ?? '';
        if (strpos($relative, '/') === 0) return "$scheme://$host$relative";
        return "$scheme://$host" . rtrim($parts['path'] ?? '/', '/') . '/' . $relative;
    }
}
?>