<?php
require_once 'utilities.php';
require_login();
if ($_SESSION['role_id'] != 3) die("Access denied.");

include 'header.php';

$rows = db_query_all("
    SELECT a.*, i.title AS item_title, u.email AS seller_email
    FROM auctions a
    JOIN items i ON a.item_id = i.item_id
    JOIN users u ON a.seller_id = u.user_id
    ORDER BY a.auction_id DESC
");
?>

<div class="container mt-5">
    <h2>Admin — Manage Auctions</h2>

<?php foreach ($rows as $a): ?>
<div class="card p-3 my-3">

    <h4>Auction #<?= $a['auction_id'] ?></h4>
    <p><strong>Item:</strong> <?= htmlspecialchars($a['item_title']) ?></p>
    <p><strong>Seller:</strong> <?= htmlspecialchars($a['seller_email']) ?></p>
    <p><strong>Status:</strong> <?= $a['status'] ?></p>

    <a class="btn btn-primary btn-sm" 
       href="listing.php?item_id=<?= $a['item_id'] ?>">View</a>

    <a class="btn btn-danger btn-sm" 
       href="admin_auction_delete.php?id=<?= $a['auction_id'] ?>">Delete Auction</a>

    <?php if ($a['status'] == 'active'): ?>
    <a class="btn btn-warning btn-sm" 
       href="admin_auction_close.php?id=<?= $a['auction_id'] ?>">Force Close</a>
    <?php endif; ?>

</div>
<?php endforeach; ?>

</div>

<?php include 'footer.php'; ?>
