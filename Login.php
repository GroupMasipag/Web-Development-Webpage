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

        if ($username === 'admin' && $password === 'gianmark123') {
            $_SESSION['admin_id'] = 9999;
            $_SESSION['username'] = 'Administrator';
            $_SESSION['role'] = 'admin';
            header("Location: dashboard.php");
            exit();
        }

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
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $student_number = trim($_POST['reg_username'] ?? '');
        $year_section_course = trim($_POST['year_section_course'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Backend Validation Checks
        if (empty($first_name) || empty($last_name) || empty($student_number) || empty($year_section_course) || empty($password)) {
            $error = "Please complete all required fields.";
        } elseif (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $first_name)) {
            $error = "First Name may only contain letters, spaces, hyphens, apostrophes, and periods.";
        } elseif (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $last_name)) {
            $error = "Last Name may only contain letters, spaces, hyphens, apostrophes, and periods.";
        } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $student_number)) {
            $error = "Student Number may contain only letters, numbers, hyphens, and underscores.";
        } elseif (!preg_match('/^[A-Za-z0-9\s-]+$/', $year_section_course)) {
            $error = "Year & Section / Course may contain only letters, numbers, spaces, and hyphens.";
        } elseif (strlen($password) < 8 || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $error = "Password must contain at least 8 characters and 1 special character.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!isset($_FILES['picture']) || $_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
            $error = "Please upload a student picture.";
        } elseif ($_FILES['picture']['size'] > 2 * 1024 * 1024) {
            $error = "Picture size must not exceed 2 MB.";
        } else {
            $tmp = $_FILES['picture']['tmp_name'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            $allowed_mimes = ['image/jpeg', 'image/png'];

            if (!in_array($mime, $allowed_mimes)) {
                $error = "Only JPEG/JPG and PNG pictures are allowed.";
            } else {
                $check_login = $conn->prepare("SELECT Id FROM Login WHERE Username = ? LIMIT 1");
                $check_login->bind_param("s", $student_number);
                $check_login->execute();

                $check_student = $conn->prepare("SELECT id FROM students WHERE student_number = ? LIMIT 1");
                $check_student->bind_param("s", $student_number);
                $check_student->execute();

                if ($check_login->get_result()->num_rows > 0 || $check_student->get_result()->num_rows > 0) {
                    $error = "That Student ID is already registered.";
                } else {
                    $ext = ($mime === 'image/png') ? '.png' : '.jpg';
                    $filename = $student_number . $ext;
                    $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $destination = $upload_dir . DIRECTORY_SEPARATOR . $filename;

                    if (!move_uploaded_file($tmp, $destination)) {
                        $error = "Unable to save the uploaded picture.";
                    } else {
                        $picture_path = 'uploads/' . $filename;
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);

                        $conn->begin_transaction();
                        try {
                            $insert_login = $conn->prepare("INSERT INTO Login (Username, Password) VALUES (?, ?)");
                            $insert_login->bind_param("ss", $student_number, $password_hash);
                            $insert_login->execute();

                            $insert_student = $conn->prepare("INSERT INTO students (first_name, last_name, student_number, year_section_course, picture) VALUES (?, ?, ?, ?, ?)");
                            $insert_student->bind_param("sssss", $first_name, $last_name, $student_number, $year_section_course, $picture_path);
                            $insert_student->execute();

                            $conn->commit();
                            $success = "Account & Student Profile created successfully! You can now sign in.";
                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = "Registration failed: " . $e->getMessage();
                        }
                    }
                }
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
    <title>5 Little Monkeys · Login & Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-box {
            max-height: 85vh;
            overflow-y: auto;
            padding-right: 12px;
        }
        .form-box::-webkit-scrollbar {
            width: 6px;
        }
        .form-box::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
        }

        /* INPUT FIELD HEIGHT & PADDING */
        .input-group input {
            padding: 14px 16px !important;
            font-size: 14px;
            box-sizing: border-box;
            width: 100%;
        }

        /* STRICT UNIFORM SPACING FOR ALL ROWS */
        .field-group {
            margin-bottom: 16px !important;
            position: relative;
            text-align: left;
        }

        /* TWO-COLUMN ROW EXACT FIT */
        .form-row {
            display: flex;
            gap: 12px;
            margin-bottom: 16px !important;
        }
        .form-row .field-col {
            flex: 1;
            position: relative;
        }

        /* ERROR NOTIFICATION - ABSOLUTE POSITIONING TO NOT AFFECT HEIGHT */
        .field-error-notif {
            display: none;
            position: absolute;
            left: 0;
            top: 100%;
            z-index: 10;
            font-size: 11px;
            color: #ff6b6b;
            background: rgba(40, 10, 30, 0.95);
            border-left: 3px solid #ff6b6b;
            padding: 4px 8px;
            border-radius: 4px;
            margin-top: 2px;
            font-weight: 500;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            white-space: nowrap;
        }

        .input-error {
            border: 1px solid #ff6b6b !important;
            box-shadow: 0 0 5px rgba(255, 107, 107, 0.5);
        }

        .file-input-wrapper {
            text-align: left;
            margin-top: 16px;
            margin-bottom: 16px;
            background: rgba(255, 255, 255, 0.1);
            padding: 14px;
            border-radius: 8px;
            border: 1px dashed rgba(255, 255, 255, 0.4);
            position: relative;
        }
        .file-input-wrapper label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #fff;
        }
        .file-input-wrapper input[type="file"] {
            color: #fff;
            font-size: 12px;
            width: 100%;
        }
        .img-preview {
            display: none;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 10px;
            border: 2px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* PASSWORD WRAPPER & EYE ICON */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        .password-wrapper input {
            width: 100%;
            padding-right: 42px !important;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.7);
            transition: color 0.2s ease;
            z-index: 5;
        }
        .toggle-password:hover {
            color: #ffffff;
        }
        .toggle-password svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
    </style>
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
            <!-- LOGIN FORM -->
            <div class="form-box" id="login-form">
                <div class="form-header">
                    <div class="logo-placeholder">
                        <img src="images/purple star.jpg" alt="Logo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h2>5 Little Monkeys</h2>
                    <p>Sign in to start your session</p>
                </div>
                
                <?php if ($error && (!isset($_POST['action']) || $_POST['action'] == 'login')): ?>
                    <div class="msg"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="msg success-msg"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <form action="Login.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="field-group">
                        <div class="input-group">
                            <input type="text" name="username" placeholder="Student ID / Email" required>
                        </div>
                    </div>
                    <div class="field-group">
                        <div class="input-group">
                            <div class="password-wrapper">
                                <input type="password" id="login_password" name="password" placeholder="Password" required>
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('login_password', this)" title="Toggle Password">
                                    <svg class="eye-icon" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="action-btn">Sign In</button>
                    <a href="#" class="forgot-link" onclick="alert('Please contact the System Administrator to reset your password.'); return false;">I forgot my password</a>
                </form>
            </div>

            <!-- REGISTER FORM -->
            <div class="form-box hidden" id="register-form">
                <div class="form-header">
                    <div class="logo-placeholder">
                        <img src="images/purple star.jpg" alt="Logo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h2>Create Account</h2>
                    <p>Fill in the details to register as a student</p>
                </div>

                <?php if ($error && isset($_POST['action']) && $_POST['action'] == 'register'): ?>
                    <div class="msg"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form action="Login.php" method="POST" enctype="multipart/form-data" id="registerAccountForm">
                    <input type="hidden" name="action" value="register">

                    <!-- First Name and Last Name Side-by-Side Row -->
                    <div class="form-row">
                        <div class="field-col">
                            <div class="input-group">
                                <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                            </div>
                            <div class="field-error-notif" id="err_first_name">Letters, spaces, hyphens only.</div>
                        </div>

                        <div class="field-col">
                            <div class="input-group">
                                <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                            </div>
                            <div class="field-error-notif" id="err_last_name">Letters, spaces, hyphens only.</div>
                        </div>
                    </div>

                    <!-- Student Number -->
                    <div class="field-group">
                        <div class="input-group">
                            <input type="text" id="reg_username" name="reg_username" placeholder="Student Number" required>
                        </div>
                        <div class="field-error-notif" id="err_reg_username">Letters, numbers, hyphens, underscores only.</div>
                    </div>

                    <!-- Year & Section / Course -->
                    <div class="field-group">
                        <div class="input-group">
                            <input type="text" id="year_section_course" name="year_section_course" placeholder="Year & Section / Course (e.g. BSIT 3-A)" required>
                        </div>
                        <div class="field-error-notif" id="err_year_section_course">Letters, numbers, spaces, hyphens only.</div>
                    </div>

                    <!-- Password -->
                    <div class="field-group">
                        <div class="input-group">
                            <div class="password-wrapper">
                                <input type="password" id="reg_password" name="reg_password" placeholder="Password" minlength="8" required>
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('reg_password', this)" title="Toggle Password">
                                    <svg class="eye-icon" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="field-error-notif" id="err_reg_password">Min 8 chars with 1 special char.</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="field-group">
                        <div class="input-group">
                            <div class="password-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" minlength="8" required>
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)" title="Toggle Password">
                                    <svg class="eye-icon" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="field-error-notif" id="err_confirm_password">Passwords do not match.</div>
                    </div>

                    <!-- Profile Picture Upload -->
                    <div class="file-input-wrapper">
                        <label for="picture">Upload Profile Picture (JPEG/PNG, max 2MB):</label>
                        <input type="file" id="picture" name="picture" accept="image/jpeg,image/png,.jpg,.jpeg,.png" required>
                        <div class="field-error-notif" id="err_picture" style="margin-top: 6px;">Upload a valid JPEG/PNG picture under 2MB.</div>
                        <img id="preview" class="img-preview" alt="Picture preview">
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

        // Toggle Password Visibility (Eye Icon)
        function togglePasswordVisibility(inputId, buttonBtn) {
            const input = document.getElementById(inputId);
            const svg = buttonBtn.querySelector('svg');

            if (input.type === 'password') {
                input.type = 'text';
                svg.innerHTML = '<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.17c0-1.66-1.34-3-3-3l-.17.02z"/>';
            } else {
                input.type = 'password';
                svg.innerHTML = '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>';
            }
        }

        // Image Preview Script
        document.getElementById('picture').addEventListener('change', function (event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview');

            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
                preview.removeAttribute('src');
            }
        });

        // REAL-TIME VALIDATION NOTIFICATIONS
        const nameRegex = /^[a-zA-Z\s\-\'\.]*$/;
        const studentNumRegex = /^[A-Za-z0-9_-]*$/;
        const yearCourseRegex = /^[A-Za-z0-9\s-]*$/;
        const passwordRegex = /^(?=.*[^a-zA-Z0-9]).{8,}$/;

        function validateField(inputEl, notifEl, regexCondition) {
            const val = inputEl.value;
            if (val.length > 0 && !regexCondition.test(val)) {
                notifEl.style.display = 'block';
                inputEl.classList.add('input-error');
                return false;
            } else {
                notifEl.style.display = 'none';
                inputEl.classList.remove('input-error');
                return true;
            }
        }

        // Event Listeners for Live Validation
        const firstNameInput = document.getElementById('first_name');
        const errFirstName = document.getElementById('err_first_name');
        firstNameInput.addEventListener('input', () => validateField(firstNameInput, errFirstName, nameRegex));

        const lastNameInput = document.getElementById('last_name');
        const errLastName = document.getElementById('err_last_name');
        lastNameInput.addEventListener('input', () => validateField(lastNameInput, errLastName, nameRegex));

        const regUsernameInput = document.getElementById('reg_username');
        const errRegUsername = document.getElementById('err_reg_username');
        regUsernameInput.addEventListener('input', () => validateField(regUsernameInput, errRegUsername, studentNumRegex));

        const yearCourseInput = document.getElementById('year_section_course');
        const errYearCourse = document.getElementById('err_year_section_course');
        yearCourseInput.addEventListener('input', () => validateField(yearCourseInput, errYearCourse, yearCourseRegex));

        const passwordInput = document.getElementById('reg_password');
        const errPassword = document.getElementById('err_reg_password');
        passwordInput.addEventListener('input', () => validateField(passwordInput, errPassword, passwordRegex));

        const confirmInput = document.getElementById('confirm_password');
        const errConfirm = document.getElementById('err_confirm_password');

        function validateConfirmPassword() {
            if (confirmInput.value.length > 0 && confirmInput.value !== passwordInput.value) {
                errConfirm.style.display = 'block';
                confirmInput.classList.add('input-error');
                return false;
            } else {
                errConfirm.style.display = 'none';
                confirmInput.classList.remove('input-error');
                return true;
            }
        }

        confirmInput.addEventListener('input', validateConfirmPassword);
        passwordInput.addEventListener('input', () => {
            if (confirmInput.value.length > 0) validateConfirmPassword();
        });

        // Form Submit Validation Block
        document.getElementById('registerAccountForm').addEventListener('submit', function (event) {
            const v1 = validateField(firstNameInput, errFirstName, nameRegex);
            const v2 = validateField(lastNameInput, errLastName, nameRegex);
            const v3 = validateField(regUsernameInput, errRegUsername, studentNumRegex);
            const v4 = validateField(yearCourseInput, errYearCourse, yearCourseRegex);
            const v5 = validateField(passwordInput, errPassword, passwordRegex);
            const v6 = validateConfirmPassword();

            if (!v1 || !v2 || !v3 || !v4 || !v5 || !v6) {
                event.preventDefault();
            }
        });

        <?php if (isset($_POST['action']) && $_POST['action'] == 'register' &&$error): ?>
            switchForm('register');
        <?php endif; ?>
    </script>
</body>
</html> 