<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $class_name  = trim($_POST['class_name'] ?? '');
    $class_code  = strtoupper(trim($_POST['class_code'] ?? ''));
    $semester    = trim($_POST['semester'] ?? '');
    $year        = intval($_POST['year'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($class_name === '' || $class_code === '' || $semester === '' || $year <= 0) {
        header("Location: create_class.php?error=missing");
        exit;
    }

    try {
        // 1) Create the class (DB enforces unique_class)
        $stmt = $pdo->prepare("
            INSERT INTO classes (class_name, class_code, semester, year, description, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$class_name, $class_code, $semester, $year, $description, $user_id]);

        $class_id = $pdo->lastInsertId();

        // 2) Make creator the moderator role (instructor)
        $stmt = $pdo->prepare("
            INSERT INTO class_members (class_id, user_id, role)
            VALUES (?, ?, 'instructor')
        ");
        $stmt->execute([$class_id, $user_id]);

        header("Location: dashboard.php?class_created=1");
        exit;

    } catch (PDOException $e) {
        // Duplicate class (same code/semester/year)
        if ($e->getCode() === '23000') {
            header("Location: create_class.php?error=duplicate");
            exit;
        }
        die("Error creating class: " . $e->getMessage());
    }
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Class - Academic Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Create a Class</h1>
        <div class="user-info">
            <a href="dashboard.php">Back to Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <?php if ($error === 'duplicate'): ?>
        <div class="error">That class already exists (same code, semester, and year).</div>
    <?php elseif ($error === 'missing'): ?>
        <div class="error">Please fill in all required fields.</div>
    <?php endif; ?>

    <div class="section">
        <form method="post" class="join-class-form">
            <label>Class Name:</label>
            <input type="text" name="class_name" required>

            <label>Class Code:</label>
            <input type="text" name="class_code" placeholder="CSE389" required>

            <label>Semester:</label>
            <input type="text" name="semester" placeholder="Fall" required>

            <label>Year:</label>
            <input type="number" name="year" required>

            <label>Description (optional):</label>
            <textarea name="description" rows="4"></textarea>

            <button type="submit">Create Class</button>
        </form>
    </div>
</div>
</body>
</html>
