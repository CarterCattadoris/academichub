<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$post_id  = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$reply_id = isset($_GET['reply_id']) ? (int)$_GET['reply_id'] : 0;

$isEditingPost = ($post_id > 0);
$isEditingReply = (!$isEditingPost && $reply_id > 0);

if (!$isEditingPost && !$isEditingReply) {
    header("Location: dashboard.php");
    exit;
}

/* Load target + enforce ownership + enrollment */
if ($isEditingPost) {
    $stmt = $pdo->prepare("
        SELECT p.post_id, p.class_id, p.user_id, p.content
        FROM discussion_posts p
        JOIN class_members cm ON cm.class_id = p.class_id AND cm.user_id = ?
        WHERE p.post_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $post_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) die("Unauthorized");
    if ((int)$item['user_id'] !== $user_id) die("Only the author can edit this post.");

    $class_id = (int)$item['class_id'];
    $currentContent = $item['content'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newContent = trim($_POST['content'] ?? '');
        if ($newContent === '') {
            $error = "Content cannot be empty.";
        } else {
            $stmt = $pdo->prepare("
                UPDATE discussion_posts
                SET content = ?, updated_at = NOW()
                WHERE post_id = ?
            ");
            $stmt->execute([$newContent, $post_id]);
            header("Location: class_view.php?class_id={$class_id}&edit_ok=" . urlencode("Post updated."));
            exit;
        }
    }

} else {
    $stmt = $pdo->prepare("
        SELECT r.reply_id, r.post_id, r.user_id, r.content, p.class_id
        FROM discussion_replies r
        JOIN discussion_posts p ON p.post_id = r.post_id
        JOIN class_members cm ON cm.class_id = p.class_id AND cm.user_id = ?
        WHERE r.reply_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $reply_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) die("Unauthorized");
    if ((int)$item['user_id'] !== $user_id) die("Only the author can edit this reply.");

    $class_id = (int)$item['class_id'];
    $currentContent = $item['content'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newContent = trim($_POST['content'] ?? '');
        if ($newContent === '') {
            $error = "Content cannot be empty.";
        } else {
            $stmt = $pdo->prepare("
                UPDATE discussion_replies
                SET content = ?, updated_at = NOW()
                WHERE reply_id = ?
            ");
            $stmt->execute([$newContent, $reply_id]);
            header("Location: class_view.php?class_id={$class_id}&edit_ok=" . urlencode("Reply updated."));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Discussion Item</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Edit</h1>
        <div class="user-info">
            <a href="class_view.php?class_id=<?php echo (int)$class_id; ?>">Back</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="section">
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label><strong>Content</strong></label><br>
            <textarea name="content" required maxlength="2000" style="width:100%; min-height:140px;"><?php
                echo htmlspecialchars($currentContent);
            ?></textarea>
            <br><br>
            <button type="submit">Save</button>
        </form>
    </div>
</div>
</body>
</html>
