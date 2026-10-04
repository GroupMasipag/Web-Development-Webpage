<?php
date_default_timezone_set('Asia/Manila');

# Database configuration -- main project database
$host = 'localhost';
$db   = 'student_system';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
$conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($db);
$conn->query("SET time_zone = '+08:00'");

function ensure_database_schema(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS Login (
            Id INT AUTO_INCREMENT PRIMARY KEY,
            Username VARCHAR(80) NOT NULL UNIQUE,
            Password VARCHAR(255) NOT NULL,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("ALTER TABLE Login MODIFY Username VARCHAR(80) NOT NULL");
    $conn->query("ALTER TABLE Login MODIFY Password VARCHAR(255) NOT NULL");

    if (!table_has_index($conn, 'Login', 'unique_username')) {
        $conn->query("ALTER TABLE Login ADD UNIQUE KEY unique_username (Username)");
    }

    if (!table_has_column($conn, 'Login', 'CreatedAt')) {
        $conn->query("ALTER TABLE Login ADD COLUMN CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS students (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            student_number VARCHAR(50) NOT NULL UNIQUE,
            student_id VARCHAR(50) NULL UNIQUE,
            email VARCHAR(120) NULL UNIQUE,
            password VARCHAR(255) NULL,
            course VARCHAR(120) NULL,
            year_level TINYINT UNSIGNED NULL,
            year_section_course VARCHAR(150) NOT NULL,
            picture VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    if (!table_has_column($conn, 'students', 'student_id')) {
        $conn->query("ALTER TABLE students ADD COLUMN student_id VARCHAR(50) NULL UNIQUE AFTER student_number");
    }

    if (!table_has_column($conn, 'students', 'email')) {
        $conn->query("ALTER TABLE students ADD COLUMN email VARCHAR(120) NULL UNIQUE AFTER student_id");
    }

    if (!table_has_column($conn, 'students', 'password')) {
        $conn->query("ALTER TABLE students ADD COLUMN password VARCHAR(255) NULL AFTER email");
    }

    if (!table_has_column($conn, 'students', 'course')) {
        $conn->query("ALTER TABLE students ADD COLUMN course VARCHAR(120) NULL AFTER password");
    }

    if (!table_has_column($conn, 'students', 'year_level')) {
        $conn->query("ALTER TABLE students ADD COLUMN year_level TINYINT UNSIGNED NULL AFTER course");
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS attendance (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            attendance_date DATE NOT NULL,
            attendance_time TIME NOT NULL,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_date (student_id, attendance_date),
            CONSTRAINT fk_attendance_student
                FOREIGN KEY (student_id) REFERENCES students(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function table_has_column(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) $row['total'] > 0;
}

function table_has_index(mysqli $conn, string $table, string $index): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
    ");
    $stmt->bind_param("ss", $table, $index);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) $row['total'] > 0;
}

ensure_database_schema($conn);
?>
