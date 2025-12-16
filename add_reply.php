<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$user_id = (int)$_SESSION['user_id'];

if ($post_id <= 0 || $content === '') {
    header("Location: dashboard.php");
    exit;
}

/* Find class_id for the post + ensure user enrolled */
$stmt = $pdo->prepare("
    SELECT p.class_id
    FROM discussion_posts p
    JOIN class_members cm ON cm.class_id = p.class_id AND cm.user_id = ?
    WHERE p.post_id = ?
    LIMIT 1
");
$stmt->execute([$user_id, $post_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Unauthorized");
}

$class_id = (int)$row['class_id'];

$stmt = $pdo->prepare("
    INSERT INTO discussion_replies (post_id, user_id, content, created_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$post_id, $user_id, $content]);

header("Location: class_view.php?class_id={$class_id}&reply_ok=" . urlencode("Reply posted."));
exit;
