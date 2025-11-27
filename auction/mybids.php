<?php

// 处理用户出价 + eBay 风格的自动出价逻辑
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

// 4) 查拍卖信息（包括 item_id, seller_id, start_price, end_date, status）
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

// 方便后面重定向回 listing.php
$item_id     = (int)$auction['item_id'];
$seller_id   = (int)$auction['seller_id'];
$start_price = (float)$auction['start_price'];
$end_time    = new DateTime($auction['end_date']);
$status      = $auction['status'];

// 5) 业务检查：拍卖状态、时间、不能给自己出价
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
    die(
        "Your bid must be greater than the current highest bid (£"
        . number_format($current_price, 2) . ")."
    );
}

// 8) 插入一条新的“手动出价”记录
$sql_insert = "
    INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
    VALUES (?, ?, ?, NOW())
";
db_execute($sql_insert, 'iid', [$auction_id, $user_id, $bid_amount]);

/*
 * 9) eBay 风格自动出价逻辑
 *
 * 目标：让所有设置了自动出价的人，按规则“轮流抬价”，直到：
 *   - 没有人还能继续加价，或者
 *   - 达到一个安全的循环次数上限（防止死循环）
 */

$loop_guard = 0;
$max_loops  = 50;  // 安全阀：一轮竞价最多自动抬价 50 次

while (true) {
    $loop_guard++;
    if ($loop_guard > $max_loops) {
        // 安全退出，防止异常情况导致无限循环
        break;
    }

    // 9.1 查询当前最高出价（buyer_id + bid_amount）
    $sql_top = "
        SELECT buyer_id, bid_amount
        FROM bids
        WHERE auction_id = ?
        ORDER BY bid_amount DESC, bid_time ASC
        LIMIT 1
    ";
    $res_top = db_query($sql_top, 'i', [$auction_id]);
    if (!$res_top || $res_top->num_rows === 0) {
        // 理论上不会发生，因为刚插入了一条手动出价
        break;
    }
    $top = $res_top->fetch_assoc();
    $res_top->free();

    $current_highest_bidder = (int)$top['buyer_id'];
    $current_highest_amount = (float)$top['bid_amount'];

    // 9.2 查询该拍卖的所有自动出价设置
    //     按 max_amount 从大到小排序（类似“谁更有钱谁优先”）
    $sql_ab = "
        SELECT user_id, max_amount, step
        FROM autobids
        WHERE auction_id = ?
        ORDER BY max_amount DESC
    ";
    $res_ab = db_query($sql_ab, 'i', [$auction_id]);

    if (!$res_ab || $res_ab->num_rows === 0) {
        // 没有任何自动出价用户，直接退出大循环
        if ($res_ab) {
            $res_ab->free();
        }
        break;
    }

    $someone_bid = false;  // 标记这一轮里是否有任何人自动出价

    while ($ab = $res_ab->fetch_assoc()) {
        $ab_user = (int)$ab['user_id'];
        $max     = (float)$ab['max_amount'];
        $step    = (float)$ab['step'];

        // 规则 1：当前最高出价人，本轮不再自动出价（防止自我抬价）
        if ($ab_user === $current_highest_bidder) {
            continue;
        }

        // 规则 2：max_amount 必须高于当前价，否则没法继续加价
        if ($max <= $current_highest_amount) {
            continue;
        }

        // 计算这次自动出价金额：在当前价基础上加 step，但不能超过 max_amount
        $auto_bid_amount = $current_highest_amount + $step;
        if ($auto_bid_amount > $max) {
            $auto_bid_amount = $max;
        }

        // 再次确认：自动出价金额必须严格大于当前价
        if ($auto_bid_amount <= $current_highest_amount) {
            continue;
        }

        // 插入自动出价记录
        db_execute(
            "INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
             VALUES (?, ?, ?, NOW())",
            'iid',
            [$auction_id, $ab_user, $auto_bid_amount]
        );

        // 更新“当前最高价”和“当前最高出价人”
        $current_highest_bidder = $ab_user;
        $current_highest_amount = $auto_bid_amount;

        $someone_bid = true;

        // 注意：这里 **不 break**，因为这一轮是“遍历所有自动出价用户”，
        // 但在 while(true) 的下一轮，会重新计算 current_highest，再来一轮。
        // 这一层循环只是决定：这一轮里谁能出价。
    }

    $res_ab->free();

    // 如果这一轮里，没有任何一个自动出价用户能出价，则结束整个 while(true)
    if (!$someone_bid) {
        break;
    }

    // 否则，while(true) 继续下一轮：重新计算当前最高价，再让大家看还能不能加价
}

// 10) 所有出价（手动 + 自动）完成后，重定向回该商品详情页
redirect("listing.php?item_id=" . $item_id);
