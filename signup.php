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
    <title>Library Signup</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Create Account</h2>
            <form id="signupForm">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Sign Up</button>
            </form>

            <p style="margin-top:15px;">
                Already have an account?
                <a href="index.php" class="link">Login</a>
            </p>
             <div id="errorMsg" class="alert error"></div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        document.getElementById('signupForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const res = await apiCall('signup', {
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            });

            if (res.success) {
                alert("Account created! Now login.");
                window.location.href = "index.php";
            } else {
                document.getElementById('errorMsg').innerText = res.message;
                document.getElementById('errorMsg').style.display = 'block';
            }
        });
    </script>
</body>
</html>