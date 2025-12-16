<?php
// edit_event.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if (!isset($_GET['event_id']) && !isset($_POST['event_id'])) {
    header("Location: view_events.php");
    exit;
}

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (int)$_POST['event_id'];

/* =========================
   Load event
========================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM calendar_events
    WHERE event_id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Event not found.");
}

/* =========================
   Enrollment + role check
========================= */
$stmt = $pdo->prepare("
    SELECT role
    FROM class_members
    WHERE class_id = ? AND user_id = ?
");
$stmt->execute([(int)$event['class_id'], $user_id]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership) {
    die("Unauthorized (not enrolled in this class).");
}

$isOwner     = ((int)$event['creator_user_id'] === $user_id);
$isModerator = ($membership['role'] === 'instructor');

if (!($isOwner || $isModerator)) {
    die("Unauthorized (no permission to edit this event).");
}

/* =========================
   Handle POST update
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['event_title'] ?? '');
    $desc        = trim($_POST['event_description'] ?? '');
    $type        = trim($_POST['event_type'] ?? '');
    $start       = trim($_POST['start_datetime'] ?? '');
    $end         = trim($_POST['end_datetime'] ?? '');
    $location    = trim($_POST['location'] ?? '');

    if ($title === '' || $type === '' || $start === '' || $end === '') {
        $error = "Title, type, start, and end are required.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE calendar_events
            SET event_title = ?, event_description = ?, event_type = ?,
                start_datetime = ?, end_datetime = ?, location = ?
            WHERE event_id = ?
        ");
        $stmt->execute([$title, $desc, $type, $start, $end, $location, $event_id]);

        header("Location: view_events.php");
        exit;
    }
}

/* =========================
   Show edit form
========================= */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event - Academic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Edit Event</h1>
        <div class="user-info">
            <a href="view_events.php">Back</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="section">
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_event.php">
            <input type="hidden" name="event_id" value="<?= (int)$event['event_id'] ?>">

            <label>Title</label>
            <input type="text" name="event_title" required value="<?= htmlspecialchars($event['event_title']) ?>">

            <label>Description</label>
            <textarea name="event_description"><?= htmlspecialchars($event['event_description'] ?? '') ?></textarea>

            <label>Type</label>
            <input type="text" name="event_type" required value="<?= htmlspecialchars($event['event_type']) ?>">

            <label>Start</label>
            <input type="datetime-local" name="start_datetime" required
                   value="<?= htmlspecialchars(str_replace(' ', 'T', $event['start_datetime'])) ?>">

            <label>End</label>
            <input type="datetime-local" name="end_datetime" required
                   value="<?= htmlspecialchars(str_replace(' ', 'T', $event['end_datetime'])) ?>">

            <label>Location</label>
            <input type="text" name="location" value="<?= htmlspecialchars($event['location'] ?? '') ?>">

            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>
</body>
</html>
