<?php
// admin_reports.php
// 管理员查看所有举报并处理

require_once 'utilities.php';
require_login(); // 必须登录

// 检查是否管理员（role_id = 3 或你们定义的 admin）
if ($_SESSION['role_id'] != 3) {
    echo "<p style='color:red'>Access denied: Admin only.</p>";
    exit;
}

include_once 'header.php';

// 获取所有举报以及拍卖状态
$sql = "
    SELECT
        r.report_id,
        r.description,
        r.status,
        r.created_at,
        u.name AS reporter_name,
        a.auction_id,
        a.status AS auction_status,
        i.title AS item_title
    FROM reports r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN auctions a ON r.auction_id = a.auction_id
    LEFT JOIN items i ON r.item_id = i.item_id
    ORDER BY r.created_at DESC
";
$result = db_query($sql);
?>

<div class="container mt-4">
    <h2 class="mb-4">Manage Reports</h2>

    <?php if ($result->num_rows == 0): ?>
        <div class="alert alert-info">No reports found.</div>

    <?php else: ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">
                        Report #<?= $row['report_id'] ?>  
                        <span class="badge bg-danger"><?= htmlspecialchars($row['status']) ?></span>
                    </h5>

                    <p class="text-muted mb-1">
                        <strong>Reporter:</strong> <?= htmlspecialchars($row['reporter_name']) ?>
                    </p>

                    <p class="mb-2">
                        <strong>Item:</strong>
                        <a href="listing.php?item_id=<?= $row['auction_id'] ?>" target="_blank">
                            <?= htmlspecialchars($row['item_title']) ?>
                        </a>
                    </p>

                    <p><strong>Description:</strong><br>
                        <?= nl2br(htmlspecialchars($row['description'])) ?>
                    </p>

                    <p class="text-muted small">
                        Reported at: <?= $row['created_at'] ?>
                    </p>

                    <?php if ($row['auction_status']): ?>
                        <p class="mb-2">
                            <strong>Auction Status:</strong>
                            <span class="badge
                                <?php
                                    echo $row['auction_status'] === 'removed' ? 'bg-danger' :
                                         ($row['auction_status'] === 'active' ? 'bg-success' :
                                         ($row['auction_status'] === 'finished' ? 'bg-secondary' : 'bg-warning'));
                                ?>">
                                <?= strtoupper($row['auction_status']) ?>
                            </span>
                        </p>
                    <?php endif; ?>

                    <div class="btn-group" role="group">
                        <?php if ($row['status'] === 'open'): ?>
                            <a href="resolve_report.php?report_id=<?= $row['report_id'] ?>"
                               class="btn btn-success btn-sm">
                               <i class="fa fa-check"></i> Resolve
                            </a>
                        <?php endif; ?>

                        <?php if ($row['auction_id'] && $row['auction_status'] !== 'removed'): ?>
                            <a href="remove_auction.php?auction_id=<?= $row['auction_id'] ?>&report_id=<?= $row['report_id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to remove this auction? This action cannot be undone.');">
                               <i class="fa fa-ban"></i> Remove Auction
                            </a>
                        <?php elseif ($row['auction_status'] === 'removed'): ?>
                            <span class="btn btn-secondary btn-sm disabled">
                                <i class="fa fa-ban"></i> Already Removed
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

    <?php endif; ?>
</div>

<?php include_once 'footer.php'; ?>
