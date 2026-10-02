<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome to the Dashboard</h1>
    <p>You have successfully logged in.</p>
    <a href="logout.php">Logout</a>
</body>
</html>