<?php

// TODO: Extract $_POST variables, check they're OK, and attempt to make a bid.
// Notify user of success/failure and redirect/give navigation options.
require_once 'utilities.php';

// 1) 必须是登录状态
if (!is_logged_in()) {
    // 这里你也可以改成 redirect("login.php");
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
$item_id    = (int)$auction['item_id'];
$seller_id  = (int)$auction['seller_id'];
$start_price= (float)$auction['start_price'];
$end_time   = new DateTime($auction['end_date']);
$status     = $auction['status'];

// 5) 一些业务检查：拍卖状态、时间、不能给自己出价
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
    // 这里也可以改成 redirect 回 listing，并在 GET 里带错误信息
    die("Your bid must be greater than the current highest bid (£" . number_format($current_price, 2) . ").");
}

// 8) 插入一条新的出价记录
$sql_insert = "
    INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
    VALUES (?, ?, ?, NOW())
";
db_execute($sql_insert, 'iid', [$auction_id, $user_id, $bid_amount]);

// 9) 出价成功后，重定向回该商品的详情页
//    注意：listing.php 目前是使用 item_id 作为 URL 参数
redirect("listing.php?item_id=" . $item_id);
