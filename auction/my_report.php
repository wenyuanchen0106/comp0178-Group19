<?php
require_once "utilities.php";
require_login();

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT r.report_id, r.description, r.status, r.created_at,
           a.auction_id, i.title
    FROM reports r
    JOIN auctions a ON r.auction_id = a.auction_id
    JOIN items i ON r.item_id = i.item_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
";

$result = db_query($sql, "i", [$user_id]);

include_once "header.php";
?>

<div class="container mt-5">
    <h2 class="mb-4 text-warning"><i class="fa fa-flag"></i> My Reports</h2>

    <div class="card bg-dark text-light border-warning">
        <div class="card-body">

            <?php if ($result->num_rows == 0): ?>
                <p class="text-muted">You have not submitted any reports yet.</p>
            <?php else: ?>
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="listing.php?item_id=<?= $row['auction_id'] ?>"
                                       class="text-info">
                                       <?= htmlspecialchars($row['title']) ?>
                                    </a>
                                </td>
                                <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                <td>
                                    <?php if ($row['status'] == 'open'): ?>
                                        <span class="badge bg-danger">Open</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $row['created_at'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include_once "footer.php"; ?>
