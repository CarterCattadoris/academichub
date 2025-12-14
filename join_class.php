<?php
session_start();
require_once 'config.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Join a Class - Academic Hub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <div class="header">
        <h1>Join a Class</h1>
        <div class="user-info">
            Welcome, <?= htmlspecialchars($_SESSION['first_name']); ?>!
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="calendar.php">Calendar</a>
        <a href="view_events.php">All Events</a>
        <a href="add_event.php">Add Event</a>
        <a class="active" href="join_class.php">Join Class</a>
    </div>

    <div class="section">
        <h2>Find & Join a Class</h2>

        <form action="add_class.php" method="post" class="join-class-form">

            <label for="class_code">Class Code:</label>
            <input type="text" name="class_code" id="class_code" required>

            <label for="semester">Semester:</label>
            <input type="text" name="semester" id="semester" placeholder="Fall" required>

            <label for="year">Year:</label>
            <input type="number" name="year" id="year" required>

            <button type="submit">Join Class</button>
        </form>
    </div>

</div>
</body>
</html>
