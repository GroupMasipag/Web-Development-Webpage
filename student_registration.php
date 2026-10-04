<?php
require_once 'bootstrap.php';
require_login();

$error = '';
$success = '';

$first_name = '';
$last_name = '';
$student_number = '';
$course = '';
$year_section = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_section = trim($_POST['year_section'] ?? '');

    if ($first_name === '' || $last_name === '' || $student_number === '' || $course === '' || $year_section === '') {
        $error = 'Please fill out all text fields.';
    } elseif (!isset($_FILES['picture']) || $_FILES['picture']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please upload a student picture.';
    } else {
        $target_dir = "uploads/";
        $imageFileType = strtolower(pathinfo($_FILES["picture"]["name"], PATHINFO_EXTENSION));
        
        $allowed_types = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($imageFileType, $allowed_types)) {
            $error = 'Only JPG, JPEG, and PNG files are allowed.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM students WHERE student_number = ? LIMIT 1");
            $stmt->bind_param("s", $student_number);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Student number already exists.';
            } else {
                $new_filename = $student_number . "." . $imageFileType;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
                    $year_section_course = $course . ' - ' . $year_section;

                    $insert = $conn->prepare("INSERT INTO students (first_name, last_name, student_number, course, year_section_course, picture) VALUES (?, ?, ?, ?, ?, ?)");
                    $insert->bind_param("ssssss", $first_name, $last_name, $student_number, $course, $year_section_course, $target_file);
                    
                    if ($insert->execute()) {
                        $success = 'Student registered successfully!';
                        
                        $first_name = $last_name = $student_number = $course = $year_section = '';
                    } else {
                        $error = 'Database error: ' . $conn->error;
                    }
                } else {
                    $error = 'Sorry, there was an error saving your file.';
                }
            }
        }
    }
}

$page_title = '5 Little Monkeys · Student Registration';
include 'includes/app_header.php';
?>

<section class="dashboard-card" style="max-width: 600px; margin: 0 auto;">
    <div class="scan-box" style="text-align: left;">
        <h1 style="text-align: center;">Register New Student</h1>
        <p class="subtitle" style="text-align: center;">Register the student information and upload a picture.</p>
        
        <?php if ($error): ?>
            <div class="attendance-status status-error" style="margin-bottom: 20px;">
                <?= h($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="attendance-status status-success" style="margin-bottom: 20px;">
                <?= h($success) ?>
            </div>
        <?php endif; ?>

        <form action="student_registration.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">First Name</label>
                <input type="text" name="first_name" value="<?= h($first_name) ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Last Name</label>
                <input type="text" name="last_name" value="<?= h($last_name) ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Student Number</label>
                <input type="text" name="student_number" value="<?= h($student_number) ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
            </div>

            <div class="form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Course (e.g. BSIT)</label>
                    <input type="text" name="course" value="<?= h($course) ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;">Year & Section (e.g. 3-A)</label>
                    <input type="text" name="year_section" value="<?= h($year_section) ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Upload Picture (JPG, JPEG, PNG)</label>
                <input type="file" name="picture" accept=".jpg, .jpeg, .png" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" required>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Register Student</button>
                <a href="dashboard.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/app_footer.php'; ?>