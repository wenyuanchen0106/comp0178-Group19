<?php
require_once 'utilities.php';
require_login();

// 只有 admin 才能访问
if ($_SESSION['role_name'] !== 'admin') {
    die("Access denied.");
}

// 删除 Auction
if (isset($_GET['delete_auction'])) {
    $auction_id = (int)$_GET['delete_auction'];
    db_query("DELETE FROM auctions WHERE auction_id = ?", "i", [$auction_id]);
    echo "<script>alert('Auction deleted successfully'); window.location='admin_dashboard.php';</script>";
    exit();
}

// 关闭 Auction
if (isset($_GET['close_auction'])) {
    $auction_id = (int)$_GET['close_auction'];
    db_query("UPDATE auctions SET status='finished', end_date=NOW() WHERE auction_id=?", "i", [$auction_id]);
    echo "<script>alert('Auction closed successfully'); window.location='admin_dashboard.php';</script>";
    exit();
}

// 驳回用户 Report
if (isset($_GET['dismiss_report'])) {
    $report_id = (int)$_GET['dismiss_report'];
    db_query("UPDATE reports SET status='dismissed' WHERE report_id=?", "i", [$report_id]);
    echo "<script>alert('Report dismissed'); window.location='admin_dashboard.php';</script>";
    exit();
}

// 处理用户 Report（标为已处理）
if (isset($_GET['resolve_report'])) {
    $report_id = (int)$_GET['resolve_report'];
    db_query("UPDATE reports SET status='resolved' WHERE report_id=?", "i", [$report_id]);
    echo "<script>alert('Report resolved'); window.location='admin_dashboard.php';</script>";
    exit();
}

// --- 获取所有拍卖 ---
$auctions = db_query_all("SELECT auction_id, title, status FROM auctions ORDER BY auction_id DESC");

// --- 获取所有 Report ---
$reports = db_query_all("SELECT r.report_id, r.user_id, r.item_id, r.reason, r.status, r.created_at 
                         FROM reports r ORDER BY r.created_at DESC");

include 'header.php';
?>

<div class="container mt-4">
    <h2>Admin Control Center</h2>

    <hr>

    <h3>📌 Manage Reports</h3>
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>ID</th><th>User</th><th>Item</th><th>Reason</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $rp): ?>
            <tr>
                <td><?= $rp['report_id'] ?></td>
                <td><?= $rp['user_id'] ?></td>
                <td><?= $rp['item_id'] ?></td>
                <td><?= htmlspecialchars($rp['reason']) ?></td>
                <td><?= $rp['status'] ?></td>
                <td>
                    <a href="?resolve_report=<?= $rp['report_id'] ?>" class="btn btn-success btn-sm">Resolve</a>
                    <a href="?dismiss_report=<?= $rp['report_id'] ?>" class="btn btn-warning btn-sm">Dismiss</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h3>📌 Manage Auctions</h3>
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>ID</th><th>Title</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($auctions as $a): ?>
            <tr>
                <td><?= $a['auction_id'] ?></td>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= $a['status'] ?></td>
                <td>
                    <a href="?close_auction=<?= $a['auction_id'] ?>" class="btn btn-primary btn-sm">Close</a>
                    <a href="?delete_auction=<?= $a['auction_id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>

<?php include 'footer.php'; ?>
