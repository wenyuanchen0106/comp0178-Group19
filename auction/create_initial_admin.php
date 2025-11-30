<?php
// create_initial_admin.php
// 创建初始管理员账号
// 运行此文件一次后应该删除，防止安全风险

require_once 'utilities.php';

// 管理员信息
$admin_email = 'admin@auction.com';
$admin_password = 'password123';
$admin_name = 'Admin User';

echo "<!DOCTYPE html><html><head><title>Create Initial Admin</title>";
echo "<link rel='stylesheet' href='css/bootstrap.min.css'></head><body>";
echo "<div class='container mt-5'>";

// 检查admin角色是否存在
$check_role_sql = "SELECT role_id FROM roles WHERE role_name = 'admin'";
$role_result = db_query($check_role_sql);

if ($role_result->num_rows === 0) {
    // 创建admin角色
    echo "<div class='alert alert-info'>Creating admin role...</div>";
    $create_role_sql = "INSERT INTO roles (role_id, role_name) VALUES (3, 'admin')";
    db_query($create_role_sql);
    echo "<div class='alert alert-success'>✓ Admin role created (role_id = 3)</div>";
    $admin_role_id = 3;
} else {
    $role_row = $role_result->fetch_assoc();
    $admin_role_id = $role_row['role_id'];
    echo "<div class='alert alert-info'>✓ Admin role already exists (role_id = $admin_role_id)</div>";
}

// 检查管理员账号是否已存在
$check_admin_sql = "SELECT user_id FROM users WHERE email = ?";
$admin_result = db_query($check_admin_sql, "s", [$admin_email]);

if ($admin_result->num_rows > 0) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>管理员账号已存在</h4>";
    echo "<p>邮箱: <strong>$admin_email</strong></p>";
    echo "<p>如果忘记密码，请手动在数据库中删除此账号后重新运行此脚本。</p>";
    echo "</div>";
} else {
    // 创建管理员账号
    echo "<div class='alert alert-info'>Creating admin account...</div>";

    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $create_admin_sql = "INSERT INTO users (name, email, password_hash, role_id) VALUES (?, ?, ?, ?)";
    db_query($create_admin_sql, "sssi", [$admin_name, $admin_email, $password_hash, $admin_role_id]);

    echo "<div class='alert alert-success'>";
    echo "<h4>✓ 管理员账号创建成功！</h4>";
    echo "<p><strong>邮箱:</strong> $admin_email</p>";
    echo "<p><strong>密码:</strong> $admin_password</p>";
    echo "<hr>";
    echo "<p class='mb-0'><a href='index.php' class='btn btn-primary'>前往登录</a></p>";
    echo "</div>";
}

echo "<div class='alert alert-danger mt-4'>";
echo "<h5>⚠️ 重要安全提醒</h5>";
echo "<p class='mb-0'>创建管理员账号后，请立即删除此文件：<br><code>create_initial_admin.php</code></p>";
echo "</div>";

echo "</div></body></html>";
?>
