<?php
require_once 'bootstrap.php';
require_login();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $year_section_course = trim($_POST['year_section_course'] ?? '');

    if ($first_name === '' || $last_name === '' || $student_number === '' || $year_section_course === '') {
        $error = 'Please complete all required fields.';
    } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $student_number)) {
        $error = 'Student Number may contain only letters, numbers, hyphen, and underscore.';
    } elseif (!isset($_FILES['picture']) || $_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a JPEG picture.';
    } elseif ($_FILES['picture']['size'] > 2 * 1024 * 1024) {
        $error = 'Picture size must not exceed 2 MB.';
    } else {
        $tmp = $_FILES['picture']['tmp_name'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);

        if ($mime !== 'image/jpeg') {
            $error = 'Only JPEG/JPG pictures are allowed.';
        } else {
            $check = $conn->prepare("SELECT id FROM students WHERE student_number = ? LIMIT 1");
            $check->bind_param("s", $student_number);
            $check->execute();

            if ($check->get_result()->fetch_assoc()) {
                $error = 'That student number is already registered.';
            } else {
                $filename = $student_number . '.jpg';
                $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $destination = $upload_dir . DIRECTORY_SEPARATOR . $filename;

                if (!move_uploaded_file($tmp, $destination)) {
                    $error = 'Unable to save the uploaded picture. Check the uploads folder permissions.';
                } else {
                    $picture_path = 'uploads/' . $filename;
                    $stmt = $conn->prepare(
                        "INSERT INTO students (first_name, last_name, student_number, year_section_course, picture)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("sssss", $first_name, $last_name, $student_number, $year_section_course, $picture_path);
                    $stmt->execute();

                    $success = 'Student registered successfully.';
                }
            }
        }
    }
}

$page_title = '5 Little Monkeys · Student Registration';
include 'includes/app_header.php';
?>
        <section class="registration">
            <h1>Student Registration</h1>
            <p class="subtitle">Register the student information and upload a JPEG picture.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= h($success) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" maxlength="80" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" maxlength="80" required>
                </div>

                <div class="form-group">
                    <label for="student_number">Student Number</label>
                    <input type="text" id="student_number" name="student_number" maxlength="50" required>
                </div>

                <div class="form-group">
                    <label for="year_section_course">Year &amp; Section / Course</label>
                    <input type="text" id="year_section_course" name="year_section_course" maxlength="150" placeholder="e.g. BSIT 3-A" required>
                </div>

                <div class="form-group">
                    <label for="picture">Upload Picture (JPEG/JPG, max 2 MB)</label>
                    <input type="file" id="picture" name="picture" accept="image/jpeg,.jpg,.jpeg" required>
                    <img id="preview" class="preview" alt="Picture preview">
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Submit</button>
                    <a class="btn btn-secondary" href="dashboard.php">Cancel</a>
                </div>
            </form>
        </section>

    <script>
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
    </script>
<?php include 'includes/app_footer.php'; ?>
