<?php
session_start();
require_once 'config.php';

// make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// get all events from classes the user is in
$stmt = $pdo->prepare("
    SELECT 
        ce.event_id,
        ce.event_title,
        ce.start_datetime,
        ce.end_datetime,
        ce.event_description,
        ce.location,
        ce.event_type,
        c.class_name,
        c.class_code,
        c.class_id
    FROM calendar_events ce
    JOIN classes c ON ce.class_id = c.class_id
    JOIN class_members cm ON c.class_id = cm.class_id
    WHERE cm.user_id = ?
    ORDER BY ce.start_datetime
");
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// different color for each class
$colors = ['#3788d8', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1'];

// format events for the calendar library
foreach ($events as &$event) {
    $event['color'] = $colors[$event['class_id'] % count($colors)];
    $event['title'] = $event['event_title'];
    $event['start'] = str_replace(' ', 'T', $event['start_datetime']);
    $event['end'] = str_replace(' ', 'T', $event['end_datetime']);
    $event['extendedProps'] = [
        'class_name' => $event['class_name'],
        'class_code' => $event['class_code'],
        'event_type' => $event['event_type'],
        'location' => $event['location'],
        'description' => $event['event_description']
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Calendar - Academic Hub</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <style>
        #calendar {
            max-width: 1100px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .calendar-legend {
            margin: 20px auto;
            max-width: 1100px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .legend-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 10px;
        }
        .legend-color {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 3px;
            margin-right: 8px;
            vertical-align: middle;
        }
        #event-details {
            max-width: 1100px;
            margin: 20px auto;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            display: none;
        }
        #event-details h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My Calendar</h1>
            <div class="user-info">
                <a href="dashboard.php">Back to Dashboard</a> | <a href="logout.php">Logout</a>
            </div>
        </div>
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="AllClassCalendar.php" class="active">Calendar</a>
            <a href="view_events.php">All Events</a>
            <a href="add_event.php">Add Event</a>
            <a href="join_class.php">Join Class</a>
        </div>
        <div class="section">
            <h2>Event Calendar</h2>
            <?php if (empty($events)): ?>
                <p>No events scheduled. <a href="add_event.php">Add your first event!</a></p>
            <?php else: ?>
                <div class="calendar-legend">
                    <strong>Classes:</strong>
                    <?php
                    // show which color represents which class
                    $unique_classes = [];
                    foreach ($events as $event) {
                        $class_id = $event['class_id'];
                        if (!isset($unique_classes[$class_id])) {
                            $unique_classes[$class_id] = [
                                'name' => $event['class_name'],
                                'code' => $event['class_code'],
                                'color' => $event['color']
                            ];
                        }
                    }
                    foreach ($unique_classes as $class):
                    ?>
                        <span class="legend-item">
                            <span class="legend-color" style="background-color: <?php echo $class['color']; ?>"></span>
                            <?php echo htmlspecialchars($class['code'] . ' - ' . $class['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div id='calendar'></div>
                <div id="event-details">
                    <h3 id="detail-title"></h3>
                    <p id="detail-class"></p>
                    <p id="detail-time"></p>
                    <p id="detail-location"></p>
                    <p id="detail-description"></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // setup calendar when page loads
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode($events); ?>,
                eventClick: function(info) {
                    // show event details below calendar
                    var detailsDiv = document.getElementById('event-details');
                    document.getElementById('detail-title').textContent = info.event.title;
                    var classText = 'Class: ' + info.event.extendedProps.class_name + ' (' + info.event.extendedProps.class_code + ')';
                    document.getElementById('detail-class').textContent = classText;
                    var timeText = 'When: ' + info.event.start.toLocaleString();
                    if (info.event.end) {
                        timeText += ' - ' + info.event.end.toLocaleTimeString();
                    }
                    document.getElementById('detail-time').textContent = timeText;
                    var location = info.event.extendedProps.location;
                    document.getElementById('detail-location').textContent = location ? 'Where: ' + location : '';
                    var desc = info.event.extendedProps.description;
                    document.getElementById('detail-description').textContent = desc ? desc : 'No description';
                    detailsDiv.style.display = 'block';
                },
                displayEventTime: true,
                eventOverlap: true,
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                weekNumbers: false,
                firstDay: 0,
                height: 'auto'
            });
            calendar.render();
        });
    </script>
</body>
</html>