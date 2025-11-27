<?php session_start(); 
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header("Location: admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<link rel="stylesheet" href="../style.css">
</head>

<body class="random-background no-scroll">

<div class="login-container">
    <h2 class="text-center">Admin Login</h2>

    <form id="loginForm">
        <input type="text" id="username" placeholder="Username" required>
        <input type="password" id="password" placeholder="Password" required>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        
        <p id="msg" class="text-center mt-20"></p>
    </form>
    
    <div class="text-center mt-20">
        <a href="../index.php" style="font-size: 0.875rem; text-decoration: none;">
            ← Back to Home
        </a>
    </div>
</div>

<script src="../script.js"></script>

<script>
document.getElementById("loginForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    let username = document.getElementById("username").value;
    let password = document.getElementById("password").value;
    let msg = document.getElementById("msg");
    
    msg.innerText = "Logging in...";
    msg.className = "text-center mt-20";
    // Xóa việc set color thủ công bằng JS để CSS Glass tự xử lý
    msg.style.color = "inherit"; 

    try {
        let res = await fetch("../api/auth/admin_login.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        });
        let data = await res.json();

        if (data.status === "success") {
            window.location.href = "admin_dashboard.php";
        } else {
            msg.innerText = data.message;
            msg.style.color = "var(--error)"; // Màu đỏ khi lỗi
        }
    } catch (err) {
        msg.innerText = "Connection error";
        msg.style.color = "var(--error)";
    }
});
</script>

</body>
</html>