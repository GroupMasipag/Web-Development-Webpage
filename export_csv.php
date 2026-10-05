<?php
require_once 'bootstrap.php';
require_login();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect_to('dashboard.php');
}

$query = "
    SELECT 
        s.student_number, 
        s.first_name, 
        s.last_name, 
        s.year_section_course, 
        a.attendance_date, 
        a.attendance_time 
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    ORDER BY a.attendance_date DESC, a.attendance_time DESC
";

$result = $conn->query($query);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance_log_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, ['Student Number', 'First Name', 'Last Name', 'Year/Section/Course', 'Date', 'Time']);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['student_number'],
            $row['first_name'],
            $row['last_name'],
            $row['year_section_course'],
            $row['attendance_date'],
            date('h:i:s A', strtotime($row['attendance_time']))
        ]);
    }
}

fclose($output);
exit();
?>