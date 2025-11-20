<?php
// view_events.php
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
<html>
<head>
    <title>All Events - Academic Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>All Events</h1>
            <div class="user-info">
                <a href="dashboard.php">Back to Dashboard</a> |
                <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_events.php" class="active">All Events</a>
            <a href="add_event.php">Add Event</a>
        </div>
        
        <div class="section">
            <h2>Calendar Events</h2>
            <?php if (empty($events)): ?>
                <p>No events found.</p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <h3><?php echo htmlspecialchars($event['event_title']); ?></h3>
                        <p><strong>Class:</strong> <?php echo htmlspecialchars($event['class_name']); ?> (<?php echo htmlspecialchars($event['class_code']); ?>)</p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($event['event_type']); ?></p>
                        <p><strong>When:</strong> <?php echo date('l, F j, Y', strtotime($event['start_datetime'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['start_datetime'])); ?> - <?php echo date('g:i A', strtotime($event['end_datetime'])); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                        <p><strong>Created by:</strong> <?php echo htmlspecialchars($event['creator']); ?></p>
                        <p><strong>Participants:</strong> <?php echo $event['participant_count']; ?></p>
                        <?php if ($event['event_description']): ?>
                            <p><strong>Description:</strong> <?php echo htmlspecialchars($event['event_description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>