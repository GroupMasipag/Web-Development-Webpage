<?php
$page_title = $page_title ?? '5 Little Monkeys';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app-body">
    <header class="topbar">
        <div class="brand">5 Little Monkeys</div>
        <nav>
            <a href="dashboard.php">Attendance</a>
            <a href="student_registration.php">Student Registration</a>
            <a href="attendance_log.php">Attendance Log</a>
            <a href="Logout.php">Logout</a>
        </nav>
    </header>

    <main class="page">
