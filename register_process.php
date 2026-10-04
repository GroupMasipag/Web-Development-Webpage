<?php
require_once 'bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('register.php');
}

$student_id = trim($_POST['student_id'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$course = trim($_POST['course'] ?? '');
$year_level = (int) ($_POST['year_level'] ?? 0);

if (
    $student_id === '' ||
    $first_name === '' ||
    $last_name === '' ||
    $email === '' ||
    $password === '' ||
    $course === '' ||
    $year_level === 0
) {
    $_SESSION['error'] = 'Please fill up all fields.';
    redirect_to('register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    redirect_to('register.php');
}

if (!preg_match('/^[A-Za-z0-9_-]+$/', $student_id)) {
    $_SESSION['error'] = 'Student ID may contain only letters, numbers, hyphen, and underscore.';
    redirect_to('register.php');
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Password must contain at least 6 characters.';
    redirect_to('register.php');
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = 'Passwords do not match.';
    redirect_to('register.php');
}

$check_sql = "
    SELECT id
    FROM students
    WHERE student_number = ?
       OR student_id = ?
       OR email = ?
    LIMIT 1
";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('sss', $student_id, $student_id, $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error'] = 'Student ID or Email already exists.';
    redirect_to('register.php');
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$year_section_course = $course . ' - Year ' . $year_level;
$picture = '';

$insert_sql = "
    INSERT INTO students (
        student_number,
        student_id,
        first_name,
        last_name,
        email,
        password,
        course,
        year_level,
        year_section_course,
        picture
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param(
    'sssssssiss',
    $student_id,
    $student_id,
    $first_name,
    $last_name,
    $email,
    $hashed_password,
    $course,
    $year_level,
    $year_section_course,
    $picture
);

if ($insert_stmt->execute()) {
    $_SESSION['success'] = 'Registration successful! You can now log in.';
} else {
    $_SESSION['error'] = 'Something went wrong. Please try again.';
}

redirect_to('register.php');
?>
