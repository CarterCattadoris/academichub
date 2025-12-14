<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// need a class id to show anything
if (!isset($_GET['class_id'])) {
    header("Location: dashboard.php");
    exit;
}

$class_id = $_GET['class_id'];

// verify user is actually in this class
$stmt = $pdo->prepare("SELECT COUNT(*) as enrolled FROM class_members WHERE class_id = ? AND user_id = ?");
$stmt->execute([$class_id, $_SESSION['user_id']]);
$check = $stmt->fetch();
if ($check['enrolled'] == 0) {
    die("You're not enrolled in this class");
}

// get class info
$stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name FROM classes c JOIN users u ON c.created_by = u.user_id WHERE c.class_id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

// grab all events for this class
$stmt = $pdo->prepare("SELECT ce.*, u.username as creator FROM calendar_events ce JOIN users u ON ce.creator_user_id = u.user_id WHERE ce.class_id = ? ORDER BY ce.start_datetime");
$stmt->execute([$class_id]);
$all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// split events into calendar format and upcoming list
$calendar_events = [];
$upcoming = [];
$now = time();

foreach ($all_events as $evt) {
    // format for calendar - needs specific fields
    $calendar_events[] = [
        'title' => $evt['event_title'],
        'start' => str_replace(' ', 'T', $evt['start_datetime']),
        'end' => str_replace(' ', 'T', $evt['end_datetime']),
        'color' => '#3788d8',
        'extendedProps' => [
            'description' => $evt['event_description'],
            'location' => $evt['location'],
            'type' => $evt['event_type'],
            'creator' => $evt['creator']
        ]
    ];
    
    // also track upcoming events for the list view
    if (strtotime($evt['start_datetime']) >= $now) {
        $upcoming[] = $evt;
    }
}

// get everyone in this class
$stmt = $pdo->prepare("SELECT u.*, cm.role FROM class_members cm JOIN users u ON cm.user_id = u.user_id WHERE cm.class_id = ? ORDER BY cm.role, u.last_name");
$stmt->execute([$class_id]);
$members = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $class['class_code']; ?></title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        .class-info-box {
            background: #f4f4f4;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .tabs {
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-button {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-right: 5px;
            color: #333;
        }
        
        .tab-button:hover {
            background: #f0f0f0;
        }
        
        .tab-button.active {
            border-bottom: 3px solid #007bff;
            font-weight: bold;
            color: #007bff;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        #calendar {
            background: white;
            padding: 20px;
            border-radius: 5px;
        }
        
        #event-details {
            background: #f8f9fa;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            display: none;
        }
        
        #event-details h3 {
            margin-top: 0;
        }
        
        .member-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .member-card {
            background: white;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .event-item {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 3px solid #007bff;
        }
        
        .chat-box {
            background: #f8f9fa;
            padding: 30px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($class['class_code']); ?></h1>
            <div class="user-info">
                <a href="dashboard.php">Back</a> | <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="AllClassCalendar.php">Calendar</a>
            <a href="view_events.php">Events</a>
            <a href="add_event.php">Add Event</a>
            <a href="join_class.php">Join Class</a>
        </div>
        
        <div class="class-info-box">
            <h2><?php echo htmlspecialchars($class['class_name']); ?></h2>
            <p><strong>Semester:</strong> <?php echo htmlspecialchars($class['semester'] . ' ' . $class['year']); ?></p>
            <p><strong>Created by:</strong> <?php echo htmlspecialchars($class['first_name'] . ' ' . $class['last_name']); ?></p>
            <?php if ($class['description']): ?>
                <p><?php echo htmlspecialchars($class['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="tabs">
            <button class="tab-button active" data-tab="calendar" onclick="showTab(this, 'calendar')">Calendar</button>
            <button class="tab-button" data-tab="events" onclick="showTab(this, 'events')">Events List</button>
            <button class="tab-button" data-tab="members" onclick="showTab(this, 'members')">Members</button>
            <button class="tab-button" data-tab="chat" onclick="showTab(this, 'chat')">Discussion</button>
        </div>
        
        <div id="tab-calendar" class="tab-content active">
            <div class="section">
                <h2>Calendar</h2>
                <?php if (empty($calendar_events)): ?>
                    <p>No events yet. <a href="add_event.php">Add one?</a></p>
                <?php else: ?>
                    <div id='calendar'></div>
                    <div id="event-details">
                        <h3 id="detail-title"></h3>
                        <p id="detail-time"></p>
                        <p id="detail-location"></p>
                        <p id="detail-description"></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="tab-events" class="tab-content">
            <div class="section">
                <h2>Upcoming Events</h2>
                <?php if (empty($upcoming)): ?>
                    <p>No upcoming events.</p>
                <?php else: ?>
                    <?php foreach ($upcoming as $evt): ?>
                        <div class="event-item">
                            <h4><?php echo htmlspecialchars($evt['event_title']); ?></h4>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($evt['event_type']); ?></p>
                            <p><strong>When:</strong> <?php echo date('M j, Y g:i A', strtotime($evt['start_datetime'])); ?></p>
                            <?php if ($evt['location']): ?>
                                <p><strong>Where:</strong> <?php echo htmlspecialchars($evt['location']); ?></p>
                            <?php endif; ?>
                            <?php if ($evt['event_description']): ?>
                                <p><?php echo htmlspecialchars($evt['event_description']); ?></p>
                            <?php endif; ?>
                            <p style="font-size: 0.9em; color: #666;">Posted by @<?php echo htmlspecialchars($evt['creator']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="tab-members" class="tab-content">
            <div class="section">
                <h2>Class Members (<?php echo count($members); ?>)</h2>
                <div class="member-list">
                    <?php foreach ($members as $member): ?>
                        <div class="member-card">
                            <h4><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></h4>
                            <p>@<?php echo htmlspecialchars($member['username']); ?></p>
                            <p><?php echo htmlspecialchars($member['email']); ?></p>
                            <p style="font-size: 0.85em; color: #666;"><?php echo ucfirst($member['role']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div id="tab-chat" class="tab-content">
            <div class="section">
                <h2>Class Discussion</h2>
                <div class="chat-box">
                    <h3>Discussion Feature</h3>
                    <p>This is where the class discussion/chat will go.</p>
                    <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
                        Glenn is working on this part.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // switch between tabs
        function showTab(btn, tabName) {
            var tabs = document.querySelectorAll('.tab-content');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            var buttons = document.querySelectorAll('.tab-button');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove('active');
            }
            
            document.getElementById('tab-' + tabName).classList.add('active');
            btn.classList.add('active');
        }
        
        // setup calendar when page loads
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($calendar_events)): ?>
            var calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek'
                    },
                    events: <?php echo json_encode($calendar_events); ?>,
                    eventClick: function(info) {
                        // show event details below calendar
                        var detailsDiv = document.getElementById('event-details');
                        document.getElementById('detail-title').textContent = info.event.title;
                        
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
                    }
                });
                calendar.render();
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>