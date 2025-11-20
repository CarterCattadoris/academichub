<?php
// add_event.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get user's classes
$stmt = $pdo->prepare("
    SELECT c.*
    FROM classes c
    JOIN class_members cm ON c.class_id = cm.class_id
    WHERE cm.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO calendar_events 
            (class_id, creator_user_id, event_title, event_description, event_type, 
             start_datetime, end_datetime, location)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['class_id'],
            $_SESSION['user_id'],
            $_POST['event_title'],
            $_POST['event_description'],
            $_POST['event_type'],
            $_POST['start_datetime'],
            $_POST['end_datetime'],
            $_POST['location']
        ]);
        
        $success = "Event created successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Event - Academic Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add New Event</h1>
            <div class="user-info">
                <a href="dashboard.php">Back to Dashboard</a> |
                <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_events.php">All Events</a>
            <a href="add_event.php" class="active">Add Event</a>
        </div>
        
        <div class="section">
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (empty($classes)): ?>
                <p>You must be enrolled in a class to create events.</p>
            <?php else: ?>
                <form method="POST">
                    <label>Class:</label>
                    <select name="class_id" required>
                        <option value="">Select a class...</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>">
                                <?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label>Event Title:</label>
                    <input type="text" name="event_title" required maxlength="100">
                    
                    <label>Event Type:</label>
                    <select name="event_type" required>
                        <option value="assignment">Assignment</option>
                        <option value="study_session">Study Session</option>
                        <option value="exam">Exam</option>
                        <option value="project">Project</option>
                        <option value="other">Other</option>
                    </select>
                    
                    <label>Start Date & Time:</label>
                    <input type="datetime-local" name="start_datetime" required>
                    
                    <label>End Date & Time:</label>
                    <input type="datetime-local" name="end_datetime" required>
                    
                    <label>Location:</label>
                    <input type="text" name="location" maxlength="255">
                    
                    <label>Description:</label>
                    <textarea name="event_description" rows="4"></textarea>
                    
                    <button type="submit">Create Event</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>