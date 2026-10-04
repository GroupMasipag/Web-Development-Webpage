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
    $new_password = 'Changeme123!';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE Login SET Password = ? WHERE Id = ?");
    $stmt->bind_param("si", $hashed_password, $reset_id);
    
    if ($stmt->execute()) {
        $status = "Password successfully reset to: " . $new_password;
        $status_class = "status-success";
    } else {
        $status = "Failed to reset password.";
        $status_class = "status-error";
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
                                        <form method="POST" onsubmit="return confirm('Reset password for <?= h($user['Username']) ?> to Changeme123!?');" style="margin: 0;">
                                            <input type="hidden" name="reset_id" value="<?= h($user['Id']) ?>">
                                            <button type="submit" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px; border: none; cursor: pointer;">Reset Password</button>
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