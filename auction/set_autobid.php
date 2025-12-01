<?php
require_once 'utilities.php';

// Ensure the user is logged in before allowing auto-bid settings
if (!is_logged_in()) {
    die('You must be logged in to set an auto-bid.');
}

// Determine current user role to restrict access to buyers only
$user_role = current_user_role();

// Block sellers from using auto-bid feature
if ($user_role === 'seller') {
    die('Access denied: This feature is only available to buyers.');
}

// Block admins from using auto-bid feature
if ($user_role === 'admin' || (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3)) {
    die('Access denied: This feature is only available to buyers.');
}

// Only accept POST requests for setting auto-bid
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

// Get the currently logged-in user ID
$user_id = current_user_id();

// Validate required POST fields
if (!isset($_POST['auction_id'], $_POST['max_amount'], $_POST['step'])) {
    die('Missing auto-bid data.');
}

// Sanitize and cast input values
$auction_id = (int)$_POST['auction_id'];
$max_amount = (float)$_POST['max_amount'];
$step       = (float)$_POST['step'];

// Basic validation on numeric values
if ($auction_id <= 0 || $max_amount <= 0 || $step <= 0) {
    die('Invalid auto-bid values.');
}

// Check that the auction exists and fetch basic info
$sql = "
    SELECT auction_id, item_id, status
    FROM auctions
    WHERE auction_id = ?
    LIMIT 1
";
$result = db_query($sql, 'i', [$auction_id]);

// If the auction cannot be found, stop here
if (!$result || $result->num_rows === 0) {
    die('Auction not found.');
}

// Extract auction data
$auction = $result->fetch_assoc();
$result->free();

$item_id = (int)$auction['item_id'];
$status  = $auction['status'];

// Check if an auto-bid already exists for this user and auction
$sql_check = "
    SELECT autobid_id
    FROM autobids
    WHERE user_id = ? AND auction_id = ?
    LIMIT 1
";
$check = db_query($sql_check, 'ii', [$user_id, $auction_id]);

if ($check && $check->num_rows > 0) {
    // Existing auto-bid found: update max_amount and step
    $row        = $check->fetch_assoc();
    $autobid_id = (int)$row['autobid_id'];
    $check->free();

    $sql_update = "
        UPDATE autobids
        SET max_amount = ?, step = ?
        WHERE autobid_id = ?
    ";
    db_execute($sql_update, 'ddi', [$max_amount, $step, $autobid_id]);
} else {
    // No previous auto-bid: insert a new record
    if ($check) {
        $check->free();
    }
    $sql_insert = "
        INSERT INTO autobids (user_id, auction_id, max_amount, step)
        VALUES (?, ?, ?, ?)
    ";
    db_execute($sql_insert, 'iidd', [$user_id, $auction_id, $max_amount, $step]);
}

// Redirect back to the listing page for this item after saving auto-bid
redirect('listing.php?item_id=' . $item_id);

