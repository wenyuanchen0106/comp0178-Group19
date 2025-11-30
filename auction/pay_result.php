<?php
// Enable error reporting for debugging during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load utilities and session helpers
require_once 'utilities.php';

// Require user login
require_login();

// Only allow POST requests (form submissions)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

// Basic validation: required POST fields must exist
if (!isset($_POST['auction_id'], $_POST['payment_method'])) {
    redirect('mybids.php');
}

// Sanitize and validate input
$auction_id = (int)$_POST['auction_id'];
$payment_method = trim($_POST['payment_method']);

if ($auction_id <= 0 || $payment_method === '') {
    redirect('mybids.php');
}

// Get current user id
$user_id = current_user_id();

// Fetch auction info and compute the final amount from bids
// Again, we use COALESCE(MAX(b.bid_amount), a.start_price) as the final price
$sql = "
    SELECT 
        a.auction_id,
        a.status,
        a.winner_id,
        a.start_price,
        COALESCE(MAX(b.bid_amount), a.start_price) AS current_price
    FROM auctions a
    LEFT JOIN bids b ON a.auction_id = b.auction_id
    WHERE a.auction_id = ?
    GROUP BY a.auction_id, a.status, a.winner_id, a.start_price
    LIMIT 1
";
$result = db_query($sql, 'i', [$auction_id]);

// If the auction cannot be found, go back
if (!$result || $result->num_rows === 0) {
    redirect('mybids.php');
}

$auction = $result->fetch_assoc();

// The auction must be finished/closed before payment is accepted
if (!in_array($auction['status'], ['finished', 'closed'], true)) {
    redirect('mybids.php');
}

// Only the winner can submit the payment
if ((int)$auction['winner_id'] !== $user_id) {
    redirect('mybids.php');
}

// Determine the amount to be charged, based on final auction price
$amount = (float)$auction['current_price'];

// Check if a payment record already exists to avoid duplicate payments
$sql = "
    SELECT 1
    FROM payments
    WHERE auction_id = ? AND user_id = ?
    LIMIT 1
";
$res = db_query($sql, 'ii', [$auction_id, $user_id]);

if ($res && $res->num_rows > 0) {
    // Payment already exists; set a message and redirect
    $_SESSION['success_message'] = 'Payment already recorded.';
    redirect('mybids.php');
}

// Insert a new payment record into the payments table
$sql = "
    INSERT INTO payments (user_id, auction_id, amount, payment_method, status, paid_at)
    VALUES (?, ?, ?, ?, 'completed', NOW())
";
$ok = db_query($sql, 'iids', [$user_id, $auction_id, $amount, $payment_method]);

if ($ok) {

    // ⭐ 通知买家（当前用户）
    send_notification(
        $user_id,
        "Payment Successful",
        "Your payment of £" . number_format($amount, 2) . " for auction #$auction_id was successful.",
        "mybids.php"
    );

    // ⭐ 查找卖家 ID
    $sql_seller = "SELECT seller_id FROM auctions WHERE auction_id = ?";
    $res_seller = db_query($sql_seller, 'i', [$auction_id]);
    $row_seller = $res_seller->fetch_assoc();
    $seller_id = (int)$row_seller['seller_id'];

    // ⭐ 通知卖家
    send_notification(
        $seller_id,
        "Item Paid",
        "The buyer has successfully paid £" . number_format($amount, 2) . " for your auction item (auction #$auction_id).",
        "mylistings.php"
    );

    // 更新拍卖状态
    $sql2 = "UPDATE auctions SET status = 'paid' WHERE auction_id = ?";
    db_query($sql2, 'i', [$auction_id]);

    $_SESSION['success_message'] = 'Payment successful!';

} else {
    $_SESSION['error_message'] = 'Payment failed.';
}


// Redirect back to My Bids page where the flash message will be shown
redirect('mybids.php');

