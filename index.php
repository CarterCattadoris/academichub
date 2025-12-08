<?php
session_start();
$error = isset($_GET['error']) ? "Invalid username or password." : "";
$registered = isset($_GET['registered']) ? "Account created! You may now log in." : "";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Academic Hub</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="login-page">
<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-header">
            <div class="auth-logo-circle">AH</div>
            <div>
                <h1 class="auth-title">Academic Hub</h1>
                <p class="auth-subtitle">Login to continue</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($registered): ?>
            <div class="success"><?= htmlspecialchars($registered) ?></div>
        <?php endif; ?>

        <form class="auth-form" action="dashboard.php" method="POST">
            <div class="auth-field">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="auth-field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button class="auth-button" type="submit">Log In</button>
        </form>

        <p class="auth-switch">
            Don’t have an account? <a href="register.php">Create one</a>
        </p>

    </div>
</div>

</body>
</html>
