<?php
// create_initial_admin.php
// One-off script to create the initial admin account
// Run this file once and then delete it to avoid security risks

require_once 'utilities.php';

// Admin user details
$admin_email = 'admin@auction.com';
$admin_password = 'password123';
$admin_name = 'Admin User';

echo "<!DOCTYPE html><html><head><title>Create Initial Admin</title>";
echo "<link rel='stylesheet' href='css/bootstrap.min.css'></head><body>";
echo "<div class='container mt-5'>";

// Check if admin role already exists
$check_role_sql = "SELECT role_id FROM roles WHERE role_name = 'admin'";
$role_result = db_query($check_role_sql);

if ($role_result->num_rows === 0) {
    // Create admin role
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

// Check if admin user already exists
$check_admin_sql = "SELECT user_id FROM users WHERE email = ?";
$admin_result = db_query($check_admin_sql, "s", [$admin_email]);

if ($admin_result->num_rows > 0) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>Admin account already exists</h4>";
    echo "<p>Email: <strong>$admin_email</strong></p>";
    echo "<p>If you have forgotten the password, please delete this account manually in the database and then run this script again.</p>";
    echo "</div>";
} else {
    // Create admin user account
    echo "<div class='alert alert-info'>Creating admin account...</div>";

    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $create_admin_sql = "INSERT INTO users (name, email, password_hash, role_id) VALUES (?, ?, ?, ?)";
    db_query($create_admin_sql, "sssi", [$admin_name, $admin_email, $password_hash, $admin_role_id]);

    echo "<div class='alert alert-success'>";
    echo "<h4>✓ Admin account created successfully!</h4>";
    echo "<p><strong>Email:</strong> $admin_email</p>";
    echo "<p><strong>Password:</strong> $admin_password</p>";
    echo "<hr>";
    echo "<p class='mb-0'><a href='index.php' class='btn btn-primary'>Go to login</a></p>";
    echo "</div>";
}

// Security reminder to remove this script after use
echo "<div class='alert alert-danger mt-4'>";
echo "<h5>⚠️ Important security notice</h5>";
echo "<p class='mb-0'>After creating the admin account, please delete this file immediately:<br><code>create_initial_admin.php</code></p>";
echo "</div>";

echo "</div></body></html>";
?>


