<?php
require_once 'utilities.php';

// 只接受 POST 请求，其他请求重定向回注册页
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
    exit();
}

// 1. 读取并简单清洗表单数据
$accountType = $_POST['accountType'] ?? '';
$email       = trim($_POST['email'] ?? '');
$password    = $_POST['password'] ?? '';
$password2   = $_POST['passwordConfirmation'] ?? '';

$errors = [];

// 2. 基本校验
if ($accountType !== 'buyer' && $accountType !== 'seller') {
    $errors[] = 'Please choose a valid account type.';
}

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email format is invalid.';
}

if ($password === '' || $password2 === '') {
    $errors[] = 'Password and confirmation are required.';
} elseif ($password !== $password2) {
    $errors[] = 'Passwords do not match.';
}

// 3. 如果前面的校验已经有错误，直接显示错误信息
if (!empty($errors)) {
    include_once 'header.php';
    echo '<div class="container my-3">';
    echo '<h2>Registration failed</h2>';
    echo '<div class="alert alert-danger"><ul>';
    foreach ($errors as $e) {
        echo '<li>' . htmlspecialchars($e) . '</li>';
    }
    echo '</ul></div>';
    echo '<a class="btn btn-primary" href="register.php">Back to register</a>';
    echo '</div>';
    include_once 'footer.php';
    exit();
}

// 4. 连接数据库
$conn = get_db();

// 5. 查找角色的 role_id（buyer / seller）
$roleId = null;
$result = db_query(
    "SELECT role_id FROM roles WHERE role_name = ? LIMIT 1",
    "s",
    [$accountType]
);

if ($result && $row = $result->fetch_assoc()) {
    $roleId = (int)$row['role_id'];
} else {
    // 如果 roles 表里还没有对应记录，可以视情况自动插入
    db_execute(
        "INSERT INTO roles (role_name) VALUES (?)",
        "s",
        [$accountType]
    );
    $result2 = db_query(
        "SELECT role_id FROM roles WHERE role_name = ? LIMIT 1",
        "s",
        [$accountType]
    );
    if ($result2 && $row2 = $result2->fetch_assoc()) {
        $roleId = (int)$row2['role_id'];
    } else {
        include_once 'header.php';
        echo '<div class="container my-3">';
        echo '<h2>Registration failed</h2>';
        echo '<div class="alert alert-danger">System error: role not configured.</div>';
        echo '<a class="btn btn-primary" href="register.php">Back to register</a>';
        echo '</div>';
        include_once 'footer.php';
        exit();
    }
}

// 6. 检查 email 是否已存在
$result = db_query(
    "SELECT user_id FROM users WHERE email = ? LIMIT 1",
    "s",
    [$email]
);

if ($result && $result->num_rows > 0) {
    include_once 'header.php';
    echo '<div class="container my-3">';
    echo '<h2>Registration failed</h2>';
    echo '<div class="alert alert-danger">This email is already registered.</div>';
    echo '<a class="btn btn-primary" href="register.php">Back to register</a>';
    echo '</div>';
    include_once 'footer.php';
    exit();
}

// 7. 创建用户：name 暂时用 email 代替（你的 users.name 是 NOT NULL）
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$nameForNow   = $email;   // 如果将来需要单独的 name 字段，可以再扩展表单

db_execute(
    "INSERT INTO users (name, email, password_hash, role_id) VALUES (?, ?, ?, ?)",
    "sssi",
    [$nameForNow, $email, $passwordHash, $roleId]
);

// 获取新用户 ID
$newUserId = $conn->insert_id;

// 8. 登录新用户 & 设置 session
$_SESSION['user_id']   = $newUserId;
$_SESSION['role_name'] = $accountType;
$_SESSION['logged_in']    = true;
$_SESSION['account_type'] = $accountType;   // 和 role_name 内容相同
// 9. 注册成功后重定向（可以改成你想去的页面，比如 browse.php 或 index.php）
redirect('browse.php');
exit();
?>
