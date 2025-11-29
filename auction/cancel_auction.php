<?php
require_once("utilities.php");

// 检查用户是否登录且为卖家
if (!is_logged_in() || current_user_role() !== 'seller') {
    header("Location: browse.php");
    exit();
}

// 获取 item_id
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($item_id <= 0) {
    header("Location: mylistings.php");
    exit();
}

$seller_id = current_user_id();

// 验证这个拍卖是否属于当前卖家
$sql = "
    SELECT a.auction_id, a.status
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    WHERE i.item_id = ? AND i.seller_id = ?
";

$result = db_query($sql, "ii", [$item_id, $seller_id]);

if (!$result || $result->num_rows === 0) {
    // 不是这个卖家的拍卖
    header("Location: mylistings.php");
    exit();
}

$auction_row = $result->fetch_assoc();
$auction_id = (int)$auction_row['auction_id'];
$status = $auction_row['status'];

// 只能取消 pending 状态的拍卖
if ($status !== 'pending') {
    header("Location: mylistings.php?error=only_pending");
    exit();
}

// 更新拍卖状态为 cancelled
$sql_update = "
    UPDATE auctions
    SET status = 'cancelled'
    WHERE auction_id = ?
";
db_execute($sql_update, "i", [$auction_id]);

// 重定向回 mylistings.php
header("Location: mylistings.php?cancelled=success");
exit();
?>
