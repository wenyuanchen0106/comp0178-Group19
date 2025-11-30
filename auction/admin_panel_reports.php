<?php
require_once 'utilities.php';
require_login();
if (current_user_role() !== 'admin') die("Access denied");

// Handle actions
if (!empty($_GET['action']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'resolve') {
        db_query("UPDATE reports SET status='resolved' WHERE report_id=?", "i", [$id]);
    } elseif ($action === 'dismiss') {
        db_query("UPDATE reports SET status='dismissed' WHERE report_id=?", "i", [$id]);
    }

    header("Location: admin_panel_reports.php");
    exit;
}

// Fetch reports
$sql = "
SELECT r.*, u.name AS reporter_name
FROM reports r
JOIN users u ON r.user_id=u.user_id
ORDER BY created_at DESC
";
$rows = db_query_all($sql);

include 'header.php';
?>
<div class="container mt-5">
<h3>Manage Reports</h3>

<?php foreach ($rows as $r): ?>
  <div class="card mt-3 p-3">
    <h5><?= htmlspecialchars($r['title']) ?></h5>
    <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($r['status']) ?></p>

    <a class="btn btn-success btn-sm" href="admin_panel_reports.php?action=resolve&id=<?= $r['report_id'] ?>">Resolve</a>
    <a class="btn btn-danger btn-sm" href="admin_panel_reports.php?action=dismiss&id=<?= $r['report_id'] ?>">Dismiss</a>
  </div>
<?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
