<?php
// set_autobid.php
// 作用：处理 listing.php 页面上的 Auto-bid 提交，写入/更新 autobids 表。

require_once 'utilities.php';

// 1. 检查用户是否已登录
if (!is_logged_in()) {
    // 这里也可以改成 redirect('login.php');
    die('You must be logged in to set an auto-bid.');
}

// 1.5 Sellers and Admins cannot set auto-bids - only buyers can bid
$user_role = current_user_role();
if ($user_role === 'seller') {
    die('Access denied: This feature is only available to buyers.');
}
if ($user_role === 'admin' || (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3)) {
    die('Access denied: This feature is only available to buyers.');
}

// 只接受 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

$user_id = current_user_id();

// 2. 从 $_POST 中读取 auction_id, max_amount, step
if (!isset($_POST['auction_id'], $_POST['max_amount'], $_POST['step'])) {
    die('Missing auto-bid data.');
}

$auction_id = (int)$_POST['auction_id'];
$max_amount = (float)$_POST['max_amount'];
$step       = (float)$_POST['step'];

if ($auction_id <= 0 || $max_amount <= 0 || $step <= 0) {
    die('Invalid auto-bid values.');
}

// 3. 确认 auction 存在（并可选检查状态）
$sql = "
    SELECT auction_id, item_id, status
    FROM auctions
    WHERE auction_id = ?
    LIMIT 1
";
$result = db_query($sql, 'i', [$auction_id]);

if (!$result || $result->num_rows === 0) {
    die('Auction not found.');
}

$auction = $result->fetch_assoc();
$result->free();

$item_id = (int)$auction['item_id'];
$status  = $auction['status'];

// 如果要严格要求，只在 active 拍卖上允许设置自动出价，可以取消下面注释
// if ($status !== 'active') {
//     die('You can only set auto-bid on active auctions.');
// }

// 4. 在 autobids 表中：已存在则 UPDATE，否则 INSERT
$sql_check = "
    SELECT autobid_id
    FROM autobids
    WHERE user_id = ? AND auction_id = ?
    LIMIT 1
";
$check = db_query($sql_check, 'ii', [$user_id, $auction_id]);

if ($check && $check->num_rows > 0) {
    // 已有记录，更新
    $row        = $check->fetch_assoc();
    $autobid_id = (int)$row['autobid_id'];
    $check->free();

    $sql_update = "
        UPDATE autobids
        SET max_amount = ?, step = ?
        WHERE autobid_id = ?
    ";
    db_execute($sql_update, 'ddi', [$max_amount, $step, $autobid_id]);
} else {
    // 没有记录，插入新设置
    if ($check) {
        $check->free();
    }
    $sql_insert = "
        INSERT INTO autobids (user_id, auction_id, max_amount, step)
        VALUES (?, ?, ?, ?)
    ";
    db_execute($sql_insert, 'iidd', [$user_id, $auction_id, $max_amount, $step]);
}

// 5. session 提示（可选，如果你有 flash message 系统可以在这里写）
// $_SESSION['flash_message'] = 'Auto-bid saved.';

// 6. 重定向回 listing.php 对应的 item
redirect('listing.php?item_id=' . $item_id);
