<?php
require_once 'utilities.php';
require_login();
if (current_user_role() !== 'admin') die("Access denied");

// handle actions
if (!empty($_GET['action']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'close') {
        db_query("UPDATE auctions SET status='finished', end_date=NOW() WHERE auction_id=?", "i", [$id]);
    } elseif ($action === 'delete') {
        db_query("DELETE FROM auctions WHERE auction_id=?", "i", [$id]);
    }

    header("Location: admin_panel_auctions.php");
    exit;
}

// fetch auctions
$sql = "
SELECT a.*, u.name AS seller
FROM auctions a
JOIN users u ON a.seller_id=u.user_id
ORDER BY a.auction_id DESC
";
$rows = db_query_all($sql);

include 'header.php';
?>
<div class="container mt-5">
<h3>Manage Auctions</h3>

<?php foreach ($rows as $a): ?>
  <div class="card mt-3 p-3">
    <h5><?= htmlspecialchars($a['title']) ?></h5>
    <p>Seller: <?= htmlspecialchars($a['seller']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($a['status']) ?></p>

    <a class="btn btn-warning btn-sm" href="admin_panel_auctions.php?action=close&id=<?= $a['auction_id'] ?>">Close</a>
    <a class="btn btn-danger btn-sm" href="admin_panel_auctions.php?action=delete&id=<?= $a['auction_id'] ?>">Delete</a>
  </div>
<?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
