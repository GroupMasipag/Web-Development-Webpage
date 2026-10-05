<?php
date_default_timezone_set('Asia/Manila');

$host = 'localhost';$db   = 'student_system';
$user = 'root';$pass = '';

$conn = new mysqli($host, $user,$pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');$conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($db);$conn->query("SET time_zone = '+08:00'");
?>