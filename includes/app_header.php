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
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="#" style="color: #ffb703; font-weight: bold;">Admin Settings</a>
            <?php endif; ?>
            
            <a href="#" onclick="openLogoutModal(event)">Logout</a>
        </nav>
    </header>

    <div id="logoutModal" class="modal-overlay hidden" onclick="closeLogoutModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()" style="text-align: center; max-width: 320px; padding: 30px;">
            <h2 style="color: #ffffff; margin-bottom: 10px; font-size: 22px;">Confirm Logout</h2>
            <p style="color: #e0e0e0; margin-bottom: 25px; font-size: 14px;">Are you sure you want to end your session?</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <a href="Logout.php" style="flex: 1; padding: 10px; background-color: #d32f2f; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Logout</a>
                <button type="button" onclick="forceCloseLogoutModal()" style="flex: 1; padding: 10px; background-color: #ffffff; color: #333333; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal(event) {
            event.preventDefault();
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function closeLogoutModal(event) {
            if (event.target === document.getElementById('logoutModal')) {
                document.getElementById('logoutModal').classList.add('hidden');
            }
        }

        function forceCloseLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }
    </script>

    <main class="page">