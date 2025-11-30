<?php
// manage_admins.php
// 管理员管理页面 - 创建新管理员

require_once 'utilities.php';
require_login();

// 检查是否管理员
if ($_SESSION['role_id'] != 3) {
    echo "<p style='color:red'>Access denied: Admin only.</p>";
    exit;
}

$message = '';
$error = '';

// 处理创建新管理员
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 验证输入
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // 检查邮箱是否已存在
        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $result = db_query($check_sql, "s", [$email]);

        if ($result->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            // 创建新管理员 - 使用password_hash()与登录验证匹配
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (name, email, password_hash, role_id) VALUES (?, ?, ?, 3)";
            db_query($insert_sql, "sss", [$name, $email, $password_hash]);
            $message = "Admin account created successfully!";
        }
    }
}

// 获取所有管理员列表
$sql = "SELECT user_id, name, email, created_at FROM users WHERE role_id = 3 ORDER BY created_at DESC";
$admins = db_query($sql);

include_once 'header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Manage Administrators</h2>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 创建新管理员表单 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Create New Administrator</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    <small class="form-text text-muted">Minimum 6 characters</small>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" name="create_admin" class="btn btn-primary">
                    <i class="fa fa-user-plus"></i> Create Admin
                </button>
            </form>
        </div>
    </div>

    <!-- 现有管理员列表 -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Existing Administrators</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($admin = $admins->fetch_assoc()): ?>
                        <tr>
                            <td><?= $admin['user_id'] ?></td>
                            <td><?= htmlspecialchars($admin['name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= $admin['created_at'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
