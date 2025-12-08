<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$class_code = $_POST['class_code'] ?? '';
$semester = $_POST['semester'] ?? '';
$year = $_POST['year'] ?? '';

// Look up class by code + semester + year
$stmt = $pdo->prepare("
    SELECT class_id 
    FROM classes
    WHERE class_code = ? AND semester = ? AND year = ?
");
$stmt->execute([$class_code, $semester, $year]);
$class = $stmt->fetch();

if (!$class) {
    die("Class not found. Check code, semester, and year.");
}

$class_id = $class['class_id'];

// Check if already enrolled
$stmt = $pdo->prepare("
    SELECT 1 
    FROM class_members 
    WHERE user_id = ? AND class_id = ?
");
$stmt->execute([$user_id, $class_id]);

if ($stmt->fetch()) {
    die("You are already enrolled in this class.");
}

// Enroll user
$stmt = $pdo->prepare("
    INSERT INTO class_members (user_id, class_id, role)
    VALUES (?, ?, 'student')
");
$stmt->execute([$user_id, $class_id]);

header("Location: dashboard.php?joined=1");
exit;
