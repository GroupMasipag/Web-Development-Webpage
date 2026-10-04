<?php
require_once 'bootstrap.php';
require_login();

$records = [];
$result = $conn->query(
    "SELECT a.attendance_date, a.attendance_time,
            s.first_name, s.last_name, s.student_number, s.year_section_course
     FROM attendance a
     INNER JOIN students s ON s.id = a.student_id
     ORDER BY a.attendance_date DESC, a.attendance_time DESC"
);

if ($result) {
    $records = $result->fetch_all(MYSQLI_ASSOC);
}

$page_title = '5 Little Monkeys · Attendance Log';
include 'includes/app_header.php';
?>
        <section class="dashboard-card">
            <h1>Attendance Log</h1>
            <p class="subtitle">Recorded attendance entries.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Student Number</th>
                            <th>Student Name</th>
                            <th>Year &amp; Section / Course</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$records): ?>
                            <tr><td colspan="5" class="center">No attendance records yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($records as $row): ?>
                                <tr>
                                    <td><?= h($row['attendance_date']) ?></td>
                                    <td><?= h(date('h:i:s A', strtotime($row['attendance_time']))) ?></td>
                                    <td><?= h($row['student_number']) ?></td>
                                    <td><?= h($row['last_name'] . ', ' . $row['first_name']) ?></td>
                                    <td><?= h($row['year_section_course']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
<?php include 'includes/app_footer.php'; ?>
