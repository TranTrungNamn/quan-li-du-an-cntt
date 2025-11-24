<?php
    // THÊM ĐOẠN NÀY ĐỂ DEBUG
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_start();
    require_once "../db.php";
    
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        echo json_encode(["status" => "error", "message" => "Missing login data"]);
        exit;
    }

    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    // Tìm người dùng
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    if ($user_data && password_verify($pass, $user_data['password_hash'])) {
        // Đăng nhập thành công
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $user;
        $_SESSION['user_id'] = $user_data['id'];

        echo json_encode(["status" => "success"]);
    } else {
        // Tên đăng nhập hoặc mật khẩu không hợp lệ
        echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
    }

    $stmt->close();
?>