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

// 检查拍卖是否已经结束
if ($status === 'finished' || $status === 'cancelled') {
    header("Location: mylistings.php");
    exit();
}

// 找到最高出价者
$sql_winner = "
    SELECT buyer_id
    FROM bids
    WHERE auction_id = ?
    ORDER BY bid_amount DESC, bid_time ASC
    LIMIT 1
";

// 查找最高出价者
$result_winner = db_query($sql_winner, "i", [$auction_id]);
$winner_id = null;

if ($result_winner && $result_winner->num_rows > 0) {
    $winner_row = $result_winner->fetch_assoc();
    $winner_id = (int)$winner_row['buyer_id'];

    // ⭐ 给赢家发送通知
    send_notification(
        $winner_id,
        "🎉 You won an auction!",
        "You placed the highest bid and won this item!",
        "listing.php?item_id=" . $item_id
    );

    // ⭐ 给卖家发送通知
    send_notification(
        $seller_id,
        "✔ Your auction is finished",
        "Your item has been successfully sold.",
        "mylistings.php"
    );
}

// 更新拍卖状态
if ($winner_id !== null) {
    $sql_update = "
        UPDATE auctions
        SET status = 'finished',
            winner_id = ?,
            end_date = NOW()
        WHERE auction_id = ?
    ";
    db_execute($sql_update, "ii", [$winner_id, $auction_id]);
} else {
    // 没有出价者，更新状态
    $sql_update = "
        UPDATE auctions
        SET status = 'finished',
            end_date = NOW()
        WHERE auction_id = ?
    ";
    db_execute($sql_update, "i", [$auction_id]);

    // ⭐ 没人出价，通知卖家
    send_notification(
        $seller_id,
        "Auction ended — No bids",
        "Your auction ended but nobody bid.",
        "mylistings.php"
    );
}

// 重定向回 mylistings.php
header("Location: mylistings.php");
exit();
?>
