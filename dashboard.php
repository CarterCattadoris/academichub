<?php
session_start();
require_once "config.php";

/*************************************************
 * LOGIN HANDLER
 *************************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['role'] = $user['user_role'];   // CORRECT FIELD NAME

    } else {
        header("Location: index.php?error=1");
        exit;
    }
}

/*************************************************
 * BLOCK UNAUTHORIZED ACCESS
 *************************************************/
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

/*************************************************
 * LOAD DASHBOARD DATA
 *************************************************/

// User info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Class list
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

// Upcoming events
$stmt = $pdo->prepare("
    SELECT ce.*, c.class_name, c.class_code, u.username AS creator
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
                Welcome, <?= htmlspecialchars($user['first_name']) ?>!
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="view_events.php">All Events</a>
            <a href="add_event.php">Add Event</a>
            <a href="join_class.php">Join Class</a>
        </div>

        <div class="section">
            <h2>My Classes</h2>
            <?php if (empty($classes)): ?>
                <p>You aren’t enrolled in any classes yet.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Semester</th>
                            <th>Events</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                        <tr>
                            <td><?= htmlspecialchars($class['class_code']) ?></td>
                            <td><?= htmlspecialchars($class['class_name']) ?></td>
                            <td><?= htmlspecialchars($class['semester'].' '.$class['year']) ?></td>
                            <td><?= $class['event_count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>


        

        <div class="section">
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
                            <th>Date</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_events as $event): ?>
                        <tr>
                            <td><?= htmlspecialchars($event['event_title']) ?></td>
                            <td><?= htmlspecialchars($event['class_code']) ?></td>
                            <td><?= htmlspecialchars($event['event_type']) ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($event['start_datetime'])) ?></td>
                            <td><?= htmlspecialchars($event['location']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
