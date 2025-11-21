<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "web_scraping_002";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Kết nối MySQL thất bại: " . $conn->connect_error);
}
?>
