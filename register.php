<?php
session_start();
require_once "config.php";

$errors = [];

// Handle submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first = trim($_POST["first_name"]);
    $last = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    if (empty($first) || empty($last) || empty($email) || empty($username) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check unique username/email
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $errors[] = "Username or email already in use.";
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name, user_role)
            VALUES (?, ?, ?, ?, ?, 'student')
        ");
        $stmt->execute([$username, $email, $hash, $first, $last]);

        header("Location: index.php?registered=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Account</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

<div class="auth-wrapper">
    <div class="auth-card">

        <h1>Create Account</h1>

        <?php foreach ($errors as $e): ?>
            <div class="error"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form class="auth-form" action="register.php" method="POST">

            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>

            <input type="text" name="username" placeholder="Username" required>

            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>

            <button class="auth-button" type="submit">Create Account</button>

        </form>

        <p class="auth-switch">
            Already have an account? <a href="index.php">Log in</a>
        </p>

    </div>
</div>

</body>
</html>
