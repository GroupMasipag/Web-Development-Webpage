<?php
# Database configuration -- rename db name to student_system
$host = 'localhost';
$db   = 'student_system';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>