<?php
require_once 'utilities.php';
require_once 'notify.php';
require_once 'send_email.php';


$sql = "
SELECT auctions.auction_id, auctions.title, bids.user_id
FROM auctions
JOIN bids ON bids.auction_id = auctions.auction_id
WHERE auctions.end_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 MINUTE)
";

$rows = db_query_all($sql);

foreach ($rows as $r) {
    send_notification(
        $r['user_id'],
        "⏰ Auction ending soon",
        "The auction '{$r['title']}' will end in less than 30 minutes!",
        "listing.php?auction_id=" . $r['auction_id']
    );
    // 获取用户邮箱
$user = db_query_one("SELECT email, name FROM users WHERE user_id = ?", [$r['user_id']]);

sendEmail(
    $user['email'],
    "⏰ Auction ending soon: {$r['title']}",
    "Hi {$user['name']},\n\n" .
    "The auction '{$r['title']}' will end within 30 minutes.\n" .
    "Visit Stark Exchange to increase your bid.\n\n" .
    "Best regards,\nStark Exchange Team"
);

}
?>
