<?php
require_once 'utilities.php';

header('Content-Type: text/plain');

// User must be logged in
if (!is_logged_in()) {
    echo "error";
    exit();
}

// Only buyers can use watchlist: block sellers
$user_role = current_user_role();
if ($user_role === 'seller') {
    echo "error";
    exit();
}

// Only buyers can use watchlist: block admins
if ($user_role === 'admin' || (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3)) {
    echo "error";
    exit();
}

// Basic validation of POST payload
if (!isset($_POST['functionname']) || !isset($_POST['arguments'])) {
    echo "error";
    exit();
}

$function   = $_POST['functionname'];
$auction_id = (int)$_POST['arguments'];
$user_id    = current_user_id();

// Validate auction id and user id
if ($auction_id <= 0 || !$user_id) {
    echo "error";
    exit();
}

if ($function === "add_to_watchlist") {

    $sql = "INSERT IGNORE INTO watchlist (user_id, auction_id) VALUES (?, ?)";
    db_query($sql, "ii", [$user_id, $auction_id]);

    echo "success";
    exit();
}

if ($function === "remove_from_watchlist") {

    $sql = "DELETE FROM watchlist WHERE user_id = ? AND auction_id = ?";
    db_query($sql, "ii", [$user_id, $auction_id]);

    echo "success";
    exit();
}

echo "error";
exit();
?>

