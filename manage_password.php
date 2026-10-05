<?php
require_once 'bootstrap.php';
require_login();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect_to('dashboard.php');
}

$status = '';
$status_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_id'])) {
    $reset_id = (int)$_POST['reset_id'];
    $new_password = $_POST['new_password'] ?? '';

    if (strlen($new_password) < 8 || !preg_match('/[^a-zA-Z0-9]/', $new_password)) {
        $status = "Error: Password must contain at least 8 characters and 1 special character.";
        $status_class = "status-error";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE Login SET Password = ? WHERE Id = ?");
        $stmt->bind_param("si", $hashed_password, $reset_id);
        
        if ($stmt->execute()) {
            $status = "Password successfully updated.";
            $status_class = "status-success";
        } else {
            $status = "Failed to reset password.";
            $status_class = "status-error";
        }
    }
}

$users = [];
$result = $conn->query("SELECT Id, Username, CreatedAt FROM Login ORDER BY CreatedAt DESC");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}

$page_title = '5 Little Monkeys · Manage Users';
include 'includes/app_header.php';
?>
        <section class="dashboard-card">
            <h1>Manage Users</h1>
            <p class="subtitle">Administrate user accounts and reset passwords.</p>

            <?php if ($status): ?>
                <div class="attendance-status <?= h($status_class) ?>" style="margin-bottom: 20px;">
                    <?= h($status) ?>
                </div>
            <?php endif; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student ID / Email</th>
                            <th>Registered Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$users): ?>
                            <tr><td colspan="4" class="center">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= h($user['Id']) ?></td>
                                    <td><?= h($user['Username']) ?></td>
                                    <td><?= h($user['CreatedAt']) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to change the password for <?= h($user['Username']) ?>?');" style="margin: 0; display: flex; gap: 5px; align-items: center; justify-content: center;">
                                            <input type="hidden" name="reset_id" value="<?= h($user['Id']) ?>">
                                            <input type="text" name="new_password" placeholder="New Password" required minlength="8" style="padding: 6px; width: 140px; font-size: 12px; border-radius: 4px; border: 1px solid #ccc;">
                                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; border: none; cursor: pointer;">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a class="btn btn-secondary" href="dashboard.php">Back to Dashboard</a>
            </div>
        </section>
<?php include 'includes/app_footer.php'; ?>