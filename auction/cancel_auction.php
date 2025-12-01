<?php
require_once("utilities.php");

// Check if user is logged in and is a seller
if (!is_logged_in() || current_user_role() !== 'seller') {
    header("Location: browse.php");
    exit();
}

// Get item_id from query string
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($item_id <= 0) {
    header("Location: mylistings.php");
    exit();
}

$seller_id = current_user_id();

// Verify that this auction belongs to the current seller
$sql = "
    SELECT a.auction_id, a.status
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    WHERE i.item_id = ? AND i.seller_id = ?
";

$result = db_query($sql, "ii", [$item_id, $seller_id]);

if (!$result || $result->num_rows === 0) {
    // Auction does not belong to this seller
    header("Location: mylistings.php");
    exit();
}

$auction_row = $result->fetch_assoc();
$auction_id = (int)$auction_row['auction_id'];
$status = $auction_row['status'];

// Only pending auctions can be cancelled
if ($status !== 'pending') {
    header("Location: mylistings.php?error=only_pending");
    exit();
}

// Update auction status to cancelled
$sql_update = "
    UPDATE auctions
    SET status = 'cancelled'
    WHERE auction_id = ?
";
db_execute($sql_update, "i", [$auction_id]);

// Redirect back to seller listings page
header("Location: mylistings.php?cancelled=success");
exit();
?>

