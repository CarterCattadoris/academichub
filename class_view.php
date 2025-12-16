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

$class_id = (int)$_GET['class_id'];
$user_id  = (int)$_SESSION['user_id'];

// verify user is actually in this class + get their role
$stmt = $pdo->prepare("
    SELECT role
    FROM class_members
    WHERE class_id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$class_id, $user_id]);
$memberRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$memberRow) {
    die("You're not enrolled in this class");
}

$user_role    = $memberRow['role'];               // 'student' or 'instructor'
$isInstructor = ($user_role === 'instructor');    // moderator behavior

// get class info
$stmt = $pdo->prepare("
    SELECT c.*, u.first_name, u.last_name
    FROM classes c
    JOIN users u ON c.created_by = u.user_id
    WHERE c.class_id = ?
");
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    header("Location: dashboard.php");
    exit;
}

// grab all events for this class
$stmt = $pdo->prepare("
    SELECT ce.*, u.username as creator
    FROM calendar_events ce
    JOIN users u ON ce.creator_user_id = u.user_id
    WHERE ce.class_id = ?
    ORDER BY ce.start_datetime
");
$stmt->execute([$class_id]);
$all_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// split events into calendar format and upcoming list
$calendar_events = [];
$upcoming = [];
$now = time();

foreach ($all_events as $evt) {
    $calendar_events[] = [
        'title' => $evt['event_title'],
        'start' => str_replace(' ', 'T', $evt['start_datetime']),
        'end'   => str_replace(' ', 'T', $evt['end_datetime']),
        'color' => '#3788d8',
        'extendedProps' => [
            'description' => $evt['event_description'],
            'location'    => $evt['location'],
            'type'        => $evt['event_type'],
            'creator'     => $evt['creator']
        ]
    ];

    if (strtotime($evt['start_datetime']) >= $now) {
        $upcoming[] = $evt;
    }
}

// get everyone in this class
$stmt = $pdo->prepare("
    SELECT u.*, cm.role
    FROM class_members cm
    JOIN users u ON cm.user_id = u.user_id
    WHERE cm.class_id = ?
    ORDER BY cm.role, u.last_name
");
$stmt->execute([$class_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   [BEG EDIT] Discussion data
   ========================= */

$postError = $_GET['post_error'] ?? '';
$postOk    = $_GET['post_ok'] ?? '';
$replyError = $_GET['reply_error'] ?? '';
$replyOk    = $_GET['reply_ok'] ?? '';
$editError  = $_GET['edit_error'] ?? '';
$editOk     = $_GET['edit_ok'] ?? '';
$delError   = $_GET['del_error'] ?? '';
$delOk      = $_GET['del_ok'] ?? '';

// Fetch posts for this class
$stmt = $pdo->prepare("
    SELECT
        p.post_id,
        p.class_id,
        p.user_id,
        p.content,
        p.created_at,
        p.updated_at,
        u.username,
        u.first_name,
        u.last_name
    FROM discussion_posts p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.class_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$class_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch replies for those posts (if any)
$repliesByPost = [];
if (!empty($posts)) {
    $postIds = array_map(fn($r) => (int)$r['post_id'], $posts);
    $placeholders = implode(',', array_fill(0, count($postIds), '?'));

    $stmt = $pdo->prepare("
        SELECT
            r.reply_id,
            r.post_id,
            r.user_id,
            r.content,
            r.created_at,
            r.updated_at,
            u.username,
            u.first_name,
            u.last_name
        FROM discussion_replies r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.post_id IN ($placeholders)
        ORDER BY r.created_at ASC
    ");
    $stmt->execute($postIds);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($replies as $rep) {
        $pid = (int)$rep['post_id'];
        if (!isset($repliesByPost[$pid])) $repliesByPost[$pid] = [];
        $repliesByPost[$pid][] = $rep;
    }
}

/* =========================
   [END EDIT] Discussion data
   ========================= */
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($class['class_code']); ?></title>
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

        .tab-content { display: none; }
        .tab-content.active { display: block; }

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

        #event-details h3 { margin-top: 0; }

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

        /* =========================
           [BEG EDIT] Discussion styles
           ========================= */
        .discussion-wrap { max-width: 900px; }
        .discussion-compose {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .discussion-compose textarea {
            width: 100%;
            min-height: 90px;
            resize: vertical;
        }
        .discussion-post {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
        }
        .post-meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }
        .post-content { white-space: pre-wrap; }
        .post-actions {
            margin-top: 10px;
            display: flex;
            flex-direction: column; /* buttons one over another */
            gap: 8px;
            max-width: 160px;
        }
        .post-actions a {
            display: inline-block;
            text-align: center;
            padding: 8px 10px;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #ccc;
        }
        .btn-edit { background: #f8f9fa; color: #111; }
        .btn-delete { background: #fff5f5; color: #9b1c1c; border-color: #f5c2c7; }
        .replies {
            margin-top: 12px;
            padding-left: 16px;
            border-left: 3px solid #eee;
        }
        .reply {
            background: #fafafa;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
        .reply-actions {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-width: 160px;
        }
        .reply-actions a {
            display: inline-block;
            text-align: center;
            padding: 7px 10px;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #ccc;
        }
        .reply-form {
            margin-top: 12px;
        }
        .reply-form textarea {
            width: 100%;
            min-height: 70px;
            resize: vertical;
        }
        /* =========================
           [END EDIT] Discussion styles
           ========================= */
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
        <?php if (!empty($class['description'])): ?>
            <p><?php echo htmlspecialchars($class['description']); ?></p>
        <?php endif; ?>
        <p style="font-size: 0.85em; color:#666;">
            Your role: <strong><?php echo htmlspecialchars(ucfirst($user_role)); ?></strong>
        </p>
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
                        <?php if (!empty($evt['location'])): ?>
                            <p><strong>Where:</strong> <?php echo htmlspecialchars($evt['location']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($evt['event_description'])): ?>
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

    <!-- =========================
         [BEG EDIT] Discussion tab
         ========================= -->
    <div id="tab-chat" class="tab-content">
        <div class="section discussion-wrap">
            <h2>Class Discussion</h2>

            <?php if ($postOk): ?><div class="success"><?php echo htmlspecialchars($postOk); ?></div><?php endif; ?>
            <?php if ($postError): ?><div class="error"><?php echo htmlspecialchars($postError); ?></div><?php endif; ?>
            <?php if ($replyOk): ?><div class="success"><?php echo htmlspecialchars($replyOk); ?></div><?php endif; ?>
            <?php if ($replyError): ?><div class="error"><?php echo htmlspecialchars($replyError); ?></div><?php endif; ?>
            <?php if ($editOk): ?><div class="success"><?php echo htmlspecialchars($editOk); ?></div><?php endif; ?>
            <?php if ($editError): ?><div class="error"><?php echo htmlspecialchars($editError); ?></div><?php endif; ?>
            <?php if ($delOk): ?><div class="success"><?php echo htmlspecialchars($delOk); ?></div><?php endif; ?>
            <?php if ($delError): ?><div class="error"><?php echo htmlspecialchars($delError); ?></div><?php endif; ?>

            <div class="discussion-compose">
                <form method="POST" action="add_post.php">
                    <input type="hidden" name="class_id" value="<?php echo (int)$class_id; ?>">
                    <label for="content"><strong>New post</strong></label><br>
                    <textarea name="content" id="content" required maxlength="2000" placeholder="Write something..."></textarea>
                    <br><br>
                    <button type="submit">Post</button>
                </form>
            </div>

            <?php if (empty($posts)): ?>
                <p>No posts yet. Be the first to post.</p>
            <?php else: ?>

                <?php foreach ($posts as $p): ?>
                    <?php
                        $postId = (int)$p['post_id'];
                        $postOwner = ((int)$p['user_id'] === $user_id);
                        $canEditPost = $postOwner;                 // only author edits
                        $canDeletePost = $postOwner || $isInstructor; // author OR instructor deletes
                    ?>

                    <div class="discussion-post">
                        <div class="post-meta">
                            <div>
                                <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong>
                                (@<?php echo htmlspecialchars($p['username']); ?>)
                            </div>
                            <div>
                                <?php echo htmlspecialchars($p['created_at']); ?>
                                <?php if (!empty($p['updated_at'])): ?>
                                    <span style="color:#888;">(edited)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="post-content"><?php echo nl2br(htmlspecialchars($p['content'])); ?></div>

                        <?php if ($canEditPost || $canDeletePost): ?>
                            <div class="post-actions">
                                <?php if ($canEditPost): ?>
                                    <a class="btn-edit" href="edit_post.php?post_id=<?php echo $postId; ?>">Edit</a>
                                <?php endif; ?>

                                <?php if ($canDeletePost): ?>
                                    <a class="btn-delete"
                                       href="delete_post.php?type=post&id=<?php echo $postId; ?>"
                                       onclick="return confirm('Delete this post? This will also remove its replies.');">
                                        Delete
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="replies">
                            <?php if (!empty($repliesByPost[$postId])): ?>
                                <?php foreach ($repliesByPost[$postId] as $r): ?>
                                    <?php
                                        $replyId = (int)$r['reply_id'];
                                        $replyOwner = ((int)$r['user_id'] === $user_id);
                                        $canEditReply = $replyOwner;
                                        $canDeleteReply = $replyOwner || $isInstructor;
                                    ?>
                                    <div class="reply">
                                        <div class="post-meta" style="margin-bottom:6px;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></strong>
                                                (@<?php echo htmlspecialchars($r['username']); ?>)
                                            </div>
                                            <div>
                                                <?php echo htmlspecialchars($r['created_at']); ?>
                                                <?php if (!empty($r['updated_at'])): ?>
                                                    <span style="color:#888;">(edited)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="post-content"><?php echo nl2br(htmlspecialchars($r['content'])); ?></div>

                                        <?php if ($canEditReply || $canDeleteReply): ?>
                                            <div class="reply-actions">
                                                <?php if ($canEditReply): ?>
                                                    <a class="btn-edit" href="edit_post.php?reply_id=<?php echo $replyId; ?>">Edit</a>
                                                <?php endif; ?>

                                                <?php if ($canDeleteReply): ?>
                                                    <a class="btn-delete"
                                                       href="delete_post.php?type=reply&id=<?php echo $replyId; ?>"
                                                       onclick="return confirm('Delete this reply?');">
                                                        Delete
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="reply-form">
                                <form method="POST" action="add_reply.php">
                                    <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                                    <label><strong>Reply</strong></label><br>
                                    <textarea name="content" required maxlength="2000" placeholder="Write a reply..."></textarea>
                                    <br><br>
                                    <button type="submit">Reply</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </div>
    <!-- =========================
         [END EDIT] Discussion tab
         ========================= -->

</div>

<script>
    function showTab(btn, tabName) {
        var tabs = document.querySelectorAll('.tab-content');
        for (var i = 0; i < tabs.length; i++) tabs[i].classList.remove('active');

        var buttons = document.querySelectorAll('.tab-button');
        for (var i = 0; i < buttons.length; i++) buttons[i].classList.remove('active');

        document.getElementById('tab-' + tabName).classList.add('active');
        btn.classList.add('active');
    }

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
                    var detailsDiv = document.getElementById('event-details');
                    document.getElementById('detail-title').textContent = info.event.title;

                    var timeText = 'When: ' + info.event.start.toLocaleString();
                    if (info.event.end) timeText += ' - ' + info.event.end.toLocaleTimeString();
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
