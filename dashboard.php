<?php
require_once 'bootstrap.php';
require_login();

$student = null;
$status = '';
$status_class = '';

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$current_time = philippine_time();
$today_date = $current_time->format('Y-m-d');
$now_time = $current_time->format('H:i:s');

$is_attendance_open = false;
$active_class_name = '';

$sched_stmt = $conn->prepare("SELECT * FROM attendance_schedule WHERE schedule_date = ? AND ? >= start_time AND ? <= end_time LIMIT 1");
$sched_stmt->bind_param("sss", $today_date, $now_time, $now_time);
$sched_stmt->execute();
$active_schedule = $sched_stmt->get_result()->fetch_assoc();

if ($active_schedule) {
    $is_attendance_open = true;
    $active_class_name = $active_schedule['class_name'] ? " for " . $active_schedule['class_name'] : "";
} elseif ($is_admin) {
    $is_attendance_open = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_attendance') {
    $student_number = trim($_POST['student_number'] ?? '');

    if (!$is_admin && !empty($student_number)) {
        $username = $_SESSION['username'];
        
        $link_stmt = $conn->prepare("SELECT student_number FROM students WHERE student_number = ? OR email = ? LIMIT 1");
        $link_stmt->bind_param("ss", $username, $username);
        $link_stmt->execute();
        $link_result = $link_stmt->get_result()->fetch_assoc();
        
        if (!$link_result) {
            $check_exist = $conn->prepare("SELECT email FROM students WHERE student_number = ? LIMIT 1");
            $check_exist->bind_param("s", $student_number);
            $check_exist->execute();
            $target_student = $check_exist->get_result()->fetch_assoc();

            if ($target_student) {
                if (empty($target_student['email'])) {
                    $upd = $conn->prepare("UPDATE students SET email = ? WHERE student_number = ?");
                    $upd->bind_param("ss", $username, $student_number);
                    $upd->execute();
                } else {
                    $status = 'Error: This Student Number is already linked to another login account.';
                    $status_class = 'status-error';
                    $student_number = '';
                }
            }
        } elseif ($link_result['student_number'] !== $student_number) {
            $status = 'Error: You are only authorized to record attendance for your own Student Number.';
            $status_class = 'status-error';
            $student_number = '';
        }
        
        if ($student_number !== '' && !$is_attendance_open) {
            $status = 'Attendance is currently closed. There is no active class scheduled at this time.';
            $status_class = 'status-error';
            $student_number = '';
        }
    }

    if ($student_number === '' && empty($status)) {
        $status = 'Please enter a student number.';
        $status_class = 'status-error';
    } elseif (!empty($student_number)) {
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_number = ? LIMIT 1");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();

        if (!$student) {
            $status = 'Student number not found. Please register the student first.';
            $status_class = 'status-error';
        } else {
            if ($active_schedule) {
                $check = $conn->prepare("SELECT attendance_time FROM attendance WHERE student_id = ? AND attendance_date = ? AND attendance_time >= ? AND attendance_time <= ? LIMIT 1");
                $check->bind_param("isss", $student['id'], $today_date, $active_schedule['start_time'], $active_schedule['end_time']);
            } else {
                $one_hour_ago = date('H:i:s', strtotime('-1 hour', strtotime($now_time)));
                $check = $conn->prepare("SELECT attendance_time FROM attendance WHERE student_id = ? AND attendance_date = ? AND attendance_time >= ? LIMIT 1");
                $check->bind_param("iss", $student['id'], $today_date, $one_hour_ago);
            }
            
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();

            if ($existing) {
                $status = 'Attendance already recorded at ' . date('h:i A', strtotime($existing['attendance_time'])) . h($active_class_name) . '.';
                $status_class = 'status-info';
            } else {
                $insert = $conn->prepare("INSERT INTO attendance (student_id, attendance_date, attendance_time) VALUES (?, ?, ?)");
                $insert->bind_param("iss", $student['id'], $today_date, $now_time);
                $insert->execute();
                $status = 'Attendance recorded successfully at ' . date('h:i A', strtotime($now_time)) . h($active_class_name) . '.';
                $status_class = 'status-success';
            }
        }
    }
}

