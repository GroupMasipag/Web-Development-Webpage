<?php
require_once 'bootstrap.php';

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>5 Little Monkeys · Student Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="register-page">
    <main class="register-card">
        <h2>Student Registration</h2>
        <p class="subtitle">Fill up the form to create your account</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <form method="post" action="register_process.php" autocomplete="off">
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" id="student_id" name="student_id" maxlength="50" placeholder="e.g. 2024-0001" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" maxlength="80" placeholder="Juan" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" maxlength="80" placeholder="Dela Cruz" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="120" placeholder="juan@email.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" placeholder="Minimum 6 characters" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" placeholder="Re-enter your password" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="course">Course</label>
                    <select id="course" name="course" required>
                        <option value="">-- Select Course --</option>
                        <option value="BSIT">BSIT - Information Technology</option>
                        <option value="BSCS">BSCS - Computer Science</option>
                        <option value="BSIS">BSIS - Information Systems</option>
                        <option value="BSECE">BSECE - Electronics Engineering</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level" required>
                        <option value="">-- Select Year --</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="register-submit">Register</button>
            <a href="Login.php" class="register-back">Back to Login</a>
        </form>
    </main>
</body>
</html>
