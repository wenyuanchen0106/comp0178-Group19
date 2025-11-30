<?php
function send_notification($user_id, $title, $message, $link = "#") {
    require_once 'utilities.php';

    $sql = "INSERT INTO notifications (user_id, title, message, link) 
            VALUES (?, ?, ?, ?)";
    db_query($sql, "isss", [$user_id, $title, $message, $link]);
}
?>
