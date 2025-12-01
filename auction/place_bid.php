<?php
// place_bid.php
// Handles placing a manual bid, triggers auto-bids, and sends notifications/emails.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utilities.php';
require_once 'send_email.php';

// Require login before placing any bid
if (!is_logged_in()) {
    die("You must be logged in to place a bid.");
}

// Resolve current user role from helpers or session
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

// Block sellers from bidding
if ($user_role === 'seller') {
    die("Access denied: This feature is only available to buyers.");
}

// Block admins from bidding
if ($user_role === 'admin' || (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3)) {
    die("Access denied: This feature is only available to buyers.");
}

// Current logged-in user id
$user_id = current_user_id();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// Require both auction_id and bid_amount in the request
if (!isset($_POST['auction_id']) || !isset($_POST['bid_amount'])) {
    die("Missing bid data.");
}

// Sanitize and validate basic bid data
$auction_id = (int)$_POST['auction_id'];
$bid_amount = (float)$_POST['bid_amount'];

if ($auction_id <= 0 || $bid_amount <= 0) {
    die("Invalid bid data.");
}

// Fetch auction for validation (status, owner, timing)
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

// Reject bids on non-active auctions
if ($status !== 'active') {
    die("This auction is not active.");
}

// Reject bids after the auction end time
if ($now >= $end_time) {
    die("This auction has already ended.");
}

// Prevent sellers from bidding on their own auctions
if ($user_id === $seller_id) {
    die("You cannot bid on your own auction.");
}

// Get current highest bid for this auction
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

// Ensure new bid beats the current highest bid
if ($bid_amount <= $current_price) {
    die(
        "Your bid must be greater than the current highest bid (£"
        . number_format($current_price, 2) . ")."
    );
}

// Track previous highest bidder before inserting new bid
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

// Insert the user's manual bid
$sql_insert = "
    INSERT INTO bids (auction_id, buyer_id, bid_amount, bid_time)
    VALUES (?, ?, ?, NOW())
";
db_execute($sql_insert, 'iid', [$auction_id, $user_id, $bid_amount]);

// Send notifications to bidder and seller if helper exists
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

// Auto-bid loop guard to avoid infinite loops
$loop_guard = 0;
$max_loops  = 50;

// Run automatic bidding logic until no more auto-bids are triggered
while (true) {
    $loop_guard++;
    if ($loop_guard > $max_loops) {
        break;
    }

    // Get current highest bid after the last change
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

    // Fetch all auto-bid configurations for this auction
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

    // Loop through auto-bidders and place auto-bids where eligible
    while ($ab = $res_ab->fetch_assoc()) {
        $ab_user = (int)$ab['user_id'];
        $max     = (float)$ab['max_amount'];
        $step    = (float)$ab['step'];

        // Skip if this auto-bidder is already the highest bidder
        if ($ab_user === $current_highest_bidder) {
            continue;
        }

        // Skip if max auto-bid is not higher than current highest
        if ($max <= $current_highest_amount) {
            continue;
        }

        // Compute next auto-bid amount within max boundary
        $auto_bid_amount = $current_highest_amount + $step;
        if ($auto_bid_amount > $max) {
            $auto_bid_amount = $max;
        }

        // Skip if no effective increase
        if ($auto_bid_amount <= $current_highest_amount) {
            continue;
        }

        // Insert auto-bid
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

    // Exit loop when no new auto-bids were placed
    if (!$someone_bid) {
        break;
    }
}

// Determine final highest bidder and bid amount after auto-bidding
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

// If previous highest bidder lost their lead, send notification and email
if ($prev_highest_bidder && $final_highest_bidder && $prev_highest_bidder !== $final_highest_bidder && function_exists('send_notification')) {
    send_notification(
        $prev_highest_bidder,
        "You have been outbid",
        "Your previous highest bid has been outbid. The current highest bid is £" . number_format($final_highest_amount, 2) . ".",
        "listing.php?item_id=" . $item_id
    );

    // Fetch email and name of the outbid buyer
    $sql_old_user = "SELECT email, name FROM users WHERE user_id = ?";
    $res_old_user = db_query($sql_old_user, "i", [$prev_highest_bidder]);
    $old_user = $res_old_user->fetch_assoc();
    $old_email = $old_user['email'];
    $old_name  = $old_user['name'];

    // Send email to the outbid buyer, if mail helper is available
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

// Redirect back to the listing page for this item
redirect("listing.php?item_id=" . $item_id);



