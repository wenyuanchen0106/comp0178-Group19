<?php

// 处理用户出价 + 触发自动出价逻辑
require_once 'utilities.php';

// 1) 必须是登录状态
if (!is_logged_in()) {
    die("You must be logged in to place a bid.");
}

$user_id = current_user_id();

// 2) 必须是 POST 提交
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// 3) 从表单获取 auction_id 和 bid_amount
if (!isset($_POST['auction_id']) || !isset($_POST['bid_amount'])) {
    die("Missing bid data.");
}

$auction_id = (int)$_POST['auction_id'];
$bid_amount = (float)$_POST['bid_amount'];

if ($auction_id <= 0 || $bid_amount <= 0) {
    die("Invalid bid data.");
}

// 4) 查拍卖信息
$sql = "
    SELECT auction_id, item_id, seller_id, start_price, end_date, status
    FROM auctions
    WHERE auction_id = ?
    LIMIT 1
";
$result = db_query($sql, 'i', [$auction_id]);
if (!$result || $result->num_rows === 0) {
    die("Auction not found.");
}
$auction = $result->fetch_assoc();
$result->free();

$item_id     = (int)$auction['item_id'];
$seller_id   = (int)$auction['seller_id'];
$start_price = (float)$auction['start_price'];
$end_time    = new DateTime($auction['end_date']);
$status      = $auction['status'];

// 5) 业务检查
$now = new DateTime();

if ($status !== 'active') {
    die("This auction is not active.");
}

if ($now >= $end_time) {
    die("This auction has already ended.");
}

if ($user_id === $seller_id) {
    die("You cannot bid on your own auction.");
}

// 6) 当前最高出价（如果没有出价，就用起拍价）
$sql_max = "
    SELECT MAX(bid_amount) AS max_bid
    FROM bids
    WHERE auction_id = ?
";
$result_max = db_query($sql_max, 'i', [$auction_id]);
$row_max    = $result_max->fetch_assoc();
$result_max->free();

$max_bid = $row_max['max_bid'];
if ($max_bid === null) {
    $current_price = $start_price;
} else {
    $current_price = (float)$max_bid;
}

// 7) 校验：新出价必须严格大于当前最高价
if ($bid_amount <= $current_price) {
    die("Your bid must be greater than the current highest bid (£" . number_format($current_price, 2) . ").");
}

// 8) 插入用户手动出价
$sql_insert = "
    INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
    VALUES (?, ?, ?, NOW())
";
db_execute($sql_insert, 'iid', [$auction_id, $user_id, $bid_amount]);

/*
 * 9) 自动出价逻辑（简单版本）
 *
 * 思路：
 *   - 用户手动出价成功后，检查该拍卖是否有自动出价设置。
 *   - 找到“有自动出价、且上限还没被超过”的用户（不包括刚刚出价的人）。
 *   - 让其中一个满足条件的用户自动出价一步（current_price + step，不能超过 max_amount）。
 */

// 9.1 重新计算当前最高出价和当前最高出价的用户
$sql_max_full = "
    SELECT buyer_id, bid_amount
    FROM bids
    WHERE auction_id = ?
    ORDER BY bid_amount DESC, bid_time ASC
    LIMIT 1
";
$res_full = db_query($sql_max_full, 'i', [$auction_id]);
$top      = $res_full->fetch_assoc();
$res_full->free();

$current_highest_bidder = (int)$top['buyer_id'];
$current_highest_amount = (float)$top['bid_amount'];

// 9.2 查找该拍卖下所有自动出价设置（不包括刚刚出价的人）
$sql_ab = "
    SELECT user_id, max_amount, step
    FROM autobids
    WHERE auction_id = ?
      AND user_id <> ?
";
$res_ab = db_query($sql_ab, 'ii', [$auction_id, $user_id]);

if ($res_ab && $res_ab->num_rows > 0) {
    while ($ab = $res_ab->fetch_assoc()) {
        $ab_user  = (int)$ab['user_id'];
        $max      = (float)$ab['max_amount'];
        $step     = (float)$ab['step'];

        // 如果这个自动出价用户已经是当前最高出价人，就不用再处理
        if ($ab_user === $current_highest_bidder) {
            continue;
        }

        // 该用户的最高愿出价必须高于当前价格，才有意义
        if ($max <= $current_highest_amount) {
            continue;
        }

        // 计算这次自动出价金额：加一步，但不能超过 max
        $auto_bid_amount = $current_highest_amount + $step;
        if ($auto_bid_amount > $max) {
            $auto_bid_amount = $max;
        }

        // 再次确保比当前最高价高
        if ($auto_bid_amount <= $current_highest_amount) {
            continue;
        }

        // 插入一条自动出价记录
        db_execute(
            "INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
             VALUES (?, ?, ?, NOW())",
            'iid',
            [$auction_id, $ab_user, $auto_bid_amount]
        );

        // 更新当前最高价和最高出价人
        $current_highest_bidder = $ab_user;
        $current_highest_amount = $auto_bid_amount;

        // 这里为了简单，每次只让一个自动出价用户出价一次，然后结束循环。
        // 如果希望自动出价之间“互相抢价”，可以继续循环，但课程作业通常不要求那么复杂。
        break;
    }
    $res_ab->free();
}

// 10) 出价（包括自动出价）完成后，重定向回该商品详情页
redirect("listing.php?item_id=" . $item_id);
