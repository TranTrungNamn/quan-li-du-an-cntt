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
    <title>User Signup</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <div class="login-container">
        <h2>User Sign Up</h2>

        <form id="signupForm">
            <input type="text" id="username" placeholder="Username (min 3 chars)" required minlength="3">
            <input type="password" id="password" placeholder="Password (min 6 chars)" required minlength="6">
            <input type="password" id="confirm_password" placeholder="Confirm Password" required minlength="6">

            <button type="submit">Sign Up</button>
            <p id="msg" class="msg"></p>
        </form>

        <p style="text-align:center; margin-top: 15px; font-size: 14px;">
            Already have an account? <a href="login.php" style="color: black; font-weight: 600;">Login</a>
        </p>
    </div>

    <script>
        document.getElementById("signupForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            let username = document.getElementById("username").value;
            let password = document.getElementById("password").value;
            let confirm_password = document.getElementById("confirm_password").value;
            let msg = document.getElementById("msg");
            msg.innerText = "";

            if (password !== confirm_password) {
                msg.innerText = "Passwords do not match!";
                return;
            }

            try {
                let res = await fetch("../api/auth/user_signup.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
                });
                let data = await res.json();

                if (data.status === "success") {
                    msg.innerText = "Sign Up successful! Redirecting...";
                    msg.style.color = "green";
                    setTimeout(() => {
                        window.location.href = "../index.php";
                    }, 1000);
                } else {
                    msg.innerText = data.message || "Sign Up failed.";
                }
            } catch (error) {
                msg.innerText = "A network error occurred: " + error.message;
            }
        });
    </script>

</body>

</html>