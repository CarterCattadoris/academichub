<?php
// add_event.php

// Make sure the session is started before using $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        // Clear POST values after success so the form is blank again
        $_POST = [];
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Event - Academic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container add-event-container">
        <div class="header">
            <h1>Add New Event</h1>
            <div class="user-info">
                <a href="dashboard.php">Back to Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_events.php">All Events</a>
            <a href="add_event.php" class="active">Add Event</a>
            <a href="join_class.php">Join Class</a>
        </div>
        
        <div class="section">
            <h2>Create a New Event</h2>
            <p class="form-hint">
                Fill in the details below to add an event to one of your classes.
            </p>

            <?php if (isset($success)): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (empty($classes)): ?>
                <p>You must be enrolled in a class to create events.</p>
            <?php else: ?>
                <div class="form-card">
                    <form method="POST" class="event-form">
                        <div class="field-group">
                            <label for="class_id">Class</label>
                            <select name="class_id" id="class_id" required>
                                <option value="">Select a class...</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['class_id']; ?>"
                                        <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_code'] . ' - ' . $class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="field-group">
                            <label for="event_title">Event Title</label>
                            <input
                                type="text"
                                id="event_title"
                                name="event_title"
                                required
                                maxlength="100"
                                value="<?php echo isset($_POST['event_title']) ? htmlspecialchars($_POST['event_title']) : ''; ?>"
                            >
                        </div>
                        
                        <div class="field-group">
                            <label for="event_type">Event Type</label>
                            <select name="event_type" id="event_type" required>
                                <?php
                                $types = [
                                    'assignment'    => 'Assignment',
                                    'study_session' => 'Study Session',
                                    'exam'          => 'Exam',
                                    'project'       => 'Project',
                                    'other'         => 'Other',
                                ];
                                $selectedType = isset($_POST['event_type']) ? $_POST['event_type'] : 'assignment';
                                foreach ($types as $value => $label) {
                                    $sel = ($selectedType === $value) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($value) . "\" $sel>" . htmlspecialchars($label) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-row-two">
                            <div class="field-group">
                                <label for="start_datetime">Start Date &amp; Time</label>
                                <input
                                    type="datetime-local"
                                    id="start_datetime"
                                    name="start_datetime"
                                    required
                                    value="<?php echo isset($_POST['start_datetime']) ? htmlspecialchars($_POST['start_datetime']) : ''; ?>"
                                >
                            </div>
                            
                            <div class="field-group">
                                <label for="end_datetime">End Date &amp; Time</label>
                                <input
                                    type="datetime-local"
                                    id="end_datetime"
                                    name="end_datetime"
                                    required
                                    value="<?php echo isset($_POST['end_datetime']) ? htmlspecialchars($_POST['end_datetime']) : ''; ?>"
                                >
                            </div>
                        </div>
                        
                        <div class="field-group">
                            <label for="location">Location</label>
                            <input
                                type="text"
                                id="location"
                                name="location"
                                maxlength="255"
                                placeholder="e.g. Link, room number, or 'TBD'"
                                value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                            >
                        </div>
                        
                        <div class="field-group">
                            <label for="event_description">Description</label>
                            <textarea
                                id="event_description"
                                name="event_description"
                                rows="4"
                                placeholder="Optional notes, topics, or links..."
                            ><?php echo isset($_POST['event_description']) ? htmlspecialchars($_POST['event_description']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit">Create Event</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
