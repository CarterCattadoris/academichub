<?php

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = intval($_GET['event_id'] ?? 0);

if ($event_id <= 0) {
    header("Location: view_events.php");
    exit;
}

// Load event info
$stmt = $pdo->prepare("
    SELECT event_id, class_id, creator_user_id
    FROM calendar_events
    WHERE event_id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: view_events.php?error=notfound");
    exit;
}

// Check class role
$stmt = $pdo->prepare("
    SELECT role
    FROM class_members
    WHERE class_id = ? AND user_id = ?
");
$stmt->execute([$event['class_id'], $user_id]);
$membership = $stmt->fetch();

$isModerator = ($membership && $membership['role'] === 'instructor');
$isOwner = ($event['creator_user_id'] == $user_id);

if (!$isOwner && !$isModerator) {
    http_response_code(403);
    exit("Unauthorized");
}

// Allowed → delete
$stmt = $pdo->prepare("DELETE FROM calendar_events WHERE event_id = ?");
$stmt->execute([$event_id]);

header("Location: view_events.php?deleted=1");
exit;
