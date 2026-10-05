<?php
require_once 'bootstrap.php';
require_login();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect_to('dashboard.php');
}

$status = '';
$status_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
        $class_name = trim($_POST['class_name'] ?? '');
        $schedule_date = $_POST['schedule_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';

        if (empty($schedule_date) || empty($start_time) || empty($end_time)) {
            $status = "Date, Start Time, and End Time are required.";
            $status_class = "status-error";
        } elseif ($start_time >= $end_time) {
            $status = "Start Time must be earlier than End Time.";
            $status_class = "status-error";
        } else {
            $stmt = $conn->prepare("INSERT INTO attendance_schedule (class_name, schedule_date, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $class_name, $schedule_date, $start_time, $end_time);
            
            if ($stmt->execute()) {
                $status = "Schedule successfully added.";
                $status_class = "status-success";
            } else {
                $status = "Failed to add schedule.";
                $status_class = "status-error";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_schedule') {
        $delete_id = (int)$_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM attendance_schedule WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $status = "Schedule deleted.";
            $status_class = "status-success";
        }
    }
}

$schedules = [];
$result = $conn->query("SELECT * FROM attendance_schedule ORDER BY schedule_date DESC, start_time ASC");
if ($result) {
    $schedules = $result->fetch_all(MYSQLI_ASSOC);
}

$page_title = '5 Little Monkeys · Manage Schedules';
include 'includes/app_header.php';
?>
        <section class="dashboard-card">
            <h1>Class Schedules</h1>
            <p class="subtitle">Set up attendance time windows for specific dates and classes.</p>

            <?php if ($status): ?>
                <div class="attendance-status <?= h($status_class) ?>" style="margin-bottom: 20px;">
                    <?= h($status) ?>
                </div>
            <?php endif; ?>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #ddd;">
                <h3 style="margin-bottom: 15px; font-size: 16px; color: #333;">Add New Schedule</h3>
                <form method="POST" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <input type="hidden" name="action" value="add_schedule">
                    
                    <div style="flex: 1; min-width: 200px;">
                        <label style="font-size: 12px; font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Class Name (Optional)</label>
                        <input type="text" name="class_name" placeholder="e.g. IT301 Programming" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div style="flex: 1; min-width: 150px;">
                        <label style="font-size: 12px; font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Date</label>
                        <input type="date" name="schedule_date" required value="<?= date('Y-m-d') ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div style="flex: 1; min-width: 120px;">
                        <label style="font-size: 12px; font-weight: bold; color: #555; display: block; margin-bottom: 5px;">Start Time</label>
                        <input type="time" name="start_time" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>

                    <div style="flex: 1; min-width: 120px;">
                        <label style="font-size: 12px; font-weight: bold; color: #555; display: block; margin-bottom: 5px;">End Time</label>
                        <input type="time" name="end_time" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    
                    <div>
                        <button type="submit" class="btn btn-primary" style="padding: 9px 20px;">Add Schedule</button>
                    </div>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Class Name</th>
                            <th>Time Window</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$schedules): ?>
                            <tr><td colspan="4" class="center">No schedules found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $row): ?>
                                <tr>
                                    <td><?= h(date('M d, Y', strtotime($row['schedule_date']))) ?></td>
                                    <td><?= h($row['class_name'] ?: '—') ?></td>
                                    <td><?= h(date('h:i A', strtotime($row['start_time']))) ?> - <?= h(date('h:i A', strtotime($row['end_time']))) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this schedule?');" style="margin: 0;">
                                            <input type="hidden" name="action" value="delete_schedule">
                                            <input type="hidden" name="delete_id" value="<?= h($row['id']) ?>">
                                            <button type="submit" style="padding: 5px 10px; background: #d32f2f; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a class="btn btn-secondary" href="dashboard.php">Back to Dashboard</a>
            </div>
        </section>
<?php include 'includes/app_footer.php'; ?>