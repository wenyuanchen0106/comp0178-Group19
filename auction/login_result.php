<?php
require_once 'utilities.php';   // Session is started inside utilities.php; do not start it again here

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
    exit();
}

// 1. Read form data (names must match the login form in header.php)
// The starter code login form uses name="email" and name="password"
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

// 2. Basic validation
if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email format is invalid.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
}

// If there are validation errors, show them and stop
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

// 3. Look up the user in the database (join roles to get role info)
$sql = "
    SELECT u.user_id, u.password_hash, u.role_id, r.role_name, u.name
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE u.email = ?
    LIMIT 1
";
$result = db_query($sql, "s", [$email]);

if (!$result || $result->num_rows === 0) {
    // Email not found
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

// 4. Verify password against stored password_hash
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

// 5. Login successful: set session variables (consistent with registration)
$_SESSION['user_id']      = $row['user_id'];
$_SESSION['logged_in']    = true;
$_SESSION['account_type'] = $row['role_name'];  // buyer / seller / admin
$_SESSION['role_name']    = $row['role_name'];  // used by admin pages
$_SESSION['role_id']      = $row['role_id'];    // used for permission checks
$_SESSION['username']     = $row['name'];       // display name
$_SESSION['email']        = $email;

// 6. Redirect to main page (can be changed to index.php or mylistings.php)
redirect('browse.php');
exit();
?>