$page_title = '5 Little Monkeys · Attendance';
include 'includes/app_header.php';
?>
        <style>
            .timer-bar-container {
                width: 100%;
                height: 6px;
                background-color: rgba(0, 0, 0, 0.1);
                border-radius: 4px;
                margin-top: 20px;
                overflow: hidden;
            }
            .timer-bar {
                height: 100%;
                width: 100%;
                background-color: #6b7fd7;
                transform-origin: left;
                animation: timerDeplete 5s linear forwards;
            }
            @keyframes timerDeplete {
                0% { transform: scaleX(1); }
                100% { transform: scaleX(0); }
            }
        </style>

        <section class="dashboard-card">
            <div class="scan-box">
                <h1>Student Attendance</h1>
                <p class="subtitle">Record daily attendance.</p>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="record_attendance">
                    
                    <input type="text" name="student_number" id="student_number" placeholder="Enter Student Number" autofocus required <?= !$is_attendance_open ? 'disabled' : '' ?>>
                    <div class="hint">The cursor is automatically ready for the next student number.</div>
                    
                    <?php if (!$is_attendance_open && !$is_admin): ?>
                        <div style="color: #d32f2f; font-weight: bold; font-size: 14px; text-align: center; margin-top: 10px; margin-bottom: 5px;">
                            Attendance is currently closed. There are no active classes scheduled right now.
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                        <?php if ($is_attendance_open): ?>
                            <button class="btn btn-primary" type="submit">Record Attendance</button>
                        <?php else: ?>
                            <button class="btn btn-primary" type="button" disabled style="background: #999; cursor: not-allowed; border: none; color: #fff;">Record Attendance</button>
                        <?php endif; ?>
                        
                        <a class="btn btn-secondary" href="student_registration.php">Student Registration</a>
                        <a class="btn btn-secondary" href="attendance_log.php"><?= $is_admin ? 'View Log' : 'View My Log' ?></a>
                    </div>
                </form>
            </div>

            <?php if ($is_admin): ?>
                <div class="admin-panel" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; text-align: left;">
                    <h3 style="margin-bottom: 10px; font-size: 16px; color: #d32f2f;">Admin Controls</h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 10px;">Advanced system operations restricted from standard users.</p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a class="btn btn-secondary" href="manage_password.php" style="padding: 8px 12px; font-size: 12px;">Manage Users</a>
                        <a class="btn btn-secondary" href="manage_schedule.php" style="padding: 8px 12px; font-size: 12px; background: #6b7fd7; color: white;">Manage Schedules</a>
                        <a class="btn btn-secondary" href="export_csv.php" style="padding: 8px 12px; font-size: 12px;">Export Data (CSV)</a>
                        <a class="btn btn-secondary" href="#" style="padding: 8px 12px; font-size: 12px;">System Settings</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($status): ?>
                <div class="attendance-status <?= h($status_class) ?>">
                    <?= h($status) ?>
                </div>
            <?php endif; ?>

            <?php if ($student): ?>
                <section class="student-card">
                    <div class="student-name"><?= h($student['last_name']) ?>, <?= h($student['first_name']) ?></div>
                    <div class="picture-frame">
                        <?php if (!empty($student['picture'])): ?><img src="<?= h($student['picture']) ?>" alt="Student Picture"><?php else: ?><div class="picture-placeholder">No Picture</div><?php endif; ?>
                    </div>
                    <div class="student-field"><?= h($student['student_number']) ?></div>
                    <div class="student-field"><?= h($student['year_section_course']) ?></div>
                    <div class="student-field"><?= h(philippine_time()->format('F d, Y')) ?> &nbsp; <?= h(philippine_time()->format('h:i:s A')) ?></div>
                    
                    <div class="timer-bar-container">
                        <div class="timer-bar"></div>
                    </div>
                </section>
            <?php endif; ?>
        </section>

    <?php if ($student): ?>
        <script> document.addEventListener('DOMContentLoaded', function () { setTimeout(function () { window.location.href = 'dashboard.php'; }, 5000); }); </script>
    <?php else: ?>
        <script> document.addEventListener('DOMContentLoaded', function () { const input = document.getElementById('student_number'); if (input && !input.disabled) { input.focus(); input.select(); } }); </script>
    <?php endif; ?>
<?php include 'includes/app_footer.php'; ?>