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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Academic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container dashboard-container">
        <div class="header">
            <h1>AcademicHub</h1>
            <div class="user-info">
                <span class="user-greeting">
                    Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!
                </span>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="view_events.php">All Events</a>
            <a href="add_event.php">Add Event</a>
        </div>

        <!-- Overview cards -->
        <div class="overview-grid">
            <div class="overview-card">
                <h3>My Classes</h3>
                <p class="overview-number"><?php echo count($classes); ?></p>
                <p class="overview-label">Enrolled this term</p>
            </div>
            <div class="overview-card">
                <h3>Upcoming Events</h3>
                <p class="overview-number"><?php echo count($upcoming_events); ?></p>
                <p class="overview-label">Next 10 on your calendar</p>
            </div>
            <div class="overview-card">
                <h3>Today</h3>
                <p class="overview-text">
                    <?php echo date('M j, Y'); ?>
                </p>
                <p class="overview-label">Stay on top of your schedule</p>
            </div>
        </div>

        <!-- Two-column layout: classes + upcoming events -->
        <div class="dashboard-grid">
            <section class="section dashboard-section">
                <h2>My Classes</h2>
                <?php if (empty($classes)): ?>
                    <p>You're not enrolled in any classes yet.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Class Code</th>
                                <th>Class Name</th>
                                <th>Semester</th>
                                <th>Events</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                                    <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($class['semester'] . ' ' . $class['year']); ?></td>
                                    <td><?php echo (int)$class['event_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section class="section dashboard-section">
                <h2>Upcoming Events</h2>
                <?php if (empty($upcoming_events)): ?>
                    <p>No upcoming events.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Class</th>
                                <th>Type</th>
                                <th>Date/Time</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_events as $event): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($event['event_title']); ?></strong>
                                        <div class="table-subtext">
                                            Created by <?php echo htmlspecialchars($event['creator']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['class_code']); ?></td>
                                    <td>
                                        <span class="event-type-pill">
                                            <?php echo htmlspecialchars($event['event_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($event['start_datetime'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </div>
    </div>
</body>
</html>
