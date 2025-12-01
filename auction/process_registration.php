<?php
// process_registration.php
// Handles registration form submission, validates input, creates the user, and logs them in.

require_once 'utilities.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit();
}

// 1. Read and sanitize form input
$accountType = $_POST['accountType'] ?? '';
$email       = trim($_POST['email'] ?? '');
$name        = trim($_POST['name'] ?? ''); // Read full name field
$password    = $_POST['password'] ?? '';
$password2   = $_POST['passwordConfirmation'] ?? '';

$errors = [];

// 2. Basic validation
if ($accountType !== 'buyer' && $accountType !== 'seller') {
    $errors[] = 'Please choose a valid account type.';
}

// Validate full name
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

// 3. If there are validation errors, show them and stop
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

// 4. Get database connection
$conn = get_db();

// 5. Look up role_id for the selected account type
$roleId = null;
$result = db_query(
    "SELECT role_id FROM roles WHERE role_name = ? LIMIT 1",
    "s",
    [$accountType]
);

if ($result && $row = $result->fetch_assoc()) {
    $roleId = (int)$row['role_id'];
} else {
    // Fallback: create the role record if it does not exist yet
    db_execute("INSERT INTO roles (role_name) VALUES (?)", "s", [$accountType]);
    $result2 = db_query("SELECT role_id FROM roles WHERE role_name = ? LIMIT 1", "s", [$accountType]);
    if ($result2 && $row2 = $result2->fetch_assoc()) {
        $roleId = (int)$row2['role_id'];
    } else {
        die("System error: Role configuration failed.");
    }
}

// 6. Check whether the email is already registered
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

// 7. Insert new user record
$passwordHash = password_hash($password, PASSWORD_DEFAULT); // Hash password securely

db_execute(
    "INSERT INTO users (name, email, password_hash, role_id) VALUES (?, ?, ?, ?)",
    "sssi",
    [$name, $email, $passwordHash, $roleId]
);

// Get the new user id from the connection
$newUserId = $conn->insert_id;

// 8. Log the new user in and populate session fields
$_SESSION['user_id']      = $newUserId;
$_SESSION['username']     = $name;        // Store full name for greeting in header
$_SESSION['role_name']    = $accountType;
$_SESSION['logged_in']    = true;
$_SESSION['account_type'] = $accountType;

// 9. Show success message and redirect to browse page
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
