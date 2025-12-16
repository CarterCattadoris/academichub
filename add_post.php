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

$class_id = (int)($_POST['class_id'] ?? 0);
$content  = trim($_POST['content'] ?? '');
$user_id  = (int)$_SESSION['user_id'];

if ($class_id <= 0 || $content === '') {
    header("Location: class_view.php?class_id={$class_id}&post_error=" . urlencode("Post cannot be empty."));
    exit;
}

/* Enrollment check: user must be in class */
$stmt = $pdo->prepare("SELECT 1 FROM class_members WHERE class_id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$class_id, $user_id]);
if (!$stmt->fetch()) {
    die("Unauthorized");
}

$stmt = $pdo->prepare("
    INSERT INTO discussion_posts (class_id, user_id, content, created_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$class_id, $user_id, $content]);

header("Location: class_view.php?class_id={$class_id}&post_ok=" . urlencode("Post created."));
exit;
