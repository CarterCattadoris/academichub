<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$class_code = strtoupper(trim($_POST['class_code'] ?? ''));
$semester   = trim($_POST['semester'] ?? '');
$year       = intval($_POST['year'] ?? 0);

if ($class_code === '' || $semester === '' || $year <= 0) {
    header("Location: join_class.php?error=missing");
    exit;
}

// 1) Find the class (must already exist)
$stmt = $pdo->prepare("
    SELECT class_id
    FROM classes
    WHERE class_code = ? AND semester = ? AND year = ?
");
$stmt->execute([$class_code, $semester, $year]);
$class = $stmt->fetch();

if (!$class) {
    header("Location: join_class.php?error=notfound");
    exit;
}

$class_id = $class['class_id'];

// 2) Prevent duplicate membership (unique_member exists in schema)
try {
    $stmt = $pdo->prepare("
        INSERT INTO class_members (class_id, user_id, role)
        VALUES (?, ?, 'student')
    ");
    $stmt->execute([$class_id, $user_id]);

} catch (PDOException $e) {
    // Duplicate membership
    if ($e->getCode() === '23000') {
        header("Location: dashboard.php?joined=already");
        exit;
    }
    die("Join failed: " . $e->getMessage());
}

header("Location: dashboard.php?joined=1");
exit;
