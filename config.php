<?php
// config.example.php
// Copy this file to config.php and update with your settings



// Database connection settings
$host = "localhost";
$dbname = "academichub";
$username = "root";
$password = ""; // Add your MySQL password here

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>