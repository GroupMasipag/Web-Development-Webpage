<?php
session_start();
require 'config.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'login') {$username = $conn->real_escape_string($_POST['username']);
        $password =$conn->real_escape_string($_POST['password']);$query = "SELECT * FROM Login WHERE Username='$username' AND Password='$password'";
        $result = $conn->query($query);
        
        if ($result->num_rows > 0) {
            $user =$result->fetch_assoc();
            $_SESSION['admin_id'] =$user['Id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    } elseif (isset($_POST['action']) &&$_POST['action'] == 'register') {
        $username =$conn->real_escape_string($_POST['reg_username']);$password = $conn->real_escape_string($_POST['reg_password']);
        
        $check =$conn->query("SELECT * FROM Login WHERE Username='$username'");
        if ($check->num_rows > 0) {$error = "Username already exists.";
        } else {
            $conn->query("INSERT INTO Login (Username, Password) VALUES ('$username', '$password')");
            $success = "Account created. You can now sign in.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register · Fixed</title>
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
                
                <?php if($error) echo "<div class='msg'>$error</div>"; ?>
                <?php if($success) echo "<div style='color:#4caf50;' class='msg'>$success</div>"; ?>
                
                <form action="login.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="input-group">
                        <input type="text" name="username" placeholder="Student ID / Email" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" maxlength="10" required>
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
                
                <form action="login.php" method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="input-group">
                        <input type="text" name="reg_username" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <input type="password" name="reg_password" placeholder="Password (Max 10 chars)" maxlength="10" required>
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
    </script>
</body>
</html>