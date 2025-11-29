<?php
require_once 'utilities.php';

// 只接受 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit();
}

// 1. 读取并清洗表单数据
$accountType = $_POST['accountType'] ?? '';
$email       = trim($_POST['email'] ?? '');
$name        = trim($_POST['name'] ?? ''); // ✅ 新增：获取名字
$password    = $_POST['password'] ?? '';
$password2   = $_POST['passwordConfirmation'] ?? '';

$errors = [];

// 2. 基本校验
if ($accountType !== 'buyer' && $accountType !== 'seller') {
    $errors[] = 'Please choose a valid account type.';
}

// ✅ 新增：校验名字
if ($name === '') {
    $errors[] = 'Full Name is required.';
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

// 3. 如果有错误，显示错误信息
if (!empty($errors)) {
    include_once 'header.php';
    echo '<div class="container my-5">';
    echo '<h2 class="text-danger">Registration Failed</h2>';
    echo '<div class="alert alert-danger"><ul>';
    foreach ($errors as $e) {
        echo '<li>' . htmlspecialchars($e) . '</li>';
    }
    echo '</ul></div>';
    echo '<a class="btn btn-secondary" href="register.php">Back to Registration</a>';
    echo '</div>';
    include_once 'footer.php';
    exit();
}

// 4. 连接数据库
$conn = get_db();

// 5. 查找角色的 role_id
$roleId = null;
$result = db_query(
    "SELECT role_id FROM roles WHERE role_name = ? LIMIT 1",
    "s",
    [$accountType]
);

if ($result && $row = $result->fetch_assoc()) {
    $roleId = (int)$row['role_id'];
} else {
    // 自动修复角色表逻辑 (保留你原有的逻辑)
    db_execute("INSERT INTO roles (role_name) VALUES (?)", "s", [$accountType]);
    $result2 = db_query("SELECT role_id FROM roles WHERE role_name = ? LIMIT 1", "s", [$accountType]);
    if ($result2 && $row2 = $result2->fetch_assoc()) {
        $roleId = (int)$row2['role_id'];
    } else {
        die("System error: Role configuration failed.");
    }
}

// 6. 检查 email 是否已存在
$result = db_query("SELECT user_id FROM users WHERE email = ? LIMIT 1", "s", [$email]);

if ($result && $result->num_rows > 0) {
    include_once 'header.php';
    echo '<div class="container my-5">';
    echo '<div class="alert alert-danger text-center">This email is already registered.</div>';
    echo '<div class="text-center"><a class="btn btn-secondary" href="register.php">Back to Registration</a></div>';
    echo '</div>';
    include_once 'footer.php';
    exit();
}

// 7. 创建用户 (插入数据库)
$passwordHash = password_hash($password, PASSWORD_DEFAULT); // 使用 PHP 的安全密码哈希函数

// ✅ 修正：使用用户输入的 $name，而不是 $email
db_execute(
    "INSERT INTO users (name, email, password_hash, role_id) VALUES (?, ?, ?, ?)",
    "sssi",
    [$name, $email, $passwordHash, $roleId]
);

// 获取新用户 ID
$newUserId = $conn->insert_id;

// 8. 自动登录 & 设置 Session
$_SESSION['user_id']      = $newUserId;
$_SESSION['username']     = $name;        // ✅ 存入名字，方便 Header 显示 "Hello, Tony Stark"
$_SESSION['role_name']    = $accountType;
$_SESSION['logged_in']    = true;
$_SESSION['account_type'] = $accountType;

// 9. 注册成功提示并跳转
include_once 'header.php';
?>

<div class="container my-5 text-center">
    <div class="alert alert-success shadow-lg" style="border: 2px solid var(--color-accent); background-color: #1a1a1a; color: #fff;">
        <h2 style="font-family: 'Oswald', sans-serif; color: var(--color-accent);">REGISTRATION SUCCESSFUL</h2>
        <p class="lead mt-3">Welcome to the S.H.I.E.L.D. Database, <strong><?php echo htmlspecialchars($name); ?></strong>.</p>
        <p>Redirecting you to the listings...</p>
    </div>
</div>

<script>
    setTimeout(function() {
        window.location.href = "browse.php";
    }, 2000);
</script>

<?php 
include_once 'footer.php'; 
exit();
?>