<?php
// index.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];

    // Simple login - just check if user exists (no password for testing)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Hub - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo-circle">AH</div>
                <div>
                    <h1 class="auth-title">AcademicHub</h1>
                    <p class="auth-subtitle">
                        Sign in with a demo username to explore the portal.
                    </p>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="auth-field">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        placeholder="thomas, carter, aaron, angelo, glenn"
                    >
                </div>

                <button type="submit" class="auth-button">
                    Sign In
                </button>
            </form>

            <div class="hint">
                <p class="hint-label">Demo accounts</p>
                <p class="hint-users">thomas • carter • aaron • angelo • glenn</p>
            </div>
        </div>
    </div>
</body>
</html>
