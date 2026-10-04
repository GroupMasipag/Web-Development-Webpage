<?php
session_start();
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            font-family: 'Segoe UI', sans-serif;
        }
        .register-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        .register-card h2 {
            color: #4a3f8f;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }
        .register-card p.subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #ddd;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-back {
            text-decoration: none;
            color: #667eea;
            display: block;
            text-align: center;
            margin-top: 15px;
            font-weight: 600;
        }
        .btn-back:hover {
            color: #4a3f8f;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <h2>📝 Student Registration</h2>
        <p class="subtitle">Fill up the form to create your account</p>

        <?php
        // Ipakita ang error/success message kung meron
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="register_process.php" method="POST">
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" 
                       placeholder="e.g., 2024-0001" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           placeholder="Juan" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           placeholder="Dela Cruz" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="juan@email.com" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Minimum 6 characters" minlength="6" required>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                       placeholder="Re-enter your password" minlength="6" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="course" class="form-label">Course</label>
                    <select class="form-select" id="course" name="course" required>
                        <option value="">-- Select Course --</option>
                        <option value="BSIT">BSIT - Information Technology</option>
                        <option value="BSCS">BSCS - Computer Science</option>
                        <option value="BSIS">BSIS - Information Systems</option>
                        <option value="BSECE">BSECE - Electronics Engineering</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="year_level" class="form-label">Year Level</label>
                    <select class="form-select" id="year_level" name="year_level" required>
                        <option value="">-- Select Year --</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-register">Register</button>
            <a href="index.php" class="btn-back">← Back to Home</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>