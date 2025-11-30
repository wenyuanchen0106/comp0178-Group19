<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utilities.php';
require_once 'send_email.php';

if (!is_logged_in()) {
    die("You must be logged in to place a bid.");
}

$user_role = null;
if (function_exists('current_user_role')) {
    $user_role = current_user_role();
} elseif (isset($_SESSION['role_name'])) {
    $user_role = $_SESSION['role_name'];
} elseif (isset($_SESSION['role_id'])) {
    if ($_SESSION['role_id'] == 1) {
        $user_role = 'buyer';
    } elseif ($_SESSION['role_id'] == 2) {
        $user_role = 'seller';
    } elseif ($_SESSION['role_id'] == 3) {
        $user_role = 'admin';
    }
}

if ($user_role === 'seller') {
    die("Access denied: This feature is only available to buyers.");
}
if ($user_role === 'admin' || (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3)) {
    die("Access denied: This feature is only available to buyers.");
}

$user_id = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

if (!isset($_POST['auction_id']) || !isset($_POST['bid_amount'])) {
    die("Missing bid data.");
}

$auction_id = (int)$_POST['auction_id'];
$bid_amount = (float)$_POST['bid_amount'];

if ($auction_id <= 0 || $bid_amount <= 0) {
    die("Invalid bid data.");
}

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

if ($bid_amount <= $current_price) {
    die(
        "Your bid must be greater than the current highest bid (£"
        . number_format($current_price, 2) . ")."
    );
}

$prev_highest_bidder = null;
$prev_highest_amount = null;

$sql_prev = "
    SELECT buyer_id, bid_amount
    FROM bids
    WHERE auction_id = ?
    ORDER BY bid_amount DESC, bid_time ASC
    LIMIT 1
";
$res_prev = db_query($sql_prev, "i", [$auction_id]);
if ($res_prev && $res_prev->num_rows > 0) {
    $prev = $res_prev->fetch_assoc();
    $prev_highest_bidder = (int)$prev['buyer_id'];
    $prev_highest_amount = (float)$prev['bid_amount'];
}
if ($res_prev) {
    $res_prev->free();
}

$sql_insert = "
    INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
    VALUES (?, ?, ?, NOW())
";
db_execute($sql_insert, 'iid', [$auction_id, $user_id, $bid_amount]);

if (function_exists('send_notification')) {
    send_notification(
        $user_id,
        "Bid placed successfully!",
        "Your bid of £" . number_format($bid_amount, 2) . " has been placed.",
        "listing.php?item_id=" . $item_id
    );

    if ($seller_id && $seller_id != $user_id) {
        send_notification(
            $seller_id,
            "Your auction received a new bid!",
            "Someone bid £" . number_format($bid_amount, 2) . " on your item.",
            "listing.php?item_id=" . $item_id
        );
    }
}

$loop_guard = 0;
$max_loops  = 50;

while (true) {
    $loop_guard++;
    if ($loop_guard > $max_loops) {
        break;
    }

    $sql_top = "
        SELECT buyer_id, bid_amount
        FROM bids
        WHERE auction_id = ?
        ORDER BY bid_amount DESC, bid_time ASC
        LIMIT 1
    ";
    $res_top = db_query($sql_top, 'i', [$auction_id]);
    if (!$res_top || $res_top->num_rows === 0) {
        if ($res_top) {
            $res_top->free();
        }
        break;
    }
    $top = $res_top->fetch_assoc();
    $res_top->free();

    $current_highest_bidder = (int)$top['buyer_id'];
    $current_highest_amount = (float)$top['bid_amount'];

    $sql_ab = "
        SELECT user_id, max_amount, step
        FROM autobids
        WHERE auction_id = ?
        ORDER BY max_amount DESC
    ";
    $res_ab = db_query($sql_ab, 'i', [$auction_id]);

    if (!$res_ab || $res_ab->num_rows === 0) {
        if ($res_ab) {
            $res_ab->free();
        }
        break;
    }

    $someone_bid = false;

    while ($ab = $res_ab->fetch_assoc()) {
        $ab_user = (int)$ab['user_id'];
        $max     = (float)$ab['max_amount'];
        $step    = (float)$ab['step'];

        if ($ab_user === $current_highest_bidder) {
            continue;
        }

        if ($max <= $current_highest_amount) {
            continue;
        }

        $auto_bid_amount = $current_highest_amount + $step;
        if ($auto_bid_amount > $max) {
            $auto_bid_amount = $max;
        }

        if ($auto_bid_amount <= $current_highest_amount) {
            continue;
        }

        db_execute(
            "INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
             VALUES (?, ?, ?, NOW())",
            'iid',
            [$auction_id, $ab_user, $auto_bid_amount]
        );

        $current_highest_bidder = $ab_user;
        $current_highest_amount = $auto_bid_amount;

        $someone_bid = true;
    }

    $res_ab->free();

    if (!$someone_bid) {
        break;
    }
}

$final_highest_bidder = null;
$final_highest_amount = null;

$sql_final = "
    SELECT buyer_id, bid_amount
    FROM bids
    WHERE auction_id = ?
    ORDER BY bid_amount DESC, bid_time ASC
    LIMIT 1
";
$res_final = db_query($sql_final, "i", [$auction_id]);
if ($res_final && $res_final->num_rows > 0) {
    $final = $res_final->fetch_assoc();
    $final_highest_bidder = (int)$final['buyer_id'];
    $final_highest_amount = (float)$final['bid_amount'];
}
if ($res_final) {
    $res_final->free();
}

if ($prev_highest_bidder && $final_highest_bidder && $prev_highest_bidder !== $final_highest_bidder && function_exists('send_notification')) {
    send_notification(
        $prev_highest_bidder,
        "You have been outbid",
        "Your previous highest bid has been outbid. The current highest bid is £" . number_format($final_highest_amount, 2) . ".",
        "listing.php?item_id=" . $item_id
    );
        // 2. Fetch email + name of the outbid buyer
    $sql_old_user = "SELECT email, name FROM users WHERE user_id = ?";
    $res_old_user = db_query($sql_old_user, "i", [$prev_highest_bidder]);
    $old_user = $res_old_user->fetch_assoc();
    $old_email = $old_user['email'];
    $old_name  = $old_user['name'];

    // 3. Email notification for the outbid buyer
    if (function_exists('sendEmail')) {
        sendEmail(
            $old_email,
            "⚠ You have been outbid",
            "Hi {$old_name},\n\n".
            "Your bid on auction #{$auction_id} has been outbid by another buyer.\n\n".
            "Current highest bid: £" . number_format($final_highest_amount, 2) . "\n\n".
            "Visit Stark Exchange to increase your bid and regain the lead:\n".
            "https://localhost/comp0178-Group19/auction/listing.php?item_id={$item_id}\n\n".
            "Best regards,\n".
            "Stark Exchange Team"
        );
    }
}

redirect("listing.php?item_id=" . $item_id);


