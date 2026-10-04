<?php
require_once 'session_setup.php';
session_start();
require 'config.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Admin Check ( temporary )
        if ($username === 'admin' && $password === 'gianmark123') {
            $_SESSION['admin_id'] = 9999; 
            $_SESSION['username'] = 'Administrator';
            $_SESSION['role'] = 'admin';
            header("Location: dashboard.php");
            exit();
        }

        // Normal User DB Check
        $stmt = $conn->prepare("SELECT Id, Username, Password, Role FROM Login WHERE Username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $valid_password = false;
        if ($user) {
            $stored_password = $user['Password'];
            $valid_password = password_verify($password, $stored_password) || hash_equals($stored_password, $password);
        }

        if ($user && $valid_password) {
            if (!password_get_info($user['Password'])['algo']) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE Login SET Password = ? WHERE Id = ?");
                $update->bind_param("si", $new_hash, $user['Id']);
                $update->execute();
            }

            $_SESSION['admin_id'] = $user['Id'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'register') {
        $username = trim($_POST['reg_username'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($username) < 3 || strlen($username) > 80 || (!filter_var($username, FILTER_VALIDATE_EMAIL) && !preg_match('/^[A-Za-z0-9_.-]+$/', $username))) {
            $error = "Student ID / Email must be a valid email address or use letters, numbers, dot, underscore, or hyphen.";
        } elseif (strlen($password) < 8) {
            $error = "Password must contain at least 8 characters.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $check = $conn->prepare("SELECT Id FROM Login WHERE Username = ? LIMIT 1");
            $check->bind_param("s", $username);
            $check->execute();
            $existing = $check->get_result();

            if ($existing->num_rows > 0) {
                $error = "Username already exists.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conn->prepare("INSERT INTO Login (Username, Password) VALUES (?, ?)");
                $insert->bind_param("ss", $username, $password_hash);
                $insert->execute();
                $success = "Account created. You can now sign in.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>5 Little Monkeys · Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-container">
        <div class="left-panel">
            <div class="left-content" id="left-info">
                <h3>Welcome!</h3>
                <p>Access your portal and manage your account securely with our integrated dashboard system.</p>
                <button class="toggle-btn" onclick="switchForm('register')">Register</button>
            </div>
        </div>

        <div class="right-panel">
            <div class="form-box" id="login-form">
                <div class="form-header">
                    <div class="logo-placeholder">
                        <img src="images/purple star.jpg" alt="Logo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h2>5 Little Monkeys</h2>
                    <p>Sign in to start your session</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="msg"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="msg success-msg"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <form action="Login.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Student ID / Email" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="action-btn">Sign In</button>
                    <a href="#" class="forgot-link">I forgot my password</a>
                </form>
            </div>

            <div class="form-box hidden" id="register-form">
                <div class="form-header">
                    <div class="logo-placeholder">
                        <img src="images/purple star.jpg" alt="Logo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h2>Create Account</h2>
                    <p>Fill in the details to register</p>
                </div>
                
                <form action="Login.php" method="POST" id="registerAccountForm">
                    <input type="hidden" name="action" value="register">
                    <div class="input-group">
                        <input type="text" name="reg_username" placeholder="Student ID / Email" required>
                    </div>
                    <div class="input-group">
                        <input type="password" id="reg_password" name="reg_password" placeholder="Password" minlength="8" required>
                    </div>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" minlength="8" required>
                        <div id="passwordMatch" class="field-validation"></div>
                    </div>
                    <a href="#" class="text-link" onclick="switchForm('login')">Already have an account? Sign in!</a>
                    <button type="submit" class="action-btn" style="margin-top: 15px;">Register</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchForm(target) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const leftInfo = document.getElementById('left-info');

            loginForm.classList.add('hidden');
            registerForm.classList.add('hidden');

            if (target === 'register') {
                registerForm.classList.remove('hidden');
                leftInfo.innerHTML = `
                    <h3>Join Us Today!</h3>
                    <p>Create an account to unlock full access to all features and portal tools.</p>
                    <button class="toggle-btn" onclick="switchForm('login')">Sign In</button>
                `;
            } else {
                loginForm.classList.remove('hidden');
                leftInfo.innerHTML = `
                    <h3>Welcome Back!</h3>
                    <p>Access your portal and manage your account securely with our integrated dashboard system.</p>
                    <button class="toggle-btn" onclick="switchForm('register')">Register</button>
                `;
            }
        }

        const passwordInput = document.getElementById('reg_password');
        const confirmInput = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        const registerForm = document.getElementById('registerAccountForm');

        function updatePasswordMatch() {
            if (confirmInput.value === '') {
                passwordMatch.textContent = '';
                passwordMatch.className = 'field-validation';
                return;
            }

            if (passwordInput.value === confirmInput.value) {
                passwordMatch.textContent = 'Passwords match.';
                passwordMatch.className = 'field-validation match-success';
            } else {
                passwordMatch.textContent = 'Passwords do not match.';
                passwordMatch.className = 'field-validation match-error';
            }
        }

        passwordInput.addEventListener('input', updatePasswordMatch);
        confirmInput.addEventListener('input', updatePasswordMatch);

        registerForm.addEventListener('submit', function (event) {
            const matches = passwordInput.value === confirmInput.value;

            if (!matches) {
                event.preventDefault();
                updatePasswordMatch();
                confirmInput.focus();
            }
        });
    </script>
</body>
</html>
