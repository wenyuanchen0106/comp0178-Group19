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

    // ⭐ 正确查询 title + start_price（匹配你的表结构）
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

    // ⭐ Winner 查询：使用 buyer_id（你们真实字段）
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

    // ⭐ 计算最终价格
    if ($winner) {
        $winner_id    = $winner['user_id'];
        $winner_name  = $winner['name'];
        $winner_email = $winner['email'];
        $final_price  = $winner['bid_amount'];
    } else {
        $winner_id    = null;
        $final_price  = $start_price; // 无竞价 → 使用起拍价
    }

    // ⭐ 获取卖家信息
    $seller_result = db_query(
        "SELECT name, email FROM users WHERE user_id = ?",
        "i",
        [$seller_id]
    );
    $seller = $seller_result->fetch_assoc();
    $seller_name  = $seller['name'];
    $seller_email = $seller['email'];

    // ⭐ 更新数据库状态
    if ($winner_id !== null) {

        db_execute(
            "UPDATE auctions SET status='finished', winner_id=?, end_date=NOW() WHERE auction_id=?",
            "ii",
            [$winner_id, $auction_id]
        );

        // 发给赢家
        sendEmail(
            $winner_email,
            "🎉 You won the auction: {$auction_title}",
            "Hi {$winner_name},\n\n".
            "You won '{$auction_title}'!\n".
            "Final price: £{$final_price}\n\n".
            "Please log in to Stark Exchange to complete payment.\n"
        );

        // 发给卖家
        sendEmail(
            $seller_email,
            "📦 Your item was sold: {$auction_title}",
            "Hi {$seller_name},\n\n".
            "Your item '{$auction_title}' has been sold.\n".
            "Final price: £{$final_price}\n".
            "Winner: {$winner_name}\n"
        );

    } else {

        // 无人出价，结束拍卖
        db_execute(
            "UPDATE auctions SET status='finished', end_date=NOW() WHERE auction_id=?",
            "i",
            [$auction_id]
        );

        // 通知卖家无人出价
        sendEmail(
            $seller_email,
            "⚠ Your auction ended — No bids",
            "Hi {$seller_name},\n\n".
            "Your auction '{$auction_title}' ended with no bids.\n".
            "You may consider re-listing the item.\n"
        );
    }

    // 跳回我的拍卖页面
    header("Location: mylistings.php");
    exit();

} catch (Exception $e) {

    echo "<h1>Error:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    exit();
}
?>
