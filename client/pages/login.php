<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hotel Booking System</title>
    <link rel="stylesheet" href="../styling/styling.css">
</head>
<body>
    <img src="../assets/hotel_banner.png" alt="Hotel Booking System" class="header-image">
    <div class="container">
        <h1>User Login</h1>
        <div class="auth-form">
            <form id="loginForm" action="../../server/auth.php" method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <input type="hidden" name="action" value="user_login">
                <button type="submit">Login</button>
            </form>
            <p>Don't have an account? <a href="../pages/register.html">Register here</a></p>
            <p>Are you an admin? <a href="../pages/admin_login.html">Admin login</a></p>
        </div>
    </div>
</body>
</html>