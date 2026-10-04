<?php
require_once 'bootstrap.php';
require_login();

$student = null;
$status = '';
$status_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = trim($_POST['student_number'] ?? '');

    if ($student_number === '') {
        $status = 'Please enter a student number.';
        $status_class = 'status-error';
    } else {
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

$page_title = '5 Little Monkeys · Attendance';
include 'includes/app_header.php';
?>
        <section class="dashboard-card">
            <div class="scan-box">
                <h1>Student Attendance</h1>
                <p class="subtitle">Enter the student number and press Enter.</p>

                <form method="post" autocomplete="off">
                    <input
                        type="text"
                        name="student_number"
                        id="student_number"
                        placeholder="Enter Student Number"
                        autofocus
                        required
                    >
                    <div class="hint">The cursor is automatically ready for the next student number.</div>
                    
                    <!-- Buttons visible to ALL logged-in users -->
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                        <button class="btn btn-primary" type="submit">Record Attendance</button>
                        <a class="btn btn-secondary" href="student_registration.php">Student Registration</a>
                        <a class="btn btn-secondary" href="attendance_log.php">View Log</a>
                    </div>
                </form>
            </div>

            <!-- Admin Controls: Visible ONLY to Admins -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="admin-panel" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; text-align: left;">
                    <h3 style="margin-bottom: 10px; font-size: 16px; color: #d32f2f;">Admin Controls</h3>
                    <p style="font-size: 12px; color: #666; margin-bottom: 10px;">Advanced system operations restricted from standard users.</p>
                    <div style="display: flex; gap: 10px;">
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
                    <div class="student-name">
                        <?= h($student['last_name']) ?>, <?= h($student['first_name']) ?>
                    </div>

                    <div class="picture-frame">
                        <?php if (!empty($student['picture'])): ?>
                            <img src="<?= h($student['picture']) ?>" alt="Student Picture">
                        <?php else: ?>
                            <div class="picture-placeholder">No Picture</div>
                        <?php endif; ?>
                    </div>

                    <div class="student-field">
                        <?= h($student['student_number']) ?>
                    </div>

                    <div class="student-field">
                        <?= h($student['year_section_course']) ?>
                    </div>

                    <div class="student-field">
                        <?= h(philippine_time()->format('F d, Y')) ?>
                        &nbsp;
                        <?= h(philippine_time()->format('h:i:s A')) ?>
                    </div>
                </section>
            <?php endif; ?>
        </section>

    <?php if ($student): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(function () {
                    window.location.href = 'dashboard.php';
                }, 5000);
            });
        </script>
    <?php else: ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const input = document.getElementById('student_number');
                if (input) {
                    input.focus();
                    input.select();
                }
            });
        </script>
    <?php endif; ?>
<?php include 'includes/app_footer.php'; ?>