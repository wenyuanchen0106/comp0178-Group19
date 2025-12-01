<?php
// myreports.php
// Buyer view to list all reports submitted by the current user

require_once 'utilities.php';
require_login();

$user_id = $_SESSION['user_id'];

// Fetch all reports submitted by this user, including related item title
$sql = "
    SELECT r.report_id, r.auction_id, r.item_id,
           r.description, r.status, r.created_at,
           i.title AS item_title
    FROM reports r
    JOIN items i ON r.item_id = i.item_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
";

$result = db_query($sql, "i", [$user_id]);
$reports = $result->fetch_all(MYSQLI_ASSOC);

include_once 'header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4 text-warning">
        <i class="fa fa-flag"></i> My Reports
    </h2>

    <?php if (empty($reports)) : ?>
        <div class="alert alert-secondary">You have not submitted any reports.</div>
    <?php else : ?>
        <table class="table table-dark table-striped">
            <thead>
                <tr class="text-warning">
                    <th>Report ID</th>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r) : ?>
                    <tr>
                        <td><?= htmlspecialchars($r['report_id']) ?></td>

                        <td>
                            <a class="text-info"
                               href="listing.php?item_id=<?= urlencode($r['item_id']) ?>">
                               <?= htmlspecialchars($r['item_title']) ?>
                            </a>
                        </td>

                        <td><?= nl2br(htmlspecialchars($r['description'])) ?></td>

                        <td>
                            <?php if ($r['status'] == 'open'): ?>
                                <span class="badge bg-danger">Open</span>
                            <?php else: ?>
                                <span class="badge bg-success">Resolved</span>
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
