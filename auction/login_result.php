<?php
require_once 'utilities.php';   // 已经在里面 session_start() 了，这里不要再 session_start()

// 只接受 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
    exit();
}

// 1. 取表单数据（注意 name 要和 header.php 里的 login form 对应）
// 一般 starter code 登录表单是 name="email" 和 name="password"
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

// 2. 基本校验
if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email format is invalid.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
}

// 如果已经有错误，直接反馈
if (!empty($errors)) {
    include_once 'header.php';
    echo '<div class="container my-3">';
    echo '<h2>Login failed</h2>';
    echo '<div class="alert alert-danger"><ul>';
    foreach ($errors as $e) {
        echo '<li>' . htmlspecialchars($e) . '</li>';
    }
    echo '</ul></div>';
    echo '<a class="btn btn-primary" href="index.php">Back to home</a>';
    echo '</div>';
    include_once 'footer.php';
    exit();
}

// 3. 从数据库中查找用户（连 roles 一起查出）
$sql = "
    SELECT u.user_id, u.password_hash, u.role_id, r.role_name, u.name
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE u.email = ?
    LIMIT 1
";
$result = db_query($sql, "s", [$email]);

if (!$result || $result->num_rows === 0) {
    // 邮箱不存在
    include_once 'header.php';
    echo '<div class="container my-3">';
    echo '<h2>Login failed</h2>';
    echo '<div class="alert alert-danger">Email or password is incorrect.</div>';
    echo '<a class="btn btn-primary" href="index.php">Back to home</a>';
    echo '</div>';
    include_once 'footer.php';
    exit();
}

$row = $result->fetch_assoc();

// 4. 验证密码（和注册时的 password_hash 对应）
if (!password_verify($password, $row['password_hash'])) {
    include_once 'header.php';
    echo '<div class="container my-3">';
    echo '<h2>Login failed</h2>';
    echo '<div class="alert alert-danger">Email or password is incorrect.</div>';
    echo '<a class="btn btn-primary" href="index.php">Back to home</a>';
    echo '</div>';
    include_once 'footer.php';
    exit();
}

// 5. 登录成功：设置统一的 session 变量（和 register 那边保持一致）
$_SESSION['user_id'] = $row['user_id'];
$_SESSION['logged_in'] = true;
$_SESSION['account_type'] = $row['role_name'];  // buyer / seller / admin
$_SESSION['role_name'] = $row['role_name'];     // 一些 admin page 会用到
$_SESSION['role_id'] = $row['role_id'];         // 管理员权限检查需要
$_SESSION['username'] = $row['name'];           // 用户名
$_SESSION['email'] = $email;

// 6. 重定向到主页面（可以改成 index.php 或 mylistings.php）
redirect('browse.php');
exit();
?>
