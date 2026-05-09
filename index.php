<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: {$_SESSION['role']}_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Library Login</h2>
            <form id="loginForm">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="username" class="form-control" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Sign In</button>
                <p style="margin-top:15px;">
                    Don't have an account?
                    <a href="signup.php" class="link">Sign up</a>
                </p>
                <div id="errorMsg" class="alert error"></div>
            </form>
        </div>
    </div>
    <script src="script.js"></script>
    <script>
  function showError(msg) {
    const box = document.getElementById("errorBox");
    box.textContent = msg;
    box.classList.add("show");
}
function hideError() {
    const box = document.getElementById("errorBox");
    box.textContent = "";
    box.classList.remove("show");
}

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await apiCall('login', {
                username: document.getElementById('username').value,
                password: document.getElementById('password').value
            });
            if (res.success) {
                window.location.href = res.role + '_dashboard.php';
            } else {
                document.getElementById('errorMsg').innerText = res.message;
                document.getElementById('errorMsg').style.display = 'block';
            }
        });
    </script>
</body>
</html>
