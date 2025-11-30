<?php
// remove_auction.php
// 管理员下架拍品

require_once 'utilities.php';
require_login();

// 检查是否管理员
if ($_SESSION['role_id'] != 3) {
    echo "<p style='color:red'>Access denied: Admin only.</p>";
    exit;
}

// 获取参数
$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;
$report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;

if ($auction_id <= 0) {
    echo "<p style='color:red'>Invalid auction ID.</p>";
    exit;
}

// 检查拍卖是否存在
$check_sql = "SELECT auction_id, status FROM auctions WHERE auction_id = ?";
$result = db_query($check_sql, "i", [$auction_id]);

if ($result->num_rows === 0) {
    echo "<p style='color:red'>Auction not found.</p>";
    exit;
}

$auction = $result->fetch_assoc();

// 检查拍卖是否已经被下架
if ($auction['status'] === 'removed') {
    header("Location: admin_reports.php?message=already_removed");
    exit;
}

// 更新拍卖状态为 'removed'
$update_sql = "UPDATE auctions SET status = 'removed' WHERE auction_id = ?";
db_query($update_sql, "i", [$auction_id]);

// 如果有report_id，同时标记举报为已解决
if ($report_id > 0) {
    $resolve_sql = "UPDATE reports SET status = 'resolved' WHERE report_id = ?";
    db_query($resolve_sql, "i", [$report_id]);
}

// 可以选择发送通知给卖家
// TODO: 添加通知功能

include_once 'header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fa fa-check-circle"></i> Auction Removed Successfully</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-success">
                <p class="mb-2"><strong>Auction #<?= $auction_id ?> has been removed from the platform.</strong></p>
                <p class="mb-0">The auction is now marked as 'removed' and will no longer be visible to buyers.</p>
            </div>

            <?php if ($report_id > 0): ?>
                <div class="alert alert-info">
                    <p class="mb-0"><i class="fa fa-info-circle"></i> Report #<?= $report_id ?> has been marked as resolved.</p>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="admin_reports.php" class="btn btn-primary">
                    <i class="fa fa-arrow-left"></i> Back to Reports
                </a>
                <a href="browse.php" class="btn btn-secondary">
                    <i class="fa fa-home"></i> Go to Homepage
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
