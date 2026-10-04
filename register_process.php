<?php
session_start();
require 'config.php';

// Step 1: Siguraduhing galing sa POST yung request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

// Step 2: Kunin yung data mula sa form
$student_id       = trim($_POST['student_id']);
$first_name       = trim($_POST['first_name']);
$last_name        = trim($_POST['last_name']);
$email            = trim($_POST['email']);
$password         = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$course           = trim($_POST['course']);
$year_level       = intval($_POST['year_level']);

// Step 3: Validation - siguraduhing walang blangko
if (empty($student_id) || empty($first_name) || empty($last_name) || 
    empty($email) || empty($password) || empty($course) || empty($year_level)) {
    $_SESSION['error'] = "Please fill up all fields.";
    header("Location: register.php");
    exit();
}

// Step 4: Siguraduhing pareho yung password at confirm password
if ($password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: register.php");
    exit();
}

// Step 5: Siguraduhing hindi duplicate yung Student ID o Email
$check_sql = "SELECT id FROM students WHERE student_id = ? OR email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ss", $student_id, $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error'] = "Student ID or Email already exists.";
    header("Location: register.php");
    exit();
}
$check_stmt->close();

// Step 6: I-hash yung password (para hindi mabasa kung sakaling may makakuha ng DB)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Step 7: I-insert sa database
$insert_sql = "INSERT INTO students (student_id, first_name, last_name, email, password, course, year_level) 
               VALUES (?, ?, ?, ?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ssssssi", 
    $student_id, 
    $first_name, 
    $last_name, 
    $email, 
    $hashed_password, 
    $course, 
    $year_level
);

// Step 8: Execute at i-check kung success
if ($insert_stmt->execute()) {
    $_SESSION['success'] = "Registration successful! You can now log in.";
    header("Location: register.php");
    exit();
} else {
    $_SESSION['error'] = "Something went wrong. Please try again.";
    header("Location: register.php");
    exit();
}

$insert_stmt->close();
$conn->close();
?>