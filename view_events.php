<?php
// view_events.php

// Make sure the session is started before using $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get all events for user's classes
$stmt = $pdo->prepare("
    SELECT 
        ce.*,
        c.class_name,
        c.class_code,
        u.username as creator,
        COUNT(ep.participant_id) as participant_count
    FROM calendar_events ce
    JOIN classes c ON ce.class_id = c.class_id
    JOIN class_members cm ON c.class_id = cm.class_id
    JOIN users u ON ce.creator_user_id = u.user_id
    LEFT JOIN event_participants ep ON ce.event_id = ep.event_id
    WHERE cm.user_id = ?
    GROUP BY ce.event_id
    ORDER BY ce.start_datetime DESC
");
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Events - Academic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container events-container">
        <div class="header">
            <h1>All Events</h1>
            <div class="user-info">
                <a href="dashboard.php">Back to Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_events.php" class="active">All Events</a>
            <a href="add_event.php">Add Event</a>
            <a href="join_class.php">Join Class</a>
        </div>
        
        <div class="section">
            <h2>Calendar Events</h2>
            <?php if (empty($events)): ?>
                <p>No events found.</p>
            <?php else: ?>
                <div class="events-list">
                    <?php foreach ($events as $event): ?>
                        <?php
                            $startTs = strtotime($event['start_datetime']);
                            $endTs   = strtotime($event['end_datetime']);
                            $dateStr = date('l, F j, Y', $startTs);
                            $timeStr = date('g:i A', $startTs) . ' - ' . date('g:i A', $endTs);
                        ?>
                        <div class="event-card enhanced-event-card">
                            <div class="event-card-header">
                                <div>
                                    <h3 class="event-title">
                                        <?php echo htmlspecialchars($event['event_title']); ?>
                                    </h3>
                                    <div class="event-class-line">
                                        <?php echo htmlspecialchars($event['class_name']); ?>
                                        &middot;
                                        <?php echo htmlspecialchars($event['class_code']); ?>
                                    </div>
                                </div>
                                <span class="event-type-pill">
                                    <?php echo htmlspecialchars($event['event_type']); ?>
                                </span>
                            </div>

                            <div class="event-card-meta">
                                <div class="event-meta-item">
                                    <span class="meta-label">When</span>
                                    <span class="meta-value"><?php echo $dateStr; ?></span>
                                    <span class="meta-subvalue"><?php echo $timeStr; ?></span>
                                </div>
                                <div class="event-meta-item">
                                    <span class="meta-label">Location</span>
                                    <span class="meta-value">
                                        <?php echo htmlspecialchars($event['location']); ?>
                                    </span>
                                </div>
                                <div class="event-meta-item">
                                    <span class="meta-label">Created by</span>
                                    <span class="meta-value">
                                        <?php echo htmlspecialchars($event['creator']); ?>
                                    </span>
                                    <span class="meta-subvalue">
                                        <?php echo (int)$event['participant_count']; ?> participant(s)
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($event['event_description'])): ?>
                                <p class="event-description">
                                    <?php echo htmlspecialchars($event['event_description']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
