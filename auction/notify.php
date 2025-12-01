<?php
// notify.php
// Helper to create a notification entry in the notifications table for a given user

function send_notification($user_id, $title, $message, $link = "#") {
    require_once 'utilities.php';

    $sql = "INSERT INTO notifications (user_id, title, message, link) 
            VALUES (?, ?, ?, ?)";
    db_query($sql, "isss", [$user_id, $title, $message, $link]);
}
?>

