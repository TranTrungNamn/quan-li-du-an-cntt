<?php session_start(); 
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
    header("Location: ../index.php"); // Chuyển hướng nếu đã đăng nhập
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Login</title>
<link rel="stylesheet" href="../style.css"> 
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-container">
    <h2>User Login</h2>

    <form id="loginForm">
        <input type="text" id="username" placeholder="Username" required>
        <input type="password" id="password" placeholder="Password" required>

        <button type="submit">Login</button>
        <p id="msg" class="msg"></p>
    </form>
    
    <p style="text-align:center; margin-top: 15px; font-size: 14px;">
        Don't have an account? <a href="signup.php" style="color: black; font-weight: 600;">Sign Up</a>
        <br><br>
        <a href="../admin/login.php" style="color: #6b7280; font-size: 12px;">Admin Login</a>
    </p>
</div>

<script>
document.getElementById("loginForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    let username = document.getElementById("username").value;
    let password = document.getElementById("password").value;
    let msg = document.getElementById("msg");
    msg.innerText = "";

    try {
        let res = await fetch("../api/auth/user_login.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
        });
        let data = await res.json();

        if (data.status === "success") {
            window.location.href = "../index.php";
        } else {
            msg.innerText = data.message || "Login failed.";
        }
    } catch (error) {
        msg.innerText = "A network error occurred: " + error.message;
    }
});
</script>

</body>
</html>