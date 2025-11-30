<?php
require_once 'utilities.php';
require_login();

// Admin only
if ($_SESSION['role_id'] != 3) {
    die("Access denied.");
}

include 'header.php';

$reports = db_query_all("
    SELECT r.*, u.email AS reporter_email, i.title AS item_title
    FROM reports r
    JOIN users u ON r.user_id = u.user_id
    JOIN items i ON r.item_id = i.item_id
    ORDER BY r.created_at DESC
");
?>

<div class="container mt-5">
    <h2>Admin — Review Reports</h2>

<?php foreach ($reports as $r): ?>
<div class="card p-3 my-3">
    
    <h4>Report #<?= $r['report_id'] ?> 
        <span class="badge bg-secondary"><?= $r['status'] ?></span>
    </h4>

    <p><strong>Item:</strong> <?= htmlspecialchars($r['item_title']) ?></p>
    <p><strong>Reporter:</strong> <?= htmlspecialchars($r['reporter_email']) ?></p>
    <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>

    <a class="btn btn-primary btn-sm" href="listing.php?item_id=<?= $r['item_id'] ?>">
        View Item
    </a>

    <a class="btn btn-success btn-sm" href="admin_report_resolve.php?id=<?= $r['report_id'] ?>">
        Resolve
    </a>

    <a class="btn btn-warning btn-sm" href="admin_report_dismiss.php?id=<?= $r['report_id'] ?>">
        Dismiss
    </a>

    <a class="btn btn-danger btn-sm" href="admin_report_delete.php?id=<?= $r['report_id'] ?>">
        Delete
    </a>

</div>
<?php endforeach; ?>

</div>

<?php include 'footer.php'; ?>
