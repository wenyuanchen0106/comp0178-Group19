<?php
// notifications.php
// Notification inbox page that lists all notifications for the current user

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utilities.php';
require_login();

// Current logged-in user id
$uid = $_SESSION['user_id'];

// Fetch all notifications for this user, newest first
$rows = [];
$result = db_query(
    "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC",
    "i",
    [$uid]
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

// Mark all notifications as read for this user
db_query("UPDATE notifications SET is_read=1 WHERE user_id=?", "i", [$uid]);

include 'header.php';
?>

<div class="container mt-5">
<h3>📨 Your notifications</h3>
<hr>

<?php foreach ($rows as $n): ?>
<div class="card mb-3 p-3">
    <h5><?php echo htmlspecialchars($n['title']); ?></h5>
    <p><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
    <a class="btn btn-warning btn-sm" href="<?php echo htmlspecialchars($n['link']) ?>">
        View
    </a>
</div>
<?php endforeach; ?>

</div>

<?php include 'footer.php'; ?>
