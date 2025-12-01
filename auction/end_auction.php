<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("utilities.php");
require_once("send_email.php");

try {

    if (!is_logged_in() || current_user_role() !== 'seller') {
        throw new Exception("Not authorized");
    }

    $item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
    if ($item_id <= 0) {
        throw new Exception("Invalid item_id");
    }

    $seller_id = current_user_id();

    // Correct query for auction title and start_price (matches current schema)
    $sql = "
        SELECT a.auction_id, a.status, a.start_price, i.title
        FROM auctions a
        JOIN items i ON a.item_id = i.item_id
        WHERE a.item_id = ? AND a.seller_id = ?
    ";

    $result = db_query($sql, "ii", [$item_id, $seller_id]);
    if (!$result || $result->num_rows === 0) {
        throw new Exception("Auction not found or not yours");
    }

    $auction = $result->fetch_assoc();
    $auction_id    = (int)$auction['auction_id'];
    $status        = $auction['status'];
    $auction_title = $auction['title'];
    $start_price   = $auction['start_price'];

    if ($status !== 'active') {
        throw new Exception("Auction already ended");
    }

    // Winner query: use buyer_id as the actual foreign key to users
    $winner_sql = "
        SELECT u.user_id, u.email, u.name, b.bid_amount
        FROM bids b
        JOIN users u ON b.buyer_id = u.user_id
        WHERE b.auction_id = ?
        ORDER BY b.bid_amount DESC, b.bid_time ASC
        LIMIT 1
    ";

    $winner_result = db_query($winner_sql, "i", [$auction_id]);
    $winner = null;

    if ($winner_result && $winner_result->num_rows > 0) {
        $winner = $winner_result->fetch_assoc();
    }

    // Compute final price and winner information
    if ($winner) {
        $winner_id    = $winner['user_id'];
        $winner_name  = $winner['name'];
        $winner_email = $winner['email'];
        $final_price  = $winner['bid_amount'];
    } else {
        $winner_id    = null;
        $final_price  = $start_price; // No bids → use starting price
    }

    // Fetch seller information
    $seller_result = db_query(
        "SELECT name, email FROM users WHERE user_id = ?",
        "i",
        [$seller_id]
    );
    $seller = $seller_result->fetch_assoc();
    $seller_name  = $seller['name'];
    $seller_email = $seller['email'];

    // Update auction state and send emails
    if ($winner_id !== null) {

        db_execute(
            "UPDATE auctions SET status='finished', winner_id=?, end_date=NOW() WHERE auction_id=?",
            "ii",
            [$winner_id, $auction_id]
        );

        // Email winner
        sendEmail(
            $winner_email,
            "🎉 You won the auction: {$auction_title}",
            "Hi {$winner_name},\n\n".
            "You won '{$auction_title}'!\n".
            "Final price: £{$final_price}\n\n".
            "Please log in to Stark Exchange to complete payment.\n"
        );

        // Email seller
        sendEmail(
            $seller_email,
            "📦 Your item was sold: {$auction_title}",
            "Hi {$seller_name},\n\n".
            "Your item '{$auction_title}' has been sold.\n".
            "Final price: £{$final_price}\n".
            "Winner: {$winner_name}\n"
        );

    } else {

        // No bids, finish auction without a winner
        db_execute(
            "UPDATE auctions SET status='finished', end_date=NOW() WHERE auction_id=?",
            "i",
            [$auction_id]
        );

        // Notify seller that there were no bids
        sendEmail(
            $seller_email,
            "⚠ Your auction ended — No bids",
            "Hi {$seller_name},\n\n".
            "Your auction '{$auction_title}' ended with no bids.\n".
            "You may consider re-listing the item.\n"
        );
    }

    // Redirect back to seller listings page
    header("Location: mylistings.php");
    exit();

} catch (Exception $e) {

    error_log("Auction end error: " . $e->getMessage());

    header("Location: mylistings.php");
    exit();
}

?>


?>

