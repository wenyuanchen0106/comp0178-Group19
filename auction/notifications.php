<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
<?php
require_once 'utilities.php';
require_login();

$uid = $_SESSION['user_id'];

$rows = db_query_all(
    "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC",
    "i",
    [$uid]
);

// 标记已读
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
