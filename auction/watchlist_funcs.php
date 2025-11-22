<?php
require_once 'utilities.php';

header('Content-Type: text/plain');

// 必须登录
if (!is_logged_in()) {
    echo "error";
    exit();
}

if (!isset($_POST['functionname']) || !isset($_POST['arguments'])) {
    echo "error";
    exit();
}

$function = $_POST['functionname'];
$item_id  = (int)$_POST['arguments'];
$user_id  = current_user_id();

if ($item_id <= 0 || !$user_id) {
    echo "error";
    exit();
}

/* ========== 添加到 watchlist ========== */
if ($function === "add_to_watchlist") {

    $sql = "INSERT IGNORE INTO watchlist (user_id, item_id) VALUES (?, ?)";
    db_query($sql, "ii", [$user_id, $item_id]);

    echo "success";
    exit();
}

/* ========== 从 watchlist 删除 ========== */
if ($function === "remove_from_watchlist") {

    $sql = "DELETE FROM watchlist WHERE user_id = ? AND item_id = ?";
    db_query($sql, "ii", [$user_id, $item_id]);

    echo "success";
    exit();
}

echo "error";
exit();
?>
