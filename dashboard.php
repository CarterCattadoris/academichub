<?php
// dashboard.php
require_once 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's classes
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(ce.event_id) as event_count
    FROM classes c
    JOIN class_members cm ON c.class_id = cm.class_id
    LEFT JOIN calendar_events ce ON c.class_id = ce.class_id
    WHERE cm.user_id = ?
    GROUP BY c.class_id
");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();

// Get upcoming events
$stmt = $pdo->prepare("
    SELECT 
        ce.*,
        c.class_name,
        c.class_code,
        u.username as creator
    FROM calendar_events ce
    JOIN classes c ON ce.class_id = c.class_id
    JOIN class_members cm ON c.class_id = cm.class_id
    JOIN users u ON ce.creator_user_id = u.user_id
    WHERE cm.user_id = ?
        AND ce.start_datetime >= NOW()
    ORDER BY ce.start_datetime ASC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$upcoming_events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Academic Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Academic Hub Dashboard</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!
                <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_events.php">All Events</a>
            <a href="add_event.php">Add Event</a>
        </div>
        
        <div class="section">
            <h2>My Classes</h2>
            <?php if (empty($classes)): ?>
                <p>You're not enrolled in any classes yet.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Class Code</th>
                        <th>Class Name</th>
                        <th>Semester</th>
                        <th>Events</th>
                    </tr>
                    <?php foreach ($classes as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['semester'] . ' ' . $class['year']); ?></td>
                            <td><?php echo $class['event_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Upcoming Events</h2>
            <?php if (empty($upcoming_events)): ?>
                <p>No upcoming events.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Event</th>
                        <th>Class</th>
                        <th>Type</th>
                        <th>Date/Time</th>
                        <th>Location</th>
                    </tr>
                    <?php foreach ($upcoming_events as $event): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($event['event_title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($event['class_code']); ?></td>
                            <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($event['start_datetime'])); ?></td>
                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>