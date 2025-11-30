<?php
require_once 'utilities.php';

// 修改为你想要的管理员账号
$name = "Admin";
$email = "admin@example.com";
$password = "AdminPass123";

// 自动 bcrypt 加密
$hash = password_hash($password, PASSWORD_BCRYPT);

// 获取 admin 的 role_id
$sql = "SELECT role_id FROM roles WHERE role_name = 'admin' LIMIT 1";
$res = db_query($sql);
$role = $res->fetch_assoc();
$role_id = $role['role_id'];

// 插入管理员
$sql = "INSERT INTO users (name, email, password_hash, role_id) VALUES (?, ?, ?, ?)";
db_query($sql, "sssi", [$name, $email, $hash, $role_id]);

echo "Admin user created successfully!";
