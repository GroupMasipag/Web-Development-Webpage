<?php
require_once 'bootstrap.php';
require_login();

$student = null;
$status = '';
$status_class = '';

$sched_stmt = $conn->query("SELECT start_time, end_time FROM attendance_schedule LIMIT 1");
$schedule = $sched_stmt->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_schedule' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        
        $upd = $conn->prepare("UPDATE attendance_schedule SET start_time = ?, end_time = ?");
        $upd->bind_param("ss", $start, $end);
        
        if ($upd->execute()) {
            $status = "Schedule updated successfully.";
            $status_class = "status-success";
            $schedule['start_time'] = $start;
            $schedule['end_time'] = $end;
        } else {
            $status = "Failed to update schedule.";
            $status_class = "status-error";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'record_attendance') {
        $student_number = '';

        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $student_number = trim($_POST['student_number'] ?? '');
        } else {
            $username = $_SESSION['username'];
            $link_stmt = $conn->prepare("SELECT student_number FROM students WHERE student_number = ? OR email = ? LIMIT 1");
            $link_stmt->bind_param("ss", $username, $username);
            $link_stmt->execute();
            $link_result = $link_stmt->get_result()->fetch_assoc();
            
            if ($link_result) {
                $student_number = $link_result['student_number'];
            } else {
                $status = 'Profile not linked. Ensure your login username matches your registered student number.';
                $status_class = 'status-error';
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
                $current_time = philippine_time();
                $today = $current_time->format('Y-m-d');
                $now = $current_time->format('H:i:s');

                $check = $conn->prepare("SELECT attendance_time FROM attendance WHERE student_id = ? AND attendance_date = ? LIMIT 1");
                $check->bind_param("is", $student['id'], $today);
                $check->execute();
                $existing = $check->get_result()->fetch_assoc();

                if ($existing) {
                    $status = 'Attendance already recorded today at ' . date('h:i A', strtotime($existing['attendance_time'])) . '.';
                    $status_class = 'status-info';
                } else {
                    $insert = $conn->prepare("INSERT INTO attendance (student_id, attendance_date, attendance_time) VALUES (?, ?, ?)");
                    $insert->bind_param("iss", $student['id'], $today, $now);
                    $insert->execute();
                    $status = 'Attendance recorded successfully at ' . $current_time->format('h:i A') . '.';
                    $status_class = 'status-success';
                }
            }
        }
    }
}

$is_attendance_open = true;
$schedule_msg = '';

if ($schedule && $schedule['start_time'] && $schedule['end_time'] && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    $now_time = philippine_time()->format('H:i:s');
    if ($now_time < $schedule['start_time'] || $now_time > $schedule['end_time']) {
        $is_attendance_open = false;
        $schedule_msg = "Attendance is currently closed. It is only available from " . date('h:i A', strtotime($schedule['start_time'])) . " to " . date('h:i A', strtotime($schedule['end_time'])) . ".";
    }
}

$page_title = '5 Little Monkeys · Attendance';
include 'includes/app_header.php';
?>
        <section class="dashboard-card">
            <div class="scan-box">
                <h1>Student Attendance</h1>
                <p class="subtitle">Record daily attendance.</p>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="record_attendance">
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <input type="text" name="student_number" id="student_number" placeholder="Enter Student Number" autofocus required>
                        <div class="hint">The cursor is automatically ready for the next student number.</div>
                        
                        <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                            <button class="btn btn-primary" type="submit">Record Attendance</button>
                            <a class="btn btn-secondary" href="student_registration.php">Student Registration</a>
                            <a class="btn btn-secondary" href="attendance_log.php">View Log</a>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 25px; display: flex; flex-direction: column; align-items: center; gap: 15px;">
                            <?php if ($is_attendance_open): ?>
                                <button class="btn btn-primary" type="submit" style="padding: 15px 30px; font-size: 1.1em;">Record My Attendance</button>
                            <?php else: ?>
                                <button class="btn btn-primary" type="button" disabled style="padding: 15px 30px; font-size: 1.1em; background: #999; cursor: not-allowed; border: none; color: #fff; border-radius: 6px;">Record My Attendance</button>
                                <div style="color: #d32f2f; font-weight: bold; font-size: 14px; text-align: center;"><?= h($schedule_msg) ?></div>
                            <?php endif; ?>
                            
                            <a class="btn btn-secondary" href="attendance_log.php" style="padding: 15px 30px; font-size: 1.1em; max-width: 250px; width: 100%;">View My Log</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="admin-panel" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; text-align: left;">
                    <h3 style="margin-bottom: 10px; font-size: 16px; color: #d32f2f;">Admin Controls</h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 10px;">Advanced system operations restricted from standard users.</p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a class="btn btn-secondary" href="manage_users.php" style="padding: 8px 12px; font-size: 12px;">Manage Users</a>
                        <button type="button" class="btn btn-secondary" onclick="openScheduleModal(event)" style="padding: 8px 12px; font-size: 12px; border: none; cursor: pointer;">Set Schedule</button>
                        <a class="btn btn-secondary" href="#" style="padding: 8px 12px; font-size: 12px;">Export Data (CSV)</a>
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
                </section>
            <?php endif; ?>
        </section>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div id="scheduleModal" class="modal-overlay hidden" onclick="closeScheduleModal(event)">
            <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 350px; text-align: left; padding: 30px;">
                <span class="close-btn" onclick="forceCloseScheduleModal()" style="top: 10px; right: 15px;">&times;</span>
                <h2 style="color: #ffffff; margin-bottom: 10px; font-size: 20px; text-align: center;">Attendance Schedule</h2>
                <p style="color: #e0e0e0; margin-bottom: 25px; font-size: 13px; text-align: center;">Set the time window when students can record attendance.</p>
                
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="action" value="update_schedule">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="color: #ffffff; font-size: 14px; font-weight: bold; display: block; margin-bottom: 5px;">Start Time</label>
                        <input type="time" name="start_time" value="<?= h(date('H:i', strtotime($schedule['start_time']))) ?>" required style="width: 100%; padding: 10px; border-radius: 4px; border: none;">
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="color: #ffffff; font-size: 14px; font-weight: bold; display: block; margin-bottom: 5px;">End Time</label>
                        <input type="time" name="end_time" value="<?= h(date('H:i', strtotime($schedule['end_time']))) ?>" required style="width: 100%; padding: 10px; border-radius: 4px; border: none;">
                    </div>
                    
                    <button type="submit" style="width: 100%; padding: 12px; background-color: #ffb703; color: #333333; text-decoration: none; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 16px;">Save Schedule</button>
                </form>
            </div>
        </div>

        <script>
            function openScheduleModal(event) {
                event.preventDefault();
                document.getElementById('scheduleModal').classList.remove('hidden');
            }

            function closeScheduleModal(event) {
                if (event.target === document.getElementById('scheduleModal')) {
                    document.getElementById('scheduleModal').classList.add('hidden');
                }
            }

            function forceCloseScheduleModal() {
                document.getElementById('scheduleModal').classList.add('hidden');
            }

            <?php if (!$student): ?>
                document.addEventListener('DOMContentLoaded', function () { 
                    const input = document.getElementById('student_number'); 
                    if (input) { input.focus(); input.select(); } 
                });
            <?php endif; ?>
        </script>
    <?php endif; ?>

    <?php if ($student): ?>
        <script> document.addEventListener('DOMContentLoaded', function () { setTimeout(function () { window.location.href = 'dashboard.php'; }, 5000); }); </script>
    <?php endif; ?>
<?php include 'includes/app_footer.php'; ?>