<?php
require 'config.php';

$result = $conn->query("SELECT * FROM students");
if ($result) {
    echo " Connected successfully to database: student_system<br>";
    echo " Table 'students' exists and is ready!";
} else {
    echo " Error: " . $conn->error;
}
?>